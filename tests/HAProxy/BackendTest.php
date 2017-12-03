<?php

namespace HAProxy\Config\Tests;

use HAProxy\Config\Comment;
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

    public function testHasBind()
    {
        $this->expectException('HAProxy\Config\Exception\InvalidParameterException');
        $this->expectExceptionMessage('Bind is not allowed on a backend proxy block!');

        Backend::create('test')
            ->hasBind()
        ;
    }

    public function testRemoveBind()
    {
        $this->expectException('HAProxy\Config\Exception\InvalidParameterException');
        $this->expectExceptionMessage('Bind is not allowed on a backend proxy block!');

        Backend::create('test')
            ->removeBind()
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

    public function testRemoveServer()
    {
        $backend = Backend::create('www_backend')
            ->addServer('container', '127.0.0.1', 80, ['maxconn', 32])
        ;

        $this->assertTrue(
            $backend->serverExists('container')
        );

        $backend->removeServer('container');

        $this->assertFalse(
            $backend->serverExists('container')
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

    public function testRemoveAcl()
    {
        $backend = Backend::create('www_backend')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
        ;

        $this->assertTrue(
            $backend->aclExists('is_host_com')
        );

        $backend->removeAcl('is_host_com');

        $this->assertFalse(
            $backend->aclExists('is_host_com')
        );
    }

    public function testSetComment()
    {
        $backend = Backend::create('www_backend')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
        ;

        $this->assertFalse(
            $backend->hasComment()
        );

        $backend->setComment(new Comment("Hello world, I'm a comment!"));

        $this->assertTrue(
            $backend->hasComment()
        );

        $commend = $backend->getComment();

        $this->assertEquals(new Comment("Hello world, I'm a comment!"), $commend);
    }

    public function testRemoveComment()
    {
        $backend = Backend::create('www_backend')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
            ->setComment(new Comment("Hello world, I'm a comment!"))
        ;

        $this->assertTrue(
            $backend->hasComment()
        );

        $backend->removeComment();

        $this->assertFalse(
            $backend->hasComment()
        );
    }

    public function testSetParameterOrder()
    {
        $backend = Backend::create('www_backend');
        $backend->setParameterOrder(['mode', 'option', 'acl', 'server']);

        $this->assertEquals(
            ['mode' => null, 'option' => null, 'acl' => null, 'server' => null],
            $backend->getParameterOrder()
        );
    }

    public function testGetOrderedParameters()
    {
        $backend = Backend::create('www_backend');
        $backend->setParameterOrder(['mode', 'option', 'acl', 'server']);

        $backend->addParameter('option', 'forwardfor');
        $backend->addServer('localhost', '127.0.0.1', 80);
        $backend->addAcl('is_https', 'hdr(X-Forwarded-Proto) -i https');
        $backend->addParameter('mode', 'http');

        // assertEquals gave weird results allowing the test to pass while the
        // arrays were clearly QUITE different!
        $this->assertTrue(
            [
                'mode' => ['http'],
                'option forwardfor' => [],
                'acl is_https' => ['hdr(X-Forwarded-Proto) -i https'],
                'server localhost' => ['127.0.0.1:80'],
            ] === $backend->getOrderedParameters()
        );
    }

    public function testGetOrderedParametersWithGrouping()
    {
        $backend = Backend::create('www_backend');
        $backend->setParameterOrder(['mode', 'option', 'acl', 'server'], true);

        $backend->addParameter('option', 'forwardfor');
        $backend->addServer('localhost', '127.0.0.1', 80);
        $backend->addAcl('is_https', 'hdr(X-Forwarded-Proto) -i https');
        $backend->addParameter('mode', 'http');

        // assertEquals gave weird results allowing the test to pass while the
        // arrays were clearly QUITE different!
        $this->assertTrue(
            [
                'mode' => ['http'],
                '$emptyLine$0' => [],
                'option forwardfor' => [],
                '$emptyLine$1' => [],
                'acl is_https' => ['hdr(X-Forwarded-Proto) -i https'],
                '$emptyLine$2' => [],
                'server localhost' => ['127.0.0.1:80'],
            ] === $backend->getOrderedParameters()
        );
    }
}
