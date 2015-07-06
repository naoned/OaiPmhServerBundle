<?php

namespace Naoned\OaiPmhServerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Naoned\OaiPmhServerBundle\Exception\OaiPmhServerException;
use Naoned\OaiPmhServerBundle\Exception\BadVerbException;
use Naoned\OaiPmhServerBundle\Exception\BadArgumentException;
use Naoned\OaiPmhServerBundle\Exception\BadResumptionTokenException;
use Naoned\OaiPmhServerBundle\Exception\CannotDisseminateFormatException;
use Naoned\OaiPmhServerBundle\Exception\NoRecordsMatchException;
use Naoned\OaiPmhServerBundle\Exception\NoSetHierarchyException;

// Unsused for now since we assume all record are available in oAI_DC format
use Naoned\OaiPmhServerBundle\Exception\IdDoesNotExistException;

class MainController extends Controller
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
    private $availableVerbs = array(
        'GetRecord',
        'Identify',
        'ListIdentifiers',
        'ListMetadataFormats',
        'ListRecords',
        'ListSets',
    );

    public function indexAction()
    {
        $request = $this->get('request');
        $this->queryParams['verb'] = $request->get('verb');

        try {
            if (!in_array($this->queryParams['verb'], $this->availableVerbs)) {
                throw new BadVerbException();
            }
            $methodName = $this->queryParams['verb'].'Verb';
            return $this->$methodName();
        } catch (\Exception $e) {
            if (is_a($e, 'Naoned\OaiPmhServerBundle\Exception\OaiPmhServerException')) {
                $reflect = new \ReflectionClass($e);
                //Remove «Exception» at end of class name
                $code = substr($reflect->getShortName(), 0, -9);
                // lowercase first char
                $code[0] = strtolower(substr($code, 0, 1));
            } elseif (is_a($e, 'Symfony\Component\HttpKernel\Exception\NotFoundHttpException')) {
                $code = 'notFoundError';
            } else {
                $code = 'unknownError';
            }
            return $this->error($code, $e->getMessage());
        }
    }

    private function error($code, $message = '')
    {
        if (!$message) {
            $message = 'Unknown error';
        }
        return $this->render(
            'NaonedOaiPmhServerBundle::error.xml.twig',
            array(
                'code'    => $code,
                'message' => $message,
            )
        );
    }

    private function identifyVerb()
    {
        $dataProvider = $this->get('oai_pmh_data_provider');
        $this->retrieveAndCheckArguments();
        $viewParams = array(
            'repositoryName'    => $dataProvider->getRepositoryName(),
            'adminEmail'        => $dataProvider->getAdminEmail(),
            'earliestDatestamp' => $dataProvider->getEarliestDatestamp(),
        );
        return $this->render(
            'NaonedOaiPmhServerBundle::identify.xml.twig',
            array_merge($this->queryParams, $viewParams)
        );
    }

    private function getRecordVerb()
    {
        $dataProvider = $this->get('oai_pmh_data_provider');
        $this->retrieveAndCheckArguments(array(
            'metadataPrefix',
            'identifier',
        ));
        $this->checkMetadataPrefix();
        $record = $this->retrieveRecord();
        $viewParams = array(
            'record' => $record,
            'sets'   => $dataProvider->getSetsForRecord($record),
        );

        return $this->render(
            'NaonedOaiPmhServerBundle::getRecord.xml.twig',
            array_merge($this->queryParams, $viewParams)
        );
    }

    private function listRecordsVerb($headersOnly = false)
    {
        $this->retrieveAndCheckArguments(
            array('metadataPrefix'),
            array('from','until','set'),
            array('resumptionToken')
        );
        if (!array_key_exists('resumptionToken', $this->queryParams)) {
            $this->checkMetadataPrefix();
        }

        $dataProvider = $this->get('oai_pmh_data_provider');
        $this->retrieveRealParams();
        $records = $dataProvider->getRecordsIterator($this->realParams['set']);
        $records = $this->paginate($records);
        if (!count($records)) {
            throw new noRecordsMatchException();
        }

        $viewParams = array(
            'records'     => $records,
            'headersOnly' => $headersOnly,
            'resumption' => $this->resumption,
        );

        // throw new noSetHierarchyException();
        return $this->render(
            'NaonedOaiPmhServerBundle::listRecords.xml.twig',
            array_merge($this->queryParams, $viewParams)
        );
    }

    private function listIdentifiersVerb()
    {
        return $this->listRecordsVerb(true);
    }

    private function listMetadataFormatsVerb()
    {
        $this->retrieveAndCheckArguments(array(), array('identifier'));
        $viewParams = array(
        );
        // This is just for checking the record exists
        if ($this->queryParams['identifier']) {
            $record = $this->retrieveRecord();
        }
        return $this->render(
            'NaonedOaiPmhServerBundle::listMetadataFormats.xml.twig',
            array(
                'query' => $this->queryParams,
                'availableMetadata' => $this->availableMetadata,
            )
        );
    }

    private function listSetsVerb()
    {
        $this->retrieveAndCheckArguments(array(), array(), array('resumptionToken'));
        $dataProvider = $this->get('oai_pmh_data_provider');
        $sets = $dataProvider->getSetsIterator();
        if (!count($sets)) {
            throw new NoSetHierarchyException();
        }
        $this->retrieveRealParams();
        return $this->render(
            'NaonedOaiPmhServerBundle::listSets.xml.twig',
            array(
                'query'      => $this->queryParams,
                'sets'       => $this->paginate($sets),
                'resumption' => $this->resumption,
            )
        );
    }

    public function retrieveRealParams()
    {
        if (array_key_exists('resumptionToken', $this->queryParams)
            && $resumptionToken = $this->queryParams['resumptionToken']
        ) {
            $sessionData = $this->get('session')->get('oaipmh_'.$resumptionToken);
            if (!$sessionData || $sessionData['verb'] != $this->queryParams['verb']) {
                throw new badResumptionTokenException();
            }
            $this->realParams = $sessionData;
        } else {
            $from = array_key_exists('from', $this->queryParams) && $this->queryParams['from']
                ? $this->queryParams['from']
                : $this->defaultFrom;
            $until = array_key_exists('until', $this->queryParams) && $this->queryParams['until']
                ? $this->queryParams['until']
                : $this->defaultUntil;
            if ($until <= $from) {
                throw new BadArgumentException('UNTIL cannot be higher than FROM');
            }
            $this->realParams['numItemsPerPage'] = $until - $from;
            $this->realParams['currentPage']     = $until / $this->realParams['numItemsPerPage'];
            $this->realParams['verb']            = $this->queryParams['verb'];
            $this->realParams['set'] = array_key_exists('set', $this->queryParams) && $this->queryParams['set']
                ? $this->queryParams['set']
                : null;
            if (!is_int($this->realParams['currentPage'])) {
                throw new BadArgumentException('Cannot paginate');
            }
        }
    }

    public function paginate($iterator)
    {
        $paginator = $this->get('knp_paginator');
        // $paginator->setDefaultPaginatorOptions(array('pageParameterName' => 'group'));
        $pagination = $paginator->paginate(
            $iterator,
            $this->realParams['currentPage'],
            $this->realParams['numItemsPerPage']
        );
        $data = $pagination->getPaginationData();
        $this->resumption = null;
        if ($data['last'] != $data['current']) {
            $this->resumption =array(
                'token'     => $this->generateResumptionToken(),
                'expiresOn' => time()+604800,
            );
            $this->get('session')->set(
                'oaipmh_'.$this->resumption['token'],
                array_merge(
                    $this->realParams,
                    array(
                        'currentPage'     => $this->realParams['currentPage']+1,
                    )
                )
            );
        }

        return $pagination;
    }


    private function generateResumptionToken()
    {
        return uniqid();
    }

    private function retrieveRecord()
    {
        $dataProvider = $this->get('oai_pmh_data_provider');
        $record = $dataProvider->getRecord($this->queryParams['identifier']);
        if (!$record) {
            throw new idDoesNotExistException();
        }
        return $record;
    }

    private function checkMetadataPrefix()
    {
        if (!in_array($this->queryParams['metadataPrefix'], array_keys($this->availableMetadata))) {
            throw new cannotDisseminateFormatException();
        }
    }

    // Retrieve arguments and check requirements are fulfilled
    private function retrieveAndCheckArguments(
        array $required = array(),
        array $optionnal = array(),
        array $exclusive = array()
    ) {
        $request = $this->getRequest();
        $found = false;
        foreach ($exclusive as $name) {
            if ($request->get($name)) {
                $this->queryParams[$name] = $request->get($name);
                $found = true;
            }
        }
        if ($found) {
            $this->checkNoOtherArguments();
            return;
        }

        foreach ($required as $name) {
            if (!$request->get($name)) {
                throw new BadArgumentException();
            }
            $this->queryParams[$name] = $request->get($name);
        }
        foreach ($optionnal as $name) {
            $this->queryParams[$name] = $request->get($name);
        }
        $this->checkNoOtherArguments();
    }

    private function checkNoOtherArguments()
    {
        $request = $this->getRequest();
        if (count(array_diff(array_keys($request->query->all()), array_keys($this->queryParams)))) {
            throw new BadArgumentException();
        }
    }
}
