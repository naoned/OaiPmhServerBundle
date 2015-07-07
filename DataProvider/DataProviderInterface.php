<?php

namespace Naoned\OaiPmhServerBundle\DataProvider;

interface DataProviderInterface
{
    public function getRepositoryName();

    public function getAdminEmail();

    public function getEarliestDatestamp();

    public function getRecord($id);

    public function getRecords($set = null, \DateTime $from = null, \DataTime $until = null);

    public function getSetsForRecord($record);

    public function getSets();
}
