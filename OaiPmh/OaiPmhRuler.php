<?php

namespace Naoned\OaiPmhServerBundle\OaiPmh;

use Naoned\OaiPmhServerBundle\Exception\OaiPmhServerException;
use Naoned\OaiPmhServerBundle\Exception\BadArgumentException;
use Naoned\OaiPmhServerBundle\Exception\BadResumptionTokenException;
use Naoned\OaiPmhServerBundle\Exception\CannotDisseminateFormatException;

// Unsused for now since we assume all record are available in oAI_DC format
use Naoned\OaiPmhServerBundle\Exception\IdDoesNotExistException;

class OaiPmhRuler
{
    private static $defaultStarts = 0;
    public $countPerLoad;
    // This server currently supports only oai_dc Data format
    private static $availableMetadata = array(
        'oai_dc' => array(
           'schema'            => 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
           'metadataNamespace' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
        )
    );
    private static $sessionPrefix = 'oaipmh_';

    public function setCountPerLoad($countPerLoad)
    {
        $this->countPerLoad = $countPerLoad;
    }

    public function getSessionPrefix()
    {
        return $this->sessionPrefix;
    }

    public function getAvailableMetadata()
    {
        return self::$availableMetadata;
    }

    public function getSearchParams($queryParams, $session)
    {
        if (array_key_exists('resumptionToken', $queryParams)
            && $resumptionToken = $queryParams['resumptionToken']
        ) {
            $sessionData = $session->get(self::$sessionPrefix.$resumptionToken);
            if (!$sessionData || $sessionData['verb'] != $queryParams['verb']) {
                throw new badResumptionTokenException();
            }
            $searchParams = $sessionData;
        } else {
            $searchParams           = $queryParams;
            $searchParams['starts'] = self::$defaultStarts;
            $searchParams['ends']   = self::$defaultStarts + $this->countPerLoad - 1;
        }

        return $searchParams;
    }

    public function generateResumptionToken()
    {
        return uniqid();
    }

    public function getResumption($items, $searchParams, $session)
    {
        $resumption = array();
        $resumption['next'] = false;
        $itemMax = count($items) - 1;
        if ($searchParams['ends'] < $itemMax) {
            $resumption['next']       = true;
            $resumption['token']      = $this->generateResumptionToken();
            $resumption['expiresOn']  = time() + 604800;
            $resumption['totalCount'] = count($items);
            $session->set(
                self::$sessionPrefix.$resumption['token'],
                array_merge(
                    $searchParams,
                    array(
                        'starts' => $searchParams['starts'] + $this->countPerLoad,
                        'ends'   => $searchParams['starts'] + $this->countPerLoad * 2,
                    )
                )
            );
        }
        $resumption['starts'] = $searchParams['starts'];
        $ends = $searchParams['starts'] + $this->countPerLoad - 1;
        $resumption['ends'] = min($itemMax, $ends);
        $resumption['totalCount'] = count($items);
        $resumption['items'] = $items;
        $resumption['isFirst'] = $resumption['starts'] == self::$defaultStarts;
        $resumption['isLast'] = $resumption['ends'] == $itemMax;

        return $resumption;
    }

    public function checkMetadataPrefix($queryParams)
    {
        if (!in_array($queryParams['metadataPrefix'], array_keys(self::$availableMetadata))) {
            throw new cannotDisseminateFormatException();
        }
    }

    // Retrieve arguments and check requirements are fulfilled
    public static function retrieveAndCheckArguments(
        array $allArguments,
        array $required = array(),
        array $optionnal = array(),
        array $exclusive = array()
    ) {
        $found = false;
        $queryParams = array();
        $queryParams['verb'] = $allArguments['verb'];
        foreach ($exclusive as $name) {
            if (array_key_exists($name, $allArguments)) {
                $queryParams[$name] = $allArguments[$name];
                $found = true;
            }
        }
        if (!$found) {
            foreach ($required as $name) {
                if (!array_key_exists($name, $allArguments)) {
                    throw new BadArgumentException('The request is missing required arguments');
                }
                $queryParams[$name] = $allArguments[$name];
            }
            foreach ($optionnal as $name) {
                if (array_key_exists($name, $allArguments)) {
                    $queryParams[$name] = $allArguments[$name];
                }
            }
        }

        self::checkNoOtherArguments($queryParams, $allArguments);
        return $queryParams;
    }

    private static function checkNoOtherArguments($queryParams, $allArguments)
    {
        unset($allArguments['verb']);
        if (count(array_diff(array_keys($allArguments), array_keys($queryParams)))) {
            throw new BadArgumentException('The request includes illegal arguments');
        }
    }
}
