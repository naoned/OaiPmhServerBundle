<?php

namespace Naoned\OaiPmhServerBundle\Twig;

use Naoned\OaiPmhServerBundle\DataProvider\DataProviderInterface;

class RecordExtension extends \Twig_Extension
{
    private $getSetsForRecordClosure;

    public function getFunctions()
    {
        return array(
            'get_record_sets' => new \Twig_Function_Method($this, 'getRecordSets'),
        );
    }

    public function setDataProvider(DataProviderInterface $dataProvider)
    {
        $this->getSetsForRecordClosure = $dataProvider->getSetsForRecordClosure();
    }

    public function getRecordSets(array $record)
    {
        $closure = $this->getSetsForRecordClosure;
        return $closure($record);
    }

    // for a service we need a name
    public function getName()
    {
        return 'oaipmh_record';
    }
}
