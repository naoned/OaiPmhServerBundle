<?php

namespace Naoned\OaiPmhServerBundle\Twig;

use Naoned\OaiPmhServerBundle\DataProvider\DataProviderInterface;

class RecordExtension extends \Twig_Extension
{
    private $dataProvider;

    public function getFunctions()
    {
        return array(
            'get_record_sets'    => new \Twig_Function_Method($this, 'getRecordSets'),
            'dublinize_record'   => new \Twig_Function_Method($this, 'dublinizeRecord'),
            'get_record_id'      => new \Twig_Function_Method($this, 'getRecordId'),
            'get_record_updated' => new \Twig_Function_Method($this, 'getRecordUpdated'),
            'get_thumb'          => new \Twig_Function_Method($this, 'getThumb'),
        );
    }

    public function setDataProvider(DataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    public function getRecordSets($record)
    {
        return $this->dataProvider->getSetsForRecord($record);
    }

    public function dublinizeRecord($record)
    {
        return $this->dataProvider->dublinizeRecord($record);
    }

    public function getRecordId($record)
    {
        return $this->dataProvider->getRecordId($record);
    }

    public function getRecordUpdated($record)
    {
        return $this->dataProvider->getRecordUpdated($record);
    }

    public function getThumb($record)
    {
        return $this->dataProvider->getThumb($record);
    }

    // for a service we need a name
    public function getName()
    {
        return 'oaipmh_record';
    }
}
