<?php

namespace Tests\OaiPmh;

use Naoned\OaiPmhServerBundle\OaiPmh\OaiPmhRuler;

class OaiPmhRulerTests
{
    public function testIndex()
    {
        $ruler = new OaiPmhRuler();

        // Requiered paramters
        $args = $ruler->retrieveAndCheckArguments(
            array('req' => 1),
            array('req')
        );
        $this->assertEqual($args, array('req' => 1));

        $this->setExpectedException('BadArgumentException');
        $ruler->retrieveAndCheckArguments(
            array('opt' => 1),
            array('req')
        );

        // Optional paramters
        $ruler->retrieveAndCheckArguments(
            array('opt' => 1),
            array(),
            array('opt')
        );
        $this->assertEqual($args, array('opt' => 1));

        $ruler->retrieveAndCheckArguments(
            array(),
            array(),
            array('opt')
        );
        $this->assertEqual($args, array());

        // Exclusive paramters
        $ruler->retrieveAndCheckArguments(
            array('exclusive' => 1),
            array(),
            array(),
            array('exclusive')
        );
        $this->assertEqual($args, array('exclusive' => 1));

        $this->setExpectedException('BadArgumentException');
        $ruler->retrieveAndCheckArguments(
            array('exclusive' => 1, 'other' => 1),
            array(),
            array(),
            array('exclusive')
        );

        // Unexpected paramters
        $this->setExpectedException('BadArgumentException');
        $ruler->retrieveAndCheckArguments(
            array('unexpected' => 1)
        );

        // Check unicity of parameters
        $this->setExpectedException('BadArgumentException');
        $ruler->checkParamsUnicity('param1=1&parma1=2');
    }
}
