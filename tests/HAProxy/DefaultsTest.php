<?php

namespace HAProxy\Config\Tests;

use HAProxy\Config\Proxy\Defaults;
use PHPUnit\Framework\TestCase;

class DefaultsTest extends TestCase
{
    public function testConstruct()
    {
        $backend = new Defaults();
        $this->assertInstanceOf('HAProxy\Config\Proxy\Defaults', $backend);
    }

    public function testName()
    {
        $defaults = new Defaults('test');

        $this->assertEquals(
            'test',
            $defaults->getName()
        );
    }

    public function testBind()
    {
        $this->expectException('HAProxy\Config\Exception\InvalidParameterException');
        $this->expectExceptionMessage('Bind is not allowed on a defaults proxy block!');

        $defaults = new Defaults('test');
        $defaults->bind('*', 22);
    }

    public function testHasBind()
    {
        $this->expectException('HAProxy\Config\Exception\InvalidParameterException');
        $this->expectExceptionMessage('Bind is not allowed on a defaults proxy block!');

        $defaults = new Defaults('test');
        $defaults->hasBind();
    }

    public function testRemoveBind()
    {
        $this->expectException('HAProxy\Config\Exception\InvalidParameterException');
        $this->expectExceptionMessage('Bind is not allowed on a defaults proxy block!');

        $defaults = new Defaults('test');
        $defaults->removeBind();
    }

    public function testAddAcl()
    {
        $this->expectException('HAProxy\Config\Exception\InvalidParameterException');
        $this->expectExceptionMessage('Acl is not allowed on a defaults proxy block!');

        $defaults = new Defaults('test');
        $defaults->addAcl('test', 'testing');
    }

    public function testAddServer()
    {
        $this->expectException('HAProxy\Config\Exception\InvalidParameterException');
        $this->expectExceptionMessage('Server is not allowed on a defaults proxy block!');

        $defaults = new Defaults('test');
        $defaults->addServer('test', '127.0.0.1');
    }

    public function testAddParameterString()
    {
        $defaults = new Defaults();
        $defaults->addParameter('mode', 'http');

        $this->assertTrue(
            $defaults->parameterExists('mode')
        );
        $this->assertEquals(
            ['http'],
            $defaults->getParameter('mode')
        );
    }

    public function testAddParameterArray()
    {
        $defaults = new Defaults();
        $defaults->addParameter('mode', ['http']);

        $this->assertTrue(
            $defaults->parameterExists('mode')
        );
        $this->assertEquals(
            ['http'],
            $defaults->getParameter('mode')
        );
    }

    public function testAddTimeoutParameterArray()
    {
        $defaults = new Defaults();
        $defaults->addParameter('timeout', ['connect', '5000ms']);

        $this->assertFalse(
            $defaults->parameterExists('timeout')
        );
        $this->assertEquals(
            ['5000ms'],
            $defaults->getParameter('timeout connect')
        );
    }

    public function testRemoveParameter()
    {
        $defaults = new Defaults();
        $defaults->addParameter('mode', 'http');

        $this->assertTrue(
            $defaults->parameterExists('mode')
        );

        $defaults->removeParameter('mode');

        $this->assertFalse(
            $defaults->parameterExists('mode')
        );
    }

    public function testSetPrintPriority()
    {
        $this->expectException('HAProxy\Config\Exception\InvalidParameterException');
        $this->expectExceptionMessage('SetPrintPriority is not allowed on a defaults proxy block!');

        $defaults = new Defaults();
        $defaults->setPrintPriority(2);
    }
}
