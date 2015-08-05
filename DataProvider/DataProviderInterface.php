<?php

namespace Naoned\OaiPmhServerBundle\DataProvider;

interface DataProviderInterface
{
    /**
     * @return string Repository name
     */
    public function getRepositoryName();

    /**
     * @return string Repository admin email
     */
    public function getAdminEmail();

    /**
     * @return \DateTime|string     Repository earliest update change on data
     */
    public function getEarliestDatestamp();

    /**
     * must return an array of arrays with keys «identifier» and «name»
     * @return array List of all sets, with identifier and name
     */
    public function getRecord($id);

    /**
     * Search for records
     * @param  String|null    $setTitle Title of wanted set
     * @param  \DateTime|null $from     Date of last change «from»
     * @param  \DataTime|null $until    Date of last change «until»
     * @return array|ArrayObject        List of items
     */
    public function getRecords($set = null, \DateTime $from = null, \DateTime $until = null);

    /**
     * Tell me, this «record», in which «set is it ?
     * @param  any   $record An item of elements furnished by getRecords method
     * @return array         List of sets, the record belong to
     */
    public function getSets();

    /**
     * Transform the provided record in an array with Dublin Core, «dc_title»  style
     * @param  any   $record An item of elements furnished by getRecords method
     * @return array         Dublin core data
     */
    public function getSetsForRecord($record);

    /**
     * Get an array of [selection.id] => "set.title" for a all published OAI-PMH sets
     * @return array
     */
    public function dublinizeRecord($record);

    /**
     * Check if sets are supported by data provider
     * @return boolean check
     */
    public function checkSupportSets();

    /**
     * Get identifier of id
     * @param  any   $record An item of elements furnished by getRecords method
     * @return string        Record Id
     */
    public static function getRecordId($record);

    /**
     * Get last change date
     * @param  any   $record An item of elements furnished by getRecords method
     * @return \DateTime|string     Record last change
     */
    public static function getRecordUpdated($record);
}
