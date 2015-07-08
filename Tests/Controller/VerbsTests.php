<?php

namespace Naoned\OaiPmhServerBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class VerbsTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/OaiPmh/?verb=GetIdentity');
        $this->assertEqual(1, $crawler->filter('OAI-PMH>Identify>repositoryName')->count());

        $this->setExpectedException('BadVerbException');
        $crawler = $client->request('GET', '/OaiPmh/?verb=GetIdentit');
    }
}
