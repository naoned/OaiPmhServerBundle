<?php

namespace Naoned\OaiPmhServerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Naoned\OaiPmhServerBundle\Exception\OaiPmhServerException;
use Naoned\OaiPmhServerBundle\Exception\BadVerbException;
use Naoned\OaiPmhServerBundle\Exception\NoRecordsMatchException;
use Naoned\OaiPmhServerBundle\Exception\NoSetHierarchyException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

    private $queryParams = array();

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
            if ($e instanceof OaiPmhServerException) {
                $reflect = new \ReflectionClass($e);
                //Remove «Exception» at end of class namespace
                $code = substr($reflect->getShortName(), 0, -9);
                // lowercase first char
                $code[0] = strtolower(substr($code, 0, 1));
            } elseif ($e instanceof NotFoundHttpException) {
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
            $viewParams = array(
                'code'        => $code,
                'message'     => $message,
                'queryParams' => $this->queryParams,
            )
        );
    }

    private function identifyVerb()
    {
        $dataProvider = $this->get('naoned.oaipmh.data_provider');
        $oaiPmhRuler = $this->get('naoned.oaipmh.ruler');
        $this->queryParams = $oaiPmhRuler->retrieveAndCheckArguments(
            $this->getRequest()->query->all()
        );
        return $this->render(
            'NaonedOaiPmhServerBundle::identify.xml.twig',
            array(
                'dataProvider' => $dataProvider,
                'queryParams'  => $this->queryParams,
            )
        );
    }

    private function getRecordVerb()
    {
        $dataProvider = $this->get('naoned.oaipmh.data_provider');
        $oaiPmhRuler = $this->get('naoned.oaipmh.ruler');
        $this->queryParams = $oaiPmhRuler->retrieveAndCheckArguments(
            $this->getRequest()->query->all(),
            array(
                'metadataPrefix',
                'identifier',
            )
        );
        $oaiPmhRuler->checkMetadataPrefix($this->queryParams);
        $record = $this->retrieveRecord($this->queryParams['identifier']);

        return $this->render(
            'NaonedOaiPmhServerBundle::getRecord.xml.twig',
            array(
                'record'         => $record,
                'queryParams'    => $this->queryParams,
                'metadataPrefix' => $this->queryParams['metadataPrefix'],
            )
        );
    }

    private function listRecordsVerb($headersOnly = false)
    {
        $oaiPmhRuler = $this->get('naoned.oaipmh.ruler');
        $this->queryParams = $oaiPmhRuler->retrieveAndCheckArguments(
            $this->getRequest()->query->all(),
            array('metadataPrefix'),
            array('from','until','set'),
            array('resumptionToken')
        );
        if (!array_key_exists('resumptionToken', $this->queryParams)) {
            $oaiPmhRuler->checkMetadataPrefix($this->queryParams);
        }

        $dataProvider = $this->get('naoned.oaipmh.data_provider');
        $searchParams = $oaiPmhRuler->getSearchParams(
            $this->queryParams,
            $this->get('session')
        );
        $records = $dataProvider->getRecords(
            isset($searchParams['set']) ? $searchParams['set'] : null,
            isset($searchParams['from']) ? new \DateTime($searchParams['from']) : null,
            isset($searchParams['until']) ? new \DateTime($searchParams['until']) : null
        );
        if (!(is_array($records) || $records instanceof \ArrayObject)) {
            throw new Exception('Implementation error: Records must be an array or an arrayObject');
        }
        if (!count($records)) {
            throw new noRecordsMatchException();
        }
        $resumption = $oaiPmhRuler->getResumption($records, $searchParams, $this->get('session'));
        if (isset($searchParams['set']) && !count($setsBySelectionId)) {
            throw new noSetHierarchyException();
        }
        return $this->render(
            'NaonedOaiPmhServerBundle::listRecords.xml.twig',
            array(
                'records'        => $records,
                'headersOnly'    => $headersOnly,
                'resumption'     => $resumption,
                'starts'         => $searchParams['starts'],
                'ends'           => min($searchParams['ends'], count($records) - 1),
                'metadataPrefix' => $searchParams['metadataPrefix'],
                'queryParams'    => $this->queryParams,
            )
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
        $this->queryParams = $oaiPmhRuler->retrieveAndCheckArguments(
            $request->query->all(),
            array(),
            array('identifier')
        );
        // This is just for checking the record exists
        if (array_key_exists('identifier', $this->queryParams)) {
            $record = $this->retrieveRecord($this->queryParams['identifier']);
        }
        return $this->render(
            'NaonedOaiPmhServerBundle::listMetadataFormats.xml.twig',
            array(
                'availableMetadata' => $oaiPmhRuler->getAvailableMetadata(),
                'queryParams'       => $this->queryParams,
            )
        );
    }

    private function listSetsVerb()
    {
        $request = $this->getRequest();
        $oaiPmhRuler = $this->get('naoned.oaipmh.ruler');
        $this->queryParams = $oaiPmhRuler->retrieveAndCheckArguments(
            $request->query->all(),
            array(),
            array(),
            array('resumptionToken')
        );
        $dataProvider = $this->get('naoned.oaipmh.data_provider');
        $sets = $dataProvider->getSets();
        if ($sets !== null && (!(is_array($sets) || ($sets instanceof \ArrayObject)))) {
            throw new Exception('Implementation error: Sets must be an array or an arrayObject');
        }
        if (!count($sets)) {
            throw new NoSetHierarchyException();
        }
        $searchParams = $oaiPmhRuler->getSearchParams(
            $this->queryParams,
            $this->get('session')
        );
        $resumption = $oaiPmhRuler->getResumption($sets, $searchParams, $this->get('session'));
        return $this->render(
            'NaonedOaiPmhServerBundle::listSets.xml.twig',
            array(
                'query'        => $this->queryParams,
                'sets'         => $sets,
                'resumption'   => $resumption,
                'searchParams' => $searchParams,
                'queryParams'  => $this->queryParams,
                'starts'       => $searchParams['starts'],
                'ends'         => min($searchParams['ends'], count($sets) - 1),
            )
        );
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
