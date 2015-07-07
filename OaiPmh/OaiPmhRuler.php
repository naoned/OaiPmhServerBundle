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
    private $defaultFrom = 0;
    private $defaultUntil = 40;
    // This server currently supports only DC Data
    private $availableMetadata = array(
        'oai_dc' => array(
           'schema'            => 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
           'metadataNamespace' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
        )
    );
    private $sessionPrefix = 'oaipmh_';

    public function getSessionPrefix()
    {
        return $this->sessionPrefix;
    }

    public function getAvailableMetadata()
    {
        return $this->availableMetadata;
    }

    public function getSearchParams($queryParams, $session)
    {
        if (array_key_exists('resumptionToken', $queryParams)
            && $resumptionToken = $queryParams['resumptionToken']
        ) {
            $sessionData = $session->get(self::sessionPrefix.$resumptionToken);
            if (!$sessionData || $sessionData['verb'] != $queryParams['verb']) {
                throw new badResumptionTokenException();
            }
            $searchParams = $sessionData;
        } else {
            $from = array_key_exists('from', $queryParams) && $queryParams['from']
                ? $queryParams['from']
                : $this->defaultFrom;
            $until = array_key_exists('until', $queryParams) && $queryParams['until']
                ? $queryParams['until']
                : $this->defaultUntil;
            if (!is_integer($until) || !is_integer($from)) {
                throw new BadArgumentException('UNTIL and FROM must both be integer');
            }
            if ($until <= $from) {
                throw new BadArgumentException('UNTIL cannot be higher than FROM');
            }
            $searchParams['numItemsPerPage'] = $until - $from;
            $searchParams['currentPage']     = $until / $searchParams['numItemsPerPage'];
            $searchParams['verb']            = $queryParams['verb'];
            $searchParams['set'] = array_key_exists('set', $queryParams) && $queryParams['set']
                ? $queryParams['set']
                : null;
            if (!is_int($searchParams['currentPage'])) {
                throw new BadArgumentException('Cannot paginate');
            }
        }

        return $searchParams;
    }

    public function generateResumptionToken()
    {
        return uniqid();
    }

    public function checkMetadataPrefix($queryParams)
    {
        if (!in_array($queryParams['metadataPrefix'], array_keys($this->availableMetadata))) {
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
