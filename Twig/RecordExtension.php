<?php

namespace Naoned\OaiPmhServerBundle\Twig;

use Naoned\OaiPmhServerBundle\DataProvider\DataProviderInterface;

class RecordExtension extends \Twig_Extension
{
    private $dataProvider;

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('get_record_sets', [$this, 'getRecordSets']),
            new \Twig_SimpleFunction('dublinize_record', [$this, 'dublinizeRecord']),
            new \Twig_SimpleFunction('get_record_id', [$this, 'getRecordId']),
            new \Twig_SimpleFunction('get_record_updated', [$this, 'getRecordUpdated']),
            new \Twig_SimpleFunction('get_thumb', [$this, 'getThumb']),
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
