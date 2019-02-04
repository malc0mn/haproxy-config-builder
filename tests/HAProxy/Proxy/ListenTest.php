<?php

namespace HAProxy\Config\Tests\Proxy;

use HAProxy\Config\Comment;
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

    public function testCountServers()
    {
        $listen = Listen::create('www_listen')
            ->addServer('container1', '127.0.0.1', 22, ['maxconn', 32])
        ;

        $this->assertEquals(1, $listen->countServers());

        $listen->addServer('container2', '127.0.0.1', 222, ['maxconn', 32]);

        $this->assertEquals(2, $listen->countServers());
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
            ['hdr(Host)', '-i', 'example.com'],
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

    public function testSetComment()
    {
        $listen = Listen::create('ssh')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
        ;

        $this->assertFalse(
            $listen->hasComment()
        );

        $listen->setComment(new Comment("Hello world, I'm a comment!"));

        $this->assertTrue(
            $listen->hasComment()
        );

        $commend = $listen->getComment();

        $this->assertEquals(new Comment("Hello world, I'm a comment!"), $commend);
    }

    public function testRemoveComment()
    {
        $listen = Listen::create('ssh')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
            ->setComment(new Comment("Hello world, I'm a comment!"))
        ;

        $this->assertTrue(
            $listen->hasComment()
        );

        $listen->removeComment();

        $this->assertFalse(
            $listen->hasComment()
        );
    }
}
