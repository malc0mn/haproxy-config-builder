<?php

namespace HAProxy\Config\Tests;

use HAProxy\Config\Comment;
use HAProxy\Config\Proxy\Backend;
use HAProxy\Config\Proxy\Frontend;
use HAProxy\Config\Proxy\Listen;
use HAProxy\Config\Userlist;
use HAProxy\Config\Config;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    protected $config;

    protected function setUp()
    {
        $this->config = new Config();
    }

    public function testConstruct()
    {
        $config = new Config();
        $this->assertInstanceOf('HAProxy\Config\Config', $config);
    }

    public function testFactory()
    {
        $config = Config::create();
        $this->assertInstanceOf('HAProxy\Config\Config', $config);
    }

    public function testDebugOn()
    {
        $this->config->setDebug();

        $this->assertTrue(
            $this->config->getGlobal()->isDebug()
        );
    }

    public function testDebugOff()
    {
        $this->config->setDebug(false);

        $this->assertFalse(
            $this->config->getGlobal()->isDebug()
        );
    }

    public function testQuietOn()
    {
        $this->config->setQuiet();

        $this->assertTrue(
            $this->config->getGlobal()->isQuiet()
        );
    }

    public function testQuietOff()
    {
        $this->config->setDebug(false);

        $this->assertFalse(
            $this->config->getGlobal()->isQuiet()
        );
    }

    public function testDaemonOn()
    {
        $this->config->setDaemon();

        $this->assertTrue(
            $this->config->getGlobal()->isDaemon()
        );
    }

    public function testDaemonOff()
    {
        $this->config->setDaemon(false);

        $this->assertFalse(
            $this->config->getGlobal()->isDaemon()
        );
    }

    public function testAddGlobal()
    {
        $config = Config::create()
            ->addGlobal('test', ['hello', 'world'])
        ;

        $this->assertTrue(
            $config->getGlobal()->parameterExists('test')
        );
        $this->assertEquals(
            ['hello', 'world'],
            $config->getGlobal()->getParameter('test')
        );
    }

    public function testAddDefault()
    {
        $config = Config::create()
            ->addDefaults('test', ['hello', 'world'])
        ;

        $this->assertTrue(
            $config->getDefaults()->parameterExists('test')
        );
        $this->assertEquals(
            ['hello', 'world'],
            $config->getDefaults()->getParameter('test')
        );
    }

    public function testAddUserlist()
    {
        $config = Config::create()
            ->addUserlist(new Userlist('test'))
        ;

        $this->assertTrue(
            $config->userlistExists('test')
        );
        $this->assertInstanceOf(
            'HAProxy\Config\Userlist',
            $config->getUserlist('test')
        );
    }

    public function testAddListen()
    {
        $config = Config::create()
            ->addListen(new Listen('test'))
        ;

        $this->assertTrue(
            $config->listenExists('test')
        );
        $this->assertInstanceOf(
            'HAProxy\Config\Proxy\Listen',
            $config->getListen('test')
        );
    }

    public function testRemoveListen()
    {
        $config = Config::create()
            ->addListen(new Listen('test'))
        ;

        $this->assertInstanceOf(
            'HAProxy\Config\Proxy\Listen',
            $config->getListen('test')
        );

        $config->removeListen('test');

        $this->assertFalse(
            $config->listenExists('test')
        );
    }

    public function testAddFrontend()
    {
        $config = Config::create()
            ->addFrontend(new Frontend('test'))
        ;

        $this->assertTrue(
            $config->frontendExists('test')
        );
        $this->assertInstanceOf(
            'HAProxy\Config\Proxy\Frontend',
            $config->getFrontend('test')
        );
    }

    public function testRemoveFrontend()
    {
        $config = Config::create()
            ->addFrontend(new Frontend('test'))
        ;

        $this->assertInstanceOf(
            'HAProxy\Config\Proxy\Frontend',
            $config->getFrontend('test')
        );

        $config->removeFrontend('test');

        $this->assertFalse(
            $config->frontendExists('test')
        );
    }

    public function testAddBackend()
    {
        $config = Config::create()
            ->addBackend(new Backend('test'))
        ;

        $this->assertTrue(
            $config->backendExists('test')
        );
        $this->assertInstanceOf(
            'HAProxy\Config\Proxy\Backend',
            $config->getBackend('test')
        );
    }

    public function testRemoveBackend()
    {
        $config = Config::create()
            ->addBackend(new Backend('test'))
        ;

        $this->assertInstanceOf(
            'HAProxy\Config\Proxy\Backend',
            $config->getBackend('test')
        );

        $config->removeBackend('test');

        $this->assertFalse(
            $config->backendExists('test')
        );
    }

    public function testCreate()
    {
        $comment = <<<TEXT
Simple configuration for an HTTP proxy listening on port 80 on all
interfaces and forwarding requests to a single backend "servers" with a
single server "server1" listening on 127.0.0.1:8000
TEXT;

        $config = (string) Config::create()
            ->addComment(
                new Comment($comment)
            )
            ->setDebug()
            ->setDaemon()
            ->addGlobal('maxconn', 256)
            ->addDefaults('mode', 'http')
            ->addDefaults('timeout', ['connect', '5000ms'])
            ->addDefaults('timeout', ['client', '50000ms'])
            ->addDefaults('timeout', ['server', '50000ms'])
            ->addUserlist(
                Userlist::create('developers')
                    ->addUser('eddy', '$6$mlskxjmqlkcnmlcjsmdl', ['editor', 'admin'])
                    ->addGroup('editor', [])
            )
            ->addFrontend(
                Frontend::create('http-in')
                    ->bind('*', 80)
                    ->addParameter('default_backend', 'servers')
                    ->addAcl('login_page', ['url_beg', '/login'])
            )
            ->addBackend(
                Backend::create('servers')
                    ->addServer('server1', '127.0.0.1', 8000, ['maxconn', 32])
            )
            ->addListen(
                Listen::create('ssh')
                    ->addServer('ssh-host', '*', 22, 'maxconn 3')
            )
        ;

        $this->assertEquals(@file_get_contents('tests/haproxy.conf'), $config);
    }

    public function testCreateFromFile()
    {
        $config = Config::create()
            ->addComment(
                new Comment('Simple configuration for an HTTP proxy listening on port 80 on all')
            )
            ->addComment(
                new Comment('interfaces and forwarding requests to a single backend "servers" with a')
            )
            ->addComment(
                new Comment('single server "server1" listening on 127.0.0.1:8000')
            )
            ->setDebug()
            ->setDaemon()
            ->addGlobal('maxconn', 256)
            ->addDefaults('mode', 'http')
            ->addDefaults('timeout', ['connect', '5000ms'])
            ->addDefaults('timeout', ['client', '50000ms'])
            ->addDefaults('timeout', ['server', '50000ms'])
            ->addUserlist(
                Userlist::create('developers')
                    ->addUser('eddy', '$6$mlskxjmqlkcnmlcjsmdl', ['editor', 'admin'])
                    ->addGroup('editor', [])
            )
            ->addFrontend(
                Frontend::create('http-in')
                    ->bind('*', 80)
                    ->addParameter('default_backend', 'servers')
                    ->addAcl('login_page', ['url_beg', '/login'])
            )
            ->addBackend(
                Backend::create('servers')
                    ->addServer('server1', '127.0.0.1', 8000, ['maxconn', 32])
            )
            ->addListen(
                Listen::create('ssh')
                    ->addServer('ssh-host', '*', 22, ['maxconn', 3])
            )
        ;

        $configFromFile = Config::fromFile('tests/haproxy.conf');

        $this->assertEquals($config, $configFromFile);
    }

    public function testSaveToFile()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('tmp'));

        $config = new Config();
        $config->saveToFile(vfsStream::url('tmp') . '/haproxy.conf');

        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('haproxy.conf'));
    }

    public function testSaveToFileFail()
    {
        $this->expectException('\HAProxy\Config\Exception\FileException');
        $this->expectExceptionMessage('Cannot open file "this/path/does/not/exist.conf" for writing.');

        $config = new Config();
        $config->saveToFile('this/path/does/not/exist.conf');
    }
}
