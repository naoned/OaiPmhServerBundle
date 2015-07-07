<?php

namespace Naoned\OaiPmhServerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Naoned\OaiPmhServerBundle\Exception\OaiPmhServerException;
use Naoned\OaiPmhServerBundle\Exception\BadVerbException;
use Naoned\OaiPmhServerBundle\Exception\NoRecordsMatchException;
use Naoned\OaiPmhServerBundle\Exception\NoSetHierarchyException;

// Unsused for now since we assume all record are available in oAI_DC format
use Naoned\OaiPmhServerBundle\Exception\IdDoesNotExistException;

class MainController extends Controller
{
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
        $verb = $this->get('request')->get('verb');

        try {
            if (!in_array($verb, $this->availableVerbs)) {
                throw new BadVerbException();
            }
            $methodName = $verb.'Verb';
            return $this->$methodName();
        } catch (\Exception $e) {
            if (is_a($e, 'Naoned\OaiPmhServerBundle\Exception\OaiPmhServerException')) {
                $reflect = new \ReflectionClass($e);
                //Remove «Exception» at end of class namespace
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
        $dataProvider = $this->get('naoned.oaipmh.data_provider');
        $oaiPmhRuler = $this->get('naoned.oaipmh.ruler');
        $queryParams = $oaiPmhRuler->retrieveAndCheckArguments($this->getRequest()->query->all());
        $viewParams = array(
            'repositoryName'    => $dataProvider->getRepositoryName(),
            'adminEmail'        => $dataProvider->getAdminEmail(),
            'earliestDatestamp' => $dataProvider->getEarliestDatestamp(),
        );
        return $this->render(
            'NaonedOaiPmhServerBundle::identify.xml.twig',
            array_merge($queryParams, $viewParams)
        );
    }

    private function getRecordVerb()
    {
        $dataProvider = $this->get('naoned.oaipmh.data_provider');
        $oaiPmhRuler = $this->get('naoned.oaipmh.ruler');
        $queryParams = $oaiPmhRuler->retrieveAndCheckArguments(
            $this->getRequest()->query->all(),
            array(
                'metadataPrefix',
                'identifier',
            )
        );
        $oaiPmhRuler->checkMetadataPrefix($queryParams);
        $record = $this->retrieveRecord();
        $viewParams = array(
            'record' => $record,
            'sets'   => $dataProvider->getAllSetsBySelectionId(),
        );

        return $this->render(
            'NaonedOaiPmhServerBundle::getRecord.xml.twig',
            array_merge($queryParams, $viewParams)
        );
    }

    private function listRecordsVerb($headersOnly = false)
    {
        $oaiPmhRuler = $this->get('naoned.oaipmh.ruler');
        $queryParams = $oaiPmhRuler->retrieveAndCheckArguments(
            $this->getRequest()->query->all(),
            array('metadataPrefix'),
            array('from','until','set'),
            array('resumptionToken')
        );
        if (!array_key_exists('resumptionToken', $queryParams)) {
            $oaiPmhRuler->checkMetadataPrefix($queryParams);
        }

        $dataProvider = $this->get('naoned.oaipmh.data_provider');
        $searchParams = $oaiPmhRuler->getSearchParams($queryParams, $this->get('session'));
        $records = $dataProvider->getRecords($searchParams['set']);
        if (!(is_array($records) || $records instanceof \ArrayObject)) {
            throw new Exception('Implementation error: Records must be an array or an arrayObject');
        }
        $records = $this->paginate($records, $searchParams, $this->get('session'));
        if (!count($records)) {
            throw new noRecordsMatchException();
        }
        $setsBySelectionId = $dataProvider->getAllSetsBySelectionId();
        if ($searchParams['set'] && !count($setsBySelectionId)) {
            throw new noSetHierarchyException();
        }
        $viewParams = array(
            'records'     => $records,
            'headersOnly' => $headersOnly,
            'resumption'  => $this->resumption,
            'sets'        => $setsBySelectionId,
        );

        return $this->render(
            'NaonedOaiPmhServerBundle::listRecords.xml.twig',
            array_merge($queryParams, $viewParams)
        );
    }

    private function listIdentifiersVerb()
    {
        return $this->listRecordsVerb(true);
    }

    private function listMetadataFormatsVerb()
    {
        $request = $this->getRequest();
        $oaiPmhRuler = $this->get('naoned.oaipmh.ruler');
        $queryParams = $oaiPmhRuler->retrieveAndCheckArguments($request->query->all(), array(), array('identifier'));
        // This is just for checking the record exists
        if (array_key_exists('identifier', $queryParams)) {
            $record = $this->retrieveRecord($queryParams['identifier']);
        }
        return $this->render(
            'NaonedOaiPmhServerBundle::listMetadataFormats.xml.twig',
            array(
                'query' => $queryParams,
                'availableMetadata' => $oaiPmhRuler->getAvailableMetadata(),
            )
        );
    }

    private function listSetsVerb()
    {
        $request = $this->getRequest();
        $oaiPmhRuler = $this->get('naoned.oaipmh.ruler');
        $queryParams = $oaiPmhRuler->retrieveAndCheckArguments($request->query->all(), array(), array(), array('resumptionToken'));
        $dataProvider = $this->get('naoned.oaipmh.data_provider');
        $sets = $dataProvider->getSets();
        if ($sets !== null && (!(is_array($sets) || ($sets instanceof \ArrayObject)))) {
            throw new Exception('Implementation error: Sets must be an array or an arrayObject');
        }
        if (!count($sets)) {
            throw new NoSetHierarchyException();
        }
        $searchParams = $oaiPmhRuler->getSearchParams($queryParams, $this->get('session'));
        return $this->render(
            'NaonedOaiPmhServerBundle::listSets.xml.twig',
            array(
                'query'      => $queryParams,
                'sets'       => $this->paginate($sets, $searchParams, $this->get('session')),
                'resumption' => $this->resumption,
            )
        );
    }

    public function paginate($iterator, $searchParams, $session)
    {
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $iterator,
            $searchParams['currentPage'],
            $searchParams['numItemsPerPage']
        );
        $data = $pagination->getPaginationData();
        $this->resumption = null;
        if ($data['last'] != $data['current']) {
            $this->resumption =array(
                'token'     => $this->generateResumptionToken(),
                'expiresOn' => time()+604800,
            );
            $sessionPrefix = $this->get('naoned.oaipmh.ruler')->getSessionPrefix();
            $session->set(
                $sessionPrefix.$this->resumption['token'],
                array_merge(
                    $searchParams,
                    array(
                        'currentPage'     => $searchParams['currentPage']+1,
                    )
                )
            );
        }
        return $pagination;
    }

    private function retrieveRecord($id)
    {
        $dataProvider = $this->get('naoned.oaipmh.data_provider');
        $record = $dataProvider->getRecord($id);
        if (!$record) {
            throw new idDoesNotExistException();
        }
        return $record;
    }
}
