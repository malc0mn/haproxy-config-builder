<?php

namespace HAProxy\Config\Tests;

use HAProxy\Config\Comment;
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

    public function testCountServers()
    {
        $this->expectException('HAProxy\Config\Exception\InvalidParameterException');
        $this->expectExceptionMessage('Server is not allowed on a frontend proxy block!');

        Frontend::create('www_frontend')
            ->countServers()
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
            ['hdr(Host)', '-i', 'example.com'],
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

    public function testSetComment()
    {
        $frontend = Frontend::create('www_frontend')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
        ;

        $this->assertFalse(
            $frontend->hasComment()
        );

        $frontend->setComment(new Comment("Hello world, I'm a comment!"));

        $this->assertTrue(
            $frontend->hasComment()
        );

        $commend = $frontend->getComment();

        $this->assertEquals(new Comment("Hello world, I'm a comment!"), $commend);
    }

    public function testRemoveComment()
    {
        $frontend = Frontend::create('www_frontend')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
            ->setComment(new Comment("Hello world, I'm a comment!"))
        ;

        $this->assertTrue(
            $frontend->hasComment()
        );

        $frontend->removeComment();

        $this->assertFalse(
            $frontend->hasComment()
        );
    }

    public function testAddUseBackend()
    {
        $frontend = Frontend::create('www_frontend')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
            ->addAcl('is_https', 'hdr(X-Forwarded-Proto) -i https')
            ->addUseBackend('https_backend')
            ->addUseBackend('www_backend')
        ;

        $this->assertTrue(
            $frontend->useBackendExists('www_backend')
        );

        $this->assertTrue(
            $frontend->useBackendExists('https_backend')
        );
    }

    public function testAddUseBackendWithTag()
    {
        $frontend = Frontend::create('www_frontend')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
            ->addAcl('is_https', 'hdr(X-Forwarded-Proto) -i https')
            ->addUseBackend('backend', 'www')
            ->addUseBackend('backend', 'https')
        ;

        $this->assertTrue(
            $frontend->useBackendExists('backend', 'www')
        );

        $this->assertTrue(
            $frontend->useBackendExists('backend', 'https')
        );
    }

    public function testAddUseBackendWithTagPrinted()
    {
        $frontend = Frontend::create('www_frontend')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
            ->addAcl('is_https', 'hdr(X-Forwarded-Proto) -i https')
            ->addUseBackend('backend', 'www')
            ->addUseBackend('backend', 'https')
        ;

        $this->assertTrue(
            $frontend->useBackendExists('backend', 'www')
        );

        $this->assertTrue(
            $frontend->useBackendExists('backend', 'https')
        );

        $print = <<<TEXT
frontend www_frontend
acl         is_host_com hdr(Host) -i example.com
acl         is_https hdr(X-Forwarded-Proto) -i https
use_backend backend
use_backend backend


TEXT;

        $this->assertEquals(
            $print,
            (string)$frontend
        );
    }

    public function testAddUseBackendWithConditions()
    {
        $frontend = Frontend::create('www_frontend')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
            ->addAcl('is_https', 'hdr(X-Forwarded-Proto) -i https')
            ->addUseBackendWithConditions('https_backend', ['is_https'])
            ->addUseBackendWithConditions('www_backend', ['is_host_com'])
        ;

        $this->assertTrue(
            $frontend->useBackendExists('www_backend')
        );

        $this->assertTrue(
            $frontend->useBackendExists('https_backend')
        );

        $this->assertEquals(
            ['conditions' => [['is_host_com']], 'test' => 'if'],
            $frontend->getUseBackendDetails('www_backend')
        );

        $this->assertEquals(
            ['conditions' => [['is_https']], 'test' => 'if'],
            $frontend->getUseBackendDetails('https_backend')
        );
    }

    public function testAddUseBackendWithConditionsPrinted()
    {
        $frontend = Frontend::create('www_frontend')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
            ->addAcl('is_https', 'hdr(X-Forwarded-Proto) -i https')
            ->addUseBackendWithConditions('backend', ['is_https'], 'if', 'https')
            ->addUseBackendWithConditions('backend', ['is_host_com'], 'if', 'www')
        ;

        $this->assertTrue(
            $frontend->useBackendExists('backend', 'www')
        );

        $this->assertTrue(
            $frontend->useBackendExists('backend', 'https')
        );

        $this->assertEquals(
            ['conditions' => [['is_host_com']], 'test' => 'if'],
            $frontend->getUseBackendDetails('backend', 'www')
        );

        $this->assertEquals(
            ['conditions' => [['is_https']], 'test' => 'if'],
            $frontend->getUseBackendDetails('backend', 'https')
        );

        $print = <<<TEXT
frontend www_frontend
acl         is_host_com hdr(Host) -i example.com
acl         is_https hdr(X-Forwarded-Proto) -i https
use_backend backend if is_https
use_backend backend if is_host_com


TEXT;

        $this->assertEquals(
            $print,
            (string)$frontend
        );
    }

    public function testAddUseBackendWithConditionsAndPrioPrinted()
    {
        $frontend = Frontend::create('www_frontend')
            ->setParameterOrder(['acl', 'use_backend'])
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
            ->addAcl('is_https', 'hdr(X-Forwarded-Proto) -i https')
            ->addUseBackendWithConditions('backend', ['is_https'], 'if', 'https', 2)
            ->addUseBackendWithConditions('backend', ['is_host_com'], 'if', 'www', 1)
        ;

        $this->assertTrue(
            $frontend->useBackendExists('backend', 'www')
        );

        $this->assertTrue(
            $frontend->useBackendExists('backend', 'https')
        );

        $this->assertEquals(
            ['conditions' => [['is_host_com']], 'test' => 'if'],
            $frontend->getUseBackendDetails('backend', 'www')
        );

        $this->assertEquals(
            ['conditions' => [['is_https']], 'test' => 'if'],
            $frontend->getUseBackendDetails('backend', 'https')
        );

        $print = <<<TEXT
frontend www_frontend
acl         is_host_com hdr(Host) -i example.com
acl         is_https hdr(X-Forwarded-Proto) -i https
use_backend backend if is_host_com
use_backend backend if is_https


TEXT;

        $this->assertEquals(
            $print,
            (string)$frontend
        );
    }

    public function testAddUseBackendWithDoubleConditions()
    {
        $frontend = Frontend::create('www_frontend')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
            ->addAcl('is_https', 'hdr(X-Forwarded-Proto) -i https')
            ->addUseBackendWithConditions('https_backend', ['is_https'])
            ->addUseBackendWithConditions('www_backend', ['is_host_com'])
        ;

        $this->assertTrue(
            $frontend->useBackendExists('www_backend')
        );

        $this->assertTrue(
            $frontend->useBackendExists('https_backend')
        );

        $this->assertEquals(
            ['conditions' => [['is_host_com']], 'test' => 'if'],
            $frontend->getUseBackendDetails('www_backend')
        );

        $this->assertEquals(
            ['conditions' => [['is_https']], 'test' => 'if'],
            $frontend->getUseBackendDetails('https_backend')
        );

        $frontend->addUseBackendWithConditions('https_backend', ['is_https']);

        $this->assertEquals(
            ['conditions' => [['is_https']], 'test' => 'if'],
            $frontend->getUseBackendDetails('https_backend')
        );

    }

    public function testRemoveUseBackend()
    {
        $frontend = Frontend::create('www_frontend')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
            ->addUseBackend('www_backend')
        ;

        $this->assertTrue(
            $frontend->useBackendExists('www_backend')
        );

        $frontend->removeUseBackend('www_backend');

        $this->assertFalse(
            $frontend->useBackendExists('www_backend')
        );
    }

    public function testSetParameterOrder()
    {
        $frontend = Frontend::create('www_frontend')
            ->setParameterOrder(['bind', 'mode', 'option', 'acl', 'use_backend', 'default_backend'])
        ;

        $this->assertEquals(
            ['bind' => null, 'mode' => null, 'option' => null, 'acl' => null, 'use_backend' => null, 'default_backend' =>  null],
            $frontend->getParameterOrder()
        );
    }

    public function testGetOrderedParameters()
    {
        $frontend = Frontend::create('www_frontend')
            ->setParameterOrder(['bind', 'mode', 'option', 'reqidel', 'acl', 'use_backend', 'default_backend'])
            ->addParameter('mode', 'http')
            ->addParameter('default_backend', 'www_backend')
            ->bind('*', 80)
            ->addAcl('is_https', 'hdr(X-Forwarded-Proto) -i https')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
            ->addUseBackend('host_com')
            ->addParameter('option', 'forwardfor')
        ;

        // assertEquals gave weird results allowing the test to pass while the
        // arrays were clearly QUITE different!
        $this->assertTrue(
            [
                'bind *' => [':80'],
                'mode' => ['http'],
                'option forwardfor' => [],
                'acl is_https' => ['hdr(X-Forwarded-Proto)', '-i', 'https'],
                'acl is_host_com' => ['hdr(Host)', '-i', 'example.com'],
                'use_backend host_com' => [],
                'default_backend' => ['www_backend'],
            ] === $frontend->getOrderedParameters()
        );
    }

    public function testGetOrderedParametersWithGrouping()
    {
        $frontend = Frontend::create('www_frontend')
            ->setParameterOrder([
                'bind' => false,
                'mode' => false,
                'option' => true,
                'reqidel' => false,
                'acl' => true,
                'use_backend' => true,
                'default_backend' => false,
            ])
            ->addParameter('mode', 'http')
            ->addParameter('default_backend', 'www_backend')
            ->bind('*', 80)
            ->addAcl('is_https', 'hdr(X-Forwarded-Proto) -i https')
            ->addAcl('is_host_com', 'hdr(Host) -i example.com')
            ->addUseBackend('host_com')
            ->addParameter('option', 'forwardfor')
        ;

        // assertEquals gave weird results allowing the test to pass while the
        // arrays were clearly QUITE different!
        $this->assertTrue(
            [
                'bind *' => [':80'],
                'mode' => ['http'],
                'option forwardfor' => [],
                '$emptyLine$0' => [],
                'acl is_https' => ['hdr(X-Forwarded-Proto)', '-i', 'https'],
                'acl is_host_com' => ['hdr(Host)', '-i', 'example.com'],
                '$emptyLine$1' => [],
                'use_backend host_com' => [],
                '$emptyLine$2' => [],
                'default_backend' => ['www_backend'],
            ] === $frontend->getOrderedParameters()
        );
    }

    public function testSetPrintPriority()
    {
        $frontend = Frontend::create('www_frontend')
            ->setPrintPriority(2)
        ;

        $this->assertEquals(2, $frontend->getPrintPriority());
    }

    public function testGetLongestKeywordSize()
    {
        $frontend = Frontend::create('www_frontend')
            ->addParameter('mode', 'http')
            ->addParameter('default_backend', 'www_backend')
            ->bind('*', 80)
            ->addAcl('is_https', 'hdr(X-Forwarded-Proto) -i https')
            ->addAcl('is_host_example_com', 'hdr(Host) -i example.com')
            ->addUseBackend('host_com', 'if is_host_com')
            ->addParameter('option', 'forwardfor')
        ;

        // default_backend is the longest key!
        $this->assertEquals(15, $frontend->getLongestKeywordSize());
    }
}
