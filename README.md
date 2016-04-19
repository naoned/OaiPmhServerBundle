# OaiPmhServerBundle

## About

Provides an Oai-Pmh server to serve your data.
This is an Oai-Pmh server only, you have to plug your own data provider.

## Features

* Compliant with official Oai-Pmh tech spec : http://www.openarchives.org/OAI/openarchivesprotocol.html
* Sucessfully pass http://re.cs.uct.ac.za/ test
* Automated resumption in large list, with arrays or ArrayObject
* On the fly XML generation, if you provide Records in a real-time data-accesing ArrayObject
* Parametrable resumption items-per-page (default at 50)

## Limitations

* Does not supports resumption (by token) on set lists
* More data formats (currently supports Dublin Core only)

## Installation

Require the `naoned/OaiPmhServer` package in your composer.json and update your dependencies.

    $ composer require naoned/OaiPmhServer:*

Add the NaonedOaiPmhServerBundle to your application's kernel:

```php
    public function registerBundles()
    {
        $bundles = array(
            ...
            new Naoned\OaiPmhServer\NaonedOaiPmhServerBundle(),
            ...
        );
        ...
    }
```

## Configuration


Add to your config.yml
```yml
naoned_oai_pmh_server:
    data_provider_service_name: naoned.oaipmh.data_provider
    count_per_load: 50
```
You can choose here nb of records and sets in list with resumption

Add to your routing.yml
```yml
naoned_oai_pmh_server:
    resource: "@NaonedOaiPmhServerBundle/Resources/config/routing.yml"
    prefix:   /oaipmh

```
You can choose here route to your Oai-Pmh server


Add to your services.yml
In your own Bundle (that manage data), add a service to expose data
```yml
    naoned.oaipmh.data_provider:
        class: [YOUR_VENDOR]\[YOUR_BUNDLE]\[YOUR_PATH]\[YOUR_CLASS]
        calls:
            - [ setContainer, ["@service_container"] ]
```

## Create Data provider

Fournishing data is up to you.
That’s why you have to define a service.
In order to do it, create on your side a class based on this example :

```php

namespace [YOUR_VENDOR]\[YOUR_BUNDLE]\[YOUR_PATH];

use Naoned\OaiPmhServerBundle\DataProvider\DataProviderInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class [YOUR_CLASS] extends ContainerAware implements DataProviderInterface
{
    /**
     * @return string Repository name
     */
    public function getRepositoryName()
    {
        return 'My super Oai-Pmh Server';
    }

    /**
     * @return string Repository admin email
     */
    public function getAdminEmail()
    {
        return 'me@home.com';
    }

    /**
     * @return \DateTime|string     Repository earliest update change on data
     */
    public function getEarliestDatestamp()
    {
        return "2015-01-01";
    }

    /**
     * @param  string $identifier [description]
     * @return array
     */
    public function getRecord($identifier)
    {
        return array(
            'title'       => 'Dummy content',
            'description' => 'Some more dummy content',
            'sets'        => array('seta', 'setb'),
        );
    }

    /**
     * must return an array of arrays with keys «identifier» and «name»
     * @return array List of all sets, with identifier and name
     */
    public function getSets()
    {
        return array(
            array(
                'identifier' => 'seta',
                'name'       => 'THE set number A',
            ),
            array(
                'identifier' => 'setb',
                'name'       => 'THE set identified by B',
            )
        );
    }

    /**
     * Search for records
     * @param  String|null    $setTitle Title of wanted set
     * @param  \DateTime|null $from     Date of last change «from»
     * @param  \DataTime|null $until    Date of last change «until»
     * @return array|ArrayObject        List of items
     */
    public function getRecords($setTitle = null, \DateTime $from = null, \DataTime $until = null)
    {
        return array(
            array(
                'identifier'  => '1W1',
                'title'       => 'Dummy content 1',
                'description' => 'Some more dummy content',
                'last_change' => '2015-10-12',
                'sets'        => array('seta', 'setb'),
            ),
            array(
                'identifier'  => '1W2',
                'title'       => 'Dummy content 2',
                'description' => 'Some more dummy content',
                'last_change' => '2015-10-12',
                'sets'        => array('seta'),
            ),
            array(
                'identifier'  => '1W3',
                'title'       => 'Dummy content 3',
                'description' => 'Some more dummy content',
                'last_change' => '2015-10-12',
                'sets'        => array('seta'),
            ),
            array(
                'identifier'  => '1W4',
                'title'       => 'Dummy content 4',
                'description' => 'Some more dummy content',
                'last_change' => '2015-10-12',
                'sets'        => array('setc'),
            ),
            array(
                'identifier'  => '1W5',
                'title'       => 'Dummy content 5',
                'description' => 'Some more dummy content',
                'last_change' => '2015-10-12',
                'sets'        => array('setd'),
            ),
        );
    }

    /**
     * Tell me, this «record», in which «set» is it ?
     * @param  any   $record An item of elements furnished by getRecords method
     * @return array         List of sets, the record belong to
     */
    public function getSetsForRecord($record)
    {
        return $record['sets'];
    }

    /**
     * Transform the provided record in an array with Dublin Core, «dc_title»  style
     * @param  any   $record An item of elements furnished by getRecords method
     * @return array         Dublin core data
     */
    public static function dublinizeRecord($record)
    {
        return array(
            'dc_identifier'  => $record['identifier'],
            'dc_title'       => $record['title'],
            'dc_description' => $record['description'],
        );
    }

    /**
     * Check if sets are supported by data provider
     * @return boolean check
     */
    public function checkSupportSets()
    {
        return true;
    }

    /**
     * Get identifier of id
     * @param  any   $record An item of elements furnished by getRecords method
     * @return string        Record Id
     */
    public static function getRecordId($record)
    {
        return $record['identifier'];
    }

    /**
     * Get last change date
     * @param  any   $record An item of elements furnished by getRecords method
     * @return \DateTime|string     Record last change
     */
    public static function getRecordUpdated($record)
    {
        return $record['last_change'];
    }
}

```

If you use Symfony >= 2.8, use ContainerAwareTrait instead of extending ContainerAware :

```php
namespace [YOUR_VENDOR]\[YOUR_BUNDLE]\[YOUR_PATH];

use Naoned\OaiPmhServerBundle\DataProvider\DataProviderInterface
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class [YOUR_CLASS] implements DataProviderInterface
{
    use ContainerAwareTrait;

    ...
}
```

Of course, you have to implement data retreiveing here, based on anything : db (Sql), mappers (Doctrine, Pomm) or any other data storing (ElasticSearch …). That why I made this class container aware, but you can preferely set required services via setters.

In addition, lists (records ans sets) can be sent as ArrayObjects, in order to manage data calling in an other class that implements ```\ArrayObject```.
