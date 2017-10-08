<?php

namespace HAProxy\Config\Tests;

use HAProxy\Config\Proxy\Backend;
use PHPUnit\Framework\TestCase;

class BackendTest extends TestCase
{
    public function testConstruct()
    {
        $backend = new Backend('test');
        $this->assertInstanceOf('HAProxy\Config\Proxy\Backend', $backend);
    }

    public function testFactory()
    {
        $backend = Backend::create('test');
        $this->assertInstanceOf('HAProxy\Config\Proxy\Backend', $backend);
    }

    public function testBind()
    {
        $this->expectException('HAProxy\Config\Exception\InvalidParameterException');
        $this->expectExceptionMessage('Bind is not allowed on a backend proxy block!');

        Backend::create('test')
            ->bind('*', 22)
        ;
    }

    public function testName()
    {
        $backend = Backend::create('www_backend');

        $this->assertEquals(
            'www_backend',
            $backend->getName()
        );
    }

    public function testAddServer()
    {
        $backend = Backend::create('www_backend')
            ->addServer('container', '127.0.0.1', 80, ['maxconn', 32])
        ;

        $this->assertTrue(
            $backend->serverExists('container')
        );

        $this->assertEquals(
            ['127.0.0.1:80', 'maxconn', 32],
            $backend->getServerDetails('container')
        );
    }

    public function testAddAclString()
    {
        $backend = Backend::create('www_backend')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
        ;

        $this->assertTrue(
            $backend->aclExists('is_host_com')
        );

        $this->assertEquals(
            ['hdr(Host) -i example.com'],
            $backend->getAclDetails('is_host_com')
        );
    }

    public function testAddAclArray()
    {
        $backend = Backend::create('www_backend')
            ->addAcl('is_host_com', ['hdr(Host)', '-i', 'example.com'])
        ;

        $this->assertTrue(
            $backend->aclExists('is_host_com')
        );

        $this->assertEquals(
            ['hdr(Host)', '-i', 'example.com'],
            $backend->getAclDetails('is_host_com')
        );
    }
}
