<?php

namespace Naoned\OaiPmhServerBundle\DataProvider;

interface DataProviderInterface
{
    public function getRepositoryName();

    public function getAdminEmail();

    public function getEarliestDatestamp();

    public function getRecord($id);
}
