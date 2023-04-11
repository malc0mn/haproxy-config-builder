<?php

namespace HAProxy\Config\Tests\Proxy;

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

    public function testUseBackendWithLotsOfConditions()
    {
        $frontend = Frontend::create('www_frontend')
            ->addUseBackendWithConditions('www_backend', [
                'is_host_0 is_path_admin',
                '||',
                'is_host_1 is_path_admin',
                '||',
                'is_host_2 is_path_admin',
                '||',
                'is_host_3 is_path_admin',
                '||',
                'is_host_4 is_path_admin',
                '||',
                'is_host_5 is_path_admin',
                '||',
                'is_host_6 is_path_admin',
                '||',
                'is_host_7 is_path_admin',
                '||',
                'is_host_8 is_path_admin',
                '||',
                'is_host_9 is_path_admin',
                '||',
                'is_host_10 is_path_admin',
                '||',
                'is_host_11 is_path_admin',
                '||',
                'is_host_12 is_path_admin',
                '||',
                'is_host_13 is_path_admin',
                '||',
                'is_host_14 is_path_admin',
                '||',
                'is_host_15 is_path_admin',
                '||',
                'is_host_16 is_path_admin',
                '||',
                'is_host_17 is_path_admin',
                '||',
                'is_host_18 is_path_admin',
                '||',
                'is_host_19 is_path_admin',
                '||',
                'is_host_20 is_path_admin',
                '||',
                'is_host_21 is_path_admin',
                '||',
                'is_host_22 is_path_admin',
                '||',
                'is_host_23 is_path_admin',
                '||',
                'is_host_24 is_path_admin',
                '||',
                'is_host_25 is_path_admin',
                '||',
                'is_host_26 is_path_admin',
                '||',
                'is_host_27 is_path_admin',
                '||',
                'is_host_28 is_path_admin',
                '||',
                'is_host_29 is_path_admin',
            ])
        ;

        $this->assertTrue(
            $frontend->useBackendExists('www_backend')
        );

        $this->assertEquals(
            [
                'conditions' => [
                    [
                        'is_host_0',
                        'is_path_admin',
                    ],
                    [
                        'is_host_1',
                        'is_path_admin',
                    ],
                    [
                        'is_host_2',
                        'is_path_admin',
                    ],
                    [
                        'is_host_3',
                        'is_path_admin',
                    ],
                    [
                        'is_host_4',
                        'is_path_admin',
                    ],
                    [
                        'is_host_5',
                        'is_path_admin',
                    ],
                    [
                        'is_host_6',
                        'is_path_admin',
                    ],
                    [
                        'is_host_7',
                        'is_path_admin',
                    ],
                    [
                        'is_host_8',
                        'is_path_admin',
                    ],
                    [
                        'is_host_9',
                        'is_path_admin',
                    ],
                    [
                        'is_host_10',
                        'is_path_admin',
                    ],
                    [
                        'is_host_11',
                        'is_path_admin',
                    ],
                    [
                        'is_host_12',
                        'is_path_admin',
                    ],
                    [
                        'is_host_13',
                        'is_path_admin',
                    ],
                    [
                        'is_host_14',
                        'is_path_admin',
                    ],
                    [
                        'is_host_15',
                        'is_path_admin',
                    ],
                    [
                        'is_host_16',
                        'is_path_admin',
                    ],
                    [
                        'is_host_17',
                        'is_path_admin',
                    ],
                    [
                        'is_host_18',
                        'is_path_admin',
                    ],
                    [
                        'is_host_19',
                        'is_path_admin',
                    ],
                    [
                        'is_host_20',
                        'is_path_admin',
                    ],
                    [
                        'is_host_21',
                        'is_path_admin',
                    ],
                    [
                        'is_host_22',
                        'is_path_admin',
                    ],
                    [
                        'is_host_23',
                        'is_path_admin',
                    ],
                    [
                        'is_host_24',
                        'is_path_admin',
                    ],
                    [
                        'is_host_25',
                        'is_path_admin',
                    ],
                    [
                        'is_host_26',
                        'is_path_admin',
                    ],
                    [
                        'is_host_27',
                        'is_path_admin',
                    ],
                    [
                        'is_host_28',
                        'is_path_admin',
                    ],
                    [
                        'is_host_29',
                        'is_path_admin',
                    ],
                ],
                'test' => 'if',
            ],
            $frontend->getUseBackendDetails('www_backend')
        );
    }

    public function testUseBackendWithLotsOfConditionsPrint()
    {
        $frontend = Frontend::create('www_frontend')
            ->addUseBackendWithConditions('www_backend', [
                'is_host_0 is_path_admin',
                '||',
                'is_host_1 is_path_admin',
                '||',
                'is_host_2 is_path_admin',
                '||',
                'is_host_3 is_path_admin',
                '||',
                'is_host_4 is_path_admin',
                '||',
                'is_host_5 is_path_admin',
                '||',
                'is_host_6 is_path_admin',
                '||',
                'is_host_7 is_path_admin',
                '||',
                'is_host_8 is_path_admin',
                '||',
                'is_host_9 is_path_admin',
                '||',
                'is_host_10 is_path_admin',
                '||',
                'is_host_11 is_path_admin',
                '||',
                'is_host_12 is_path_admin',
                '||',
                'is_host_13 is_path_admin',
                '||',
                'is_host_14 is_path_admin',
                '||',
                'is_host_15 is_path_admin',
                '||',
                'is_host_16 is_path_admin',
                '||',
                'is_host_17 is_path_admin',
                '||',
                'is_host_18 is_path_admin',
                '||',
                'is_host_19 is_path_admin',
                '||',
                'is_host_20 is_path_admin',
                '||',
                'is_host_21 is_path_admin',
                '||',
                'is_host_22 is_path_admin',
                '||',
                'is_host_23 is_path_admin',
                '||',
                'is_host_24 is_path_admin',
                '||',
                'is_host_25 is_path_admin',
                '||',
                'is_host_26 is_path_admin',
                '||',
                'is_host_27 is_path_admin',
                '||',
                'is_host_28 is_path_admin',
                '||',
                'is_host_29 is_path_admin',
            ])
        ;

        $this->assertTrue(
            $frontend->useBackendExists('www_backend')
        );

        $print = <<<TEXT
frontend www_frontend
use_backend www_backend if is_host_0 is_path_admin || is_host_1 is_path_admin || is_host_2 is_path_admin || is_host_3 is_path_admin || is_host_4 is_path_admin || is_host_5 is_path_admin || is_host_6 is_path_admin || is_host_7 is_path_admin || is_host_8 is_path_admin || is_host_9 is_path_admin || is_host_10 is_path_admin || is_host_11 is_path_admin || is_host_12 is_path_admin || is_host_13 is_path_admin || is_host_14 is_path_admin || is_host_15 is_path_admin || is_host_16 is_path_admin || is_host_17 is_path_admin || is_host_18 is_path_admin || is_host_19 is_path_admin
use_backend www_backend if is_host_20 is_path_admin || is_host_21 is_path_admin || is_host_22 is_path_admin || is_host_23 is_path_admin || is_host_24 is_path_admin || is_host_25 is_path_admin || is_host_26 is_path_admin || is_host_27 is_path_admin || is_host_28 is_path_admin || is_host_29 is_path_admin


TEXT;

        $this->assertEquals(
            $print,
            (string)$frontend
        );
    }

    public function testUseBackendWithoutConditions()
    {
        $frontend = Frontend::create('www_frontend')
            ->addUseBackendWithConditions('www_backend', [])
        ;

        $this->assertTrue(
            $frontend->useBackendExists('www_backend')
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

    public function testLongAcls()
    {
        $frontend = Frontend::create('www_frontend')
            ->addParameter('mode', 'http')
            ->addParameter('default_backend', 'www_backend')
            ->bind('*', 80)
            ->addAcl('is_allowed', 'req.fhdr(X-Forwarded-For) -m ip 27.61.234.15 103.86.19.87 5.104.117.229 31.161.136.5 31.161.146.143 31.161.150.83 31.161.155.118 31.161.226.240 32.201.74.24 42.106.12.236 42.106.32.15 62.45.113.167 62.45.193.31 62.133.99.212 66.249.66.198 77.161.233.214 77.165.111.92 80.56.250.110 81.28.82.167 84.80.94.178 84.84.147.64 84.245.22.130 85.149.4.88 85.149.146.149 86.93.226.218 87.233.147.218 89.200.9.239 89.200.12.3 89.200.37.111 89.200.47.250 92.64.80.156 92.64.105.130 94.213.250.27 94.231.241.27 94.231.241.125 100.67.41.44 100.83.115.134 103.249.234.61 103.250.145.176 109.235.39.233 122.170.6.132 143.176.180.63 145.131.88.217 145.131.193.114 145.133.243.88 157.32.142.93 188.120.32.251 188.206.79.19 188.206.99.167 188.206.100.73 188.207.83.246 188.207.107.36 188.207.116.154 195.74.87.202 212.45.56.7 89.99.12.20 89.99.5.58 185.146.105.54 77.251.97.137 172.94.63.4')
        ;

        $print = <<<TEXT
frontend www_frontend
mode            http
default_backend www_backend
bind            *:80
acl             is_allowed req.fhdr(X-Forwarded-For) -m ip 27.61.234.15 103.86.19.87 5.104.117.229 31.161.136.5 31.161.146.143 31.161.150.83 31.161.155.118 31.161.226.240 32.201.74.24 42.106.12.236 42.106.32.15 62.45.113.167 62.45.193.31 62.133.99.212 66.249.66.198 77.161.233.214 77.165.111.92 80.56.250.110 81.28.82.167 84.80.94.178 84.84.147.64 84.245.22.130 85.149.4.88 85.149.146.149 86.93.226.218 87.233.147.218 89.200.9.239 89.200.12.3 89.200.37.111 89.200.47.250 92.64.80.156 92.64.105.130 94.213.250.27 94.231.241.27 94.231.241.125 100.67.41.44 100.83.115.134 103.249.234.61 103.250.145.176 109.235.39.233 122.170.6.132 143.176.180.63 145.131.88.217 145.131.193.114 145.133.243.88 157.32.142.93 188.120.32.251 188.206.79.19 188.206.99.167 188.206.100.73 188.207.83.246 188.207.107.36 188.207.116.154 195.74.87.202 212.45.56.7 89.99.12.20 89.99.5.58 185.146.105.54 77.251.97.137
acl             is_allowed req.fhdr(X-Forwarded-For) -m ip 172.94.63.4


TEXT;

        $this->assertEquals(
            $print,
            (string)$frontend
        );
    }
}
