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
use Naoned\OaiPmhServerBundle\Exception\IdDoesNotExistException;

class MainController extends Controller
{
    private $availableMetadataPrefixes = array(
        'oai_dc',
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

        $record = $dataProvider->getRecord($this->queryParams['identifier']);
        if (!$record) {
            throw new idDoesNotExistException();
        }
        $viewParams = array(
            'record' => $record,
            'sets'   => $dataProvider->getSets($record),
        );

        return $this->render(
            'NaonedOaiPmhServerBundle::getRecord.xml.twig',
            array_merge($this->queryParams, $viewParams)
        );
    }

    private function checkMetadataPrefix()
    {
        if (!in_array($this->queryParams['metadataPrefix'], $this->availableMetadataPrefixes)) {
            throw new cannotDisseminateFormatException();
        }
    }

    private function listMetadataFormatsVerb()
    {
        $this->retrieveAndCheckArguments(array(
            'metadataPrefix',
            'set',
            'from',
            'until',
            'identifier',
            'resumptionToken',
        ));

        // throw new idDoesNotExistException();
        return $this->render(
            'NaonedOaiPmhServerBundle::ListMetadataFormats.xml.twig',
            $this->queryParams
        );
    }

    private function listIdentifiersVerb()
    {
        $this->retrieveAndCheckArguments(array(
            'metadataPrefix',
            'set',
            'from',
            'until',
            'identifier',
            'resumptionToken',
        ));

        // throw new badResumptionTokenException();
        // throw new cannotDisseminateFormatException();
        // throw new noRecordsMatchException();
        // throw new noSetHierarchyException();
        return $this->render(
            'NaonedOaiPmhServerBundle::identify.xml.twig',
            $this->queryParams + $viewParams
        );
    }

    private function listRecordsVerb()
    {
        // throw new badResumptionTokenException();
        // throw new cannotDisseminateFormatException();
        // throw new noRecordsMatchException();
        // throw new noSetHierarchyException();
        return $this->render(
            'NaonedOaiPmhServerBundle::identify.xml.twig',
            $this->queryParams + $viewParams
        );
    }

    private function retrieveAndCheckArguments(
        array $required = array(),
        array $optionnal = array(),
        array $exclusive = array()
    ) {
        $request = $this->getRequest();
        $found = false;
        foreach ($exclusive as $name) {
            $this->queryParams[$name] = $request->get($name);
            $found = true;
        }
        if ($found) {
            $this->checkNoOtherArguments();
            return;
        }

        foreach ($required as $name) {
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
