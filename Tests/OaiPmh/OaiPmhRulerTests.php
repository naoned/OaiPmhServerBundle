<?php

namespace Naoned\OaiPmhServerBundle\Tests\OaiPmh;

use Naoned\OaiPmhServerBundle\OaiPmh\OaiPmhRuler;

class OaiPmhRulerTests extends \PHPUnit_Framework_TestCase
{
    // Requiered paramters
    public function testRequiredParams()
    {
        $ruler = new OaiPmhRuler();

        $args = $ruler->retrieveAndCheckArguments(
            array('req' => 1),
            array('req')
        );
        $this->assertEquals($args, array('req' => 1));

        $this->setExpectedException('Naoned\OaiPmhServerBundle\Exception\BadArgumentException');
        $ruler->retrieveAndCheckArguments(
            array('opt' => 1),
            array('req')
        );

    }

    // Optionnal paramters
    public function testOptionnalParams()
    {
        $ruler = new OaiPmhRuler();

        // Optional paramters
        $args = $ruler->retrieveAndCheckArguments(
            array('opt' => 1),
            array(),
            array('opt')
        );
        $this->assertEquals($args, array('opt' => 1));

        $args = $ruler->retrieveAndCheckArguments(
            array(),
            array(),
            array('opt')
        );
        $this->assertEquals($args, array());

    }

    // Exclusive paramters
    public function testExclusiveParams()
    {
        $ruler = new OaiPmhRuler();

        $args = $ruler->retrieveAndCheckArguments(
            array('exclusive' => 1),
            array(),
            array(),
            array('exclusive')
        );
        $this->assertEquals($args, array('exclusive' => 1));

        $this->setExpectedException('Naoned\OaiPmhServerBundle\Exception\BadArgumentException');
        $ruler->retrieveAndCheckArguments(
            array('exclusive' => 1, 'other' => 1),
            array(),
            array(),
            array('exclusive')
        );
    }

    // Unexpected paramters
    public function testUnexpectedParams()
    {
        $ruler = new OaiPmhRuler();

        $this->setExpectedException('Naoned\OaiPmhServerBundle\Exception\BadArgumentException');
        $ruler->retrieveAndCheckArguments(
            array('unexpected' => 1)
        );
    }

    // Check unicity of parameters
    public function testUnicityParams()
    {
        $ruler = new OaiPmhRuler();

        $this->setExpectedException('Naoned\OaiPmhServerBundle\Exception\BadArgumentException');
        $ruler->checkParamsUnicity('param1=1&parma1=2');

        $this->setExpectedException('Naoned\OaiPmhServerBundle\Exception\BadArgumentException');
        $ruler->checkParamsUnicity('param1&param1');

        $return = $ruler->checkParamsUnicity('');
        $this->assertEquals($return, null);
    }
}
