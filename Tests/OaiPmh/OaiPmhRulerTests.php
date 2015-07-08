<?php

namespace Naoned\OaiPmhServerBundle\Tests\OaiPmh;

use Naoned\OaiPmhServerBundle\OaiPmh\OaiPmhRuler;

class OaiPmhRulerTests
{
    public function testIndex()
    {
        $ruler = new OaiPmhRuler();

        $args = $ruler->retrieveAndCheckArguments(
            array('req' => 1),
            array('req')
        );
        $this->assertEqual($args, array('req' => 1));

        $this->setExpectedException('BadArgumentException');
        $ruler->retrieveAndCheckArguments(
            array('req' => 1),
            array('opt')
        );
    }
}
