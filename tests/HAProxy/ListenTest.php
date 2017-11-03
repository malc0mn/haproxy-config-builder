<?php

namespace HAProxy\Config\Tests;

use HAProxy\Config\Proxy\Listen;
use PHPUnit\Framework\TestCase;

class ListenTest extends TestCase
{
    public function testConstruct()
    {
        $listen = new Listen('test');
        $this->assertInstanceOf('HAProxy\Config\Proxy\Listen', $listen);
    }

    public function testFactory()
    {
        $listen = Listen::create('test');
        $this->assertInstanceOf('HAProxy\Config\Proxy\Listen', $listen);
    }

    public function testBind()
    {
        $listen = Listen::create('test')
            ->bind('127.0.0.1', 22)
        ;

        $this->assertTrue(
            $listen->hasBind('127.0.0.1')
        );

        $this->assertEquals(
            [':22'],
            $listen->getBindDetails('127.0.0.1')
        );
    }

    public function testRemoveBind()
    {
        $listen = Listen::create('test')
            ->bind('*', 22)
        ;

        $this->assertTrue(
            $listen->hasBind()
        );

        $listen->removeBind();

        $this->assertFalse(
            $listen->hasBind()
        );
    }

    public function testName()
    {
        $listen = Listen::create('ssh');

        $this->assertEquals(
            'ssh',
            $listen->getName()
        );
    }

    public function testAddServer()
    {
        $listen = Listen::create('ssh')
            ->addServer('container', '127.0.0.1', 22, ['maxconn', 32])
        ;

        $this->assertTrue(
            $listen->serverExists('container')
        );

        $this->assertEquals(
            ['127.0.0.1:22', 'maxconn', 32],
            $listen->getServerDetails('container')
        );
    }

    public function testRemoveServer()
    {
        $listen = Listen::create('www_listen')
            ->addServer('container', '127.0.0.1', 22, ['maxconn', 32])
        ;

        $this->assertTrue(
            $listen->serverExists('container')
        );

        $listen->removeServer('container');

        $this->assertFalse(
            $listen->serverExists('container')
        );
    }

    public function testAddAclString()
    {
        $listen = Listen::create('ssh')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
        ;

        $this->assertTrue(
            $listen->aclExists('is_host_com')
        );

        $this->assertEquals(
            ['hdr(Host) -i example.com'],
            $listen->getAclDetails('is_host_com')
        );
    }

    public function testAddAclArray()
    {
        $listen = Listen::create('ssh')
            ->addAcl('is_host_com', ['hdr(Host)', '-i', 'example.com'])
        ;

        $this->assertTrue(
            $listen->aclExists('is_host_com')
        );

        $this->assertEquals(
            ['hdr(Host)', '-i', 'example.com'],
            $listen->getAclDetails('is_host_com')
        );
    }

    public function testRemoveAcl()
    {
        $listen = Listen::create('ssh')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
        ;

        $this->assertTrue(
            $listen->aclExists('is_host_com')
        );

        $listen->removeAcl('is_host_com');

        $this->assertFalse(
            $listen->aclExists('is_host_com')
        );
    }
}
