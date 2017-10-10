<?php

namespace HAProxy\Config\Tests;

use HAProxy\Config\Proxy\Frontend;
use PHPUnit\Framework\TestCase;

class FrontendTest extends TestCase
{
    public function testConstruct()
    {
        $frontend = new Frontend('test');
        $this->assertInstanceOf('HAProxy\Config\Proxy\Frontend', $frontend);
    }

    public function testFactory()
    {
        $frontend = Frontend::create('test');
        $this->assertInstanceOf('HAProxy\Config\Proxy\Frontend', $frontend);
    }

    public function testBind()
    {
        $frontend = Frontend::create('test')
            ->bind('*', 22)
        ;

        $this->assertTrue(
            $frontend->hasBind()
        );
    }

    public function testRemoveBind()
    {
        $frontend = Frontend::create('test')
            ->bind('*', 22)
        ;

        $this->assertTrue(
            $frontend->hasBind()
        );

        $frontend->removeBind();

        $this->assertFalse(
            $frontend->hasBind()
        );
    }

    public function testName()
    {
        $frontend = Frontend::create('www_frontend');

        $this->assertEquals(
            'www_frontend',
            $frontend->getName()
        );
    }

    public function testAddServer()
    {
        $this->expectException('HAProxy\Config\Exception\InvalidParameterException');
        $this->expectExceptionMessage('Server is not allowed on a frontend proxy block!');

        Frontend::create('www_frontend')
            ->addServer('container', '127.0.0.1', 80, ['maxconn', 32])
        ;
    }

    public function testRemoveServer()
    {
        $this->expectException('HAProxy\Config\Exception\InvalidParameterException');
        $this->expectExceptionMessage('Server is not allowed on a frontend proxy block!');

        Frontend::create('www_frontend')
            ->removeServer('container')
        ;
    }

    public function testAddAclString()
    {
        $frontend = Frontend::create('www_frontend')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
        ;

        $this->assertTrue(
            $frontend->aclExists('is_host_com')
        );

        $this->assertEquals(
            ['hdr(Host) -i example.com'],
            $frontend->getAclDetails('is_host_com')
        );
    }

    public function testAddAclArray()
    {
        $frontend = Frontend::create('www_frontend')
            ->addAcl('is_host_com', ['hdr(Host)', '-i', 'example.com'])
        ;

        $this->assertTrue(
            $frontend->aclExists('is_host_com')
        );

        $this->assertEquals(
            ['hdr(Host)', '-i', 'example.com'],
            $frontend->getAclDetails('is_host_com')
        );
    }

    public function testRemoveAcl()
    {
        $frontend = Frontend::create('www_frontend')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
        ;

        $this->assertTrue(
            $frontend->aclExists('is_host_com')
        );

        $frontend->removeAcl('is_host_com');

        $this->assertFalse(
            $frontend->aclExists('is_host_com')
        );
    }
}
