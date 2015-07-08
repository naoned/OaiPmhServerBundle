<?php

namespace Naoned\OaiPmhServerBundle\Twig;

use Naoned\OaiPmhServerBundle\DataProvider\DataProviderInterface;

class RecordExtension extends \Twig_Extension
{
    private $dataProvider;

    public function getFunctions()
    {
        return array(
            'get_record_sets'  => new \Twig_Function_Method($this, 'getRecordSets'),
            'dublinize_record' => new \Twig_Function_Method($this, 'dublinizeRecord'),
        );
    }

    public function setDataProvider(DataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    public function getRecordSets(array $record)
    {
        return $this->dataProvider->getSetsForRecord($record);
    }

    public function dublinizeRecord(array $record)
    {
        return $this->dataProvider->dublinizeRecord($record);
    }

    // for a service we need a name
    public function getName()
    {
        return 'oaipmh_record';
    }
}
