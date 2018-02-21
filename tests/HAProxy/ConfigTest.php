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
        $this->config->addGlobal('test', ['hello', 'world']);

        $this->assertTrue(
            $this->config->getGlobal()->parameterExists('test')
        );
        $this->assertEquals(
            ['hello', 'world'],
            $this->config->getGlobal()->getParameter('test')
        );
    }

    public function testAddDefault()
    {
        $this->config->addDefaults('test', ['hello', 'world']);

        $this->assertTrue(
            $this->config->getDefaults()->parameterExists('test')
        );
        $this->assertEquals(
            ['hello', 'world'],
            $this->config->getDefaults()->getParameter('test')
        );
    }

    public function testAddUserlist()
    {
        $this->config->addUserlist(new Userlist('test'));

        $this->assertTrue(
            $this->config->userlistExists('test')
        );
        $this->assertInstanceOf(
            'HAProxy\Config\Userlist',
            $this->config->getUserlist('test')
        );
    }

    public function testAddListen()
    {
        $this->config->addListen(new Listen('test'));

        $this->assertTrue(
            $this->config ->listenExists('test')
        );
        $this->assertInstanceOf(
            'HAProxy\Config\Proxy\Listen',
            $this->config->getListen('test')
        );
    }

    public function testRemoveListen()
    {
        $this->config->addListen(new Listen('test'));

        $this->assertInstanceOf(
            'HAProxy\Config\Proxy\Listen',
            $this->config->getListen('test')
        );

        $this->config->removeListen('test');

        $this->assertFalse(
            $this->config->listenExists('test')
        );
    }

    public function testAddFrontend()
    {
        $this->config->addFrontend(new Frontend('test'));

        $this->assertTrue(
            $this->config->frontendExists('test')
        );
        $this->assertInstanceOf(
            'HAProxy\Config\Proxy\Frontend',
            $this->config->getFrontend('test')
        );
    }

    public function testRemoveFrontend()
    {
        $this->config->addFrontend(new Frontend('test'));

        $this->assertInstanceOf(
            'HAProxy\Config\Proxy\Frontend',
            $this->config->getFrontend('test')
        );

        $this->config->removeFrontend('test');

        $this->assertFalse(
            $this->config->frontendExists('test')
        );
    }

    public function testAddBackend()
    {
        $this->config->addBackend(new Backend('test'));

        $this->assertTrue(
            $this->config->backendExists('test')
        );
        $this->assertInstanceOf(
            'HAProxy\Config\Proxy\Backend',
            $this->config->getBackend('test')
        );
    }

    public function testRemoveBackend()
    {
        $this->config->addBackend(new Backend('test'));

        $this->assertInstanceOf(
            'HAProxy\Config\Proxy\Backend',
            $this->config->getBackend('test')
        );

        $this->config->removeBackend('test');

        $this->assertFalse(
            $this->config->backendExists('test')
        );
    }

    public function testCreate()
    {
        $comment = <<<TEXT
Simple configuration for an HTTP proxy listening on port 80 on all
interfaces and forwarding requests to a single backend "servers" with a
single server "server1" listening on 127.0.0.1:8000
TEXT;

        $config = (string) $this->config
            ->addComment(
                new Comment($comment)
            )
            ->setDebug()
            ->setDaemon()
            ->setQUiet()
            ->addGlobal('maxconn', 256)
            ->addGlobal('stats', ['socket', '/var/run/haproxy.stats', 'user', 'haproxy', 'group', 'haproxy', 'mode', '700', 'level', 'operator'])
            ->addDefaults('mode', 'http')
            ->addDefaults('timeout', ['connect', '5000ms'])
            ->addDefaults('timeout', ['client', '50000ms'])
            ->addDefaults('timeout', ['server', '50000ms'])
            ->addUserlist(
                Userlist::create('developers')
                    ->addUser('eddy', '$6$mlskxjmqlkcnmlcjsmdl', ['editor', 'admin'])
                    ->addGroup('editor', [])
            )
            ->addUserlist(
                Userlist::create('masters')
                    ->addUser('jules', '$6$mlskxjmqlkcnmlcjsmdl')
            )
            ->addFrontend(
                Frontend::create('http-in')
                    ->bind('*', 80)
                    ->bind('::', 80)
                    ->addParameter('option', 'httpclose')
                    ->addParameter('option', 'httplog')
                    ->addParameter('reqidel', '^X-Forwarded-For:.*')
                    ->addAcl('login_page', ['url_beg', '/login'])
                    ->addParameter('default_backend', 'servers')
            )
            ->addFrontend(
                Frontend::create('https')
                    ->bind('', 443, 'ssl crt /etc/ssl/cert1.pem crt /etc/ssl/cert2.pem crt /etc/ssl/cert3.pem crt /etc/ssl/cert4.pem crt /etc/ssl/cert5.pem crt /etc/ssl/cert6.pem crt /etc/ssl/cert7.pem crt /etc/ssl/cert8.pem crt /etc/ssl/cert9.pem crt /etc/ssl/tep.pem no-sslv3')
                    ->bind('::', 443, 'ssl crt /etc/ssl/cert1.pem crt /etc/ssl/cert2.pem crt /etc/ssl/cert3.pem crt /etc/ssl/cert4.pem crt /etc/ssl/cert5.pem crt /etc/ssl/cert6.pem crt /etc/ssl/cert7.pem crt /etc/ssl/cert8.pem crt /etc/ssl/cert9.pem crt /etc/ssl/tep.pem no-sslv3')
                    ->addParameter('mode', 'http')
            )
            ->addBackend(
                Backend::create('servers')
                    ->addParameter('http-request', ['set-header', 'X-Forwarded-Port', '%[dst_port]'])
                    ->addParameter('http-request', ['set-header', 'X-Forwarded-Proto', 'https', 'if { ssl_fc }'])
                    ->addServer('server1', '127.0.0.1', 8000, ['maxconn', 32])
            )
            ->addListen(
                Listen::create('ssh')
                    ->addServer('ssh-host', '*', 22, 'maxconn 3')
            )
            ->addListen(
                Listen::create('redis')
                    ->bind('127.0.0.1', 6379)
                    ->addParameter('mode', 'tcp')
                    ->addParameter('option', 'tcp-check')
                    ->addParameter('tcp-check', 'send PING\r\n')
                    ->addParameter('tcp-check', 'expect rstring (\+PONG|\-NOAUTH)')
                    ->addParameter('tcp-check', 'send QUIT\r\n')
                    ->addParameter('tcp-check', 'expect string +OK')
                    ->addServer('remote-redis', '192.168.0.15', 6379, 'check fall 2 inter 1000ms')
            )
        ;

        $this->assertEquals(@file_get_contents('tests/haproxy.conf'), $config);
    }

    public function testCreateFromFile()
    {
        $this->config
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
            ->setQuiet()
            ->addGlobal('maxconn', 256)
            ->addGlobal('stats', ['socket', '/var/run/haproxy.stats', 'user', 'haproxy', 'group', 'haproxy', 'mode', '700', 'level', 'operator'])
            ->addDefaults('mode', 'http')
            ->addDefaults('timeout', ['connect', '5000ms'])
            ->addDefaults('timeout', ['client', '50000ms'])
            ->addDefaults('timeout', ['server', '50000ms'])
            ->addUserlist(
                Userlist::create('developers')
                    ->addUser('eddy', '$6$mlskxjmqlkcnmlcjsmdl', ['editor', 'admin'])
                    ->addGroup('editor', [])
            )
            ->addUserlist(
                Userlist::create('masters')
                    ->addUser('jules', '$6$mlskxjmqlkcnmlcjsmdl')
            )
            ->addFrontend(
                Frontend::create('http-in')
                    ->bind('*', 80)
                    ->bind('::', 80)
                    ->addParameter('option', 'httpclose')
                    ->addParameter('option', 'httplog')
                    ->addParameter('reqidel', '^X-Forwarded-For:.*')
                    ->addAcl('login_page', ['url_beg', '/login'])
                    ->addParameter('default_backend', 'servers')
            )
            ->addFrontend(
                Frontend::create('https')
                    ->bind('', 443, 'ssl crt /etc/ssl/cert1.pem crt /etc/ssl/cert2.pem crt /etc/ssl/cert3.pem crt /etc/ssl/cert4.pem crt /etc/ssl/cert5.pem crt /etc/ssl/cert6.pem crt /etc/ssl/cert7.pem crt /etc/ssl/cert8.pem crt /etc/ssl/cert9.pem crt /etc/ssl/tep.pem no-sslv3')
                    ->bind('::', 443, 'ssl crt /etc/ssl/cert1.pem crt /etc/ssl/cert2.pem crt /etc/ssl/cert3.pem crt /etc/ssl/cert4.pem crt /etc/ssl/cert5.pem crt /etc/ssl/cert6.pem crt /etc/ssl/cert7.pem crt /etc/ssl/cert8.pem crt /etc/ssl/cert9.pem crt /etc/ssl/tep.pem no-sslv3')
                    ->addParameter('mode', 'http')
            )
            ->addBackend(
                Backend::create('servers')
                    ->addParameter('http-request', ['set-header', 'X-Forwarded-Port', '%[dst_port]'])
                    ->addParameter('http-request', ['set-header', 'X-Forwarded-Proto', 'https', 'if', '{', 'ssl_fc', '}'])
                    ->addServer('server1', '127.0.0.1', 8000, ['maxconn', 32])
            )
            ->addListen(
                Listen::create('ssh')
                    ->addServer('ssh-host', '*', 22, ['maxconn', 3])
            )
            ->addListen(
                Listen::create('redis')
                    ->bind('127.0.0.1', 6379)
                    ->addParameter('mode', 'tcp')
                    ->addParameter('option', 'tcp-check')
                    ->addParameter('tcp-check', 'send PING\r\n')
                    ->addParameter('tcp-check', 'expect rstring (\+PONG|\-NOAUTH)')
                    ->addParameter('tcp-check', 'send QUIT\r\n')
                    ->addParameter('tcp-check', 'expect string +OK')
                    ->addServer('remote-redis', '192.168.0.15', 6379, 'check fall 2 inter 1000ms')
            )
        ;

        $configFromFile = Config::fromFile('tests/haproxy.conf');

        $this->assertEquals($this->config, $configFromFile);
    }

    public function testSaveToFile()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('tmp'));

        $this->config->saveToFile(vfsStream::url('tmp') . '/haproxy.conf');

        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('haproxy.conf'));
    }

    public function testSaveToFileFail()
    {
        $this->expectException('\HAProxy\Config\Exception\FileException');
        $this->expectExceptionMessage('Cannot open file "this/path/does/not/exist.conf" for writing.');

        $this->config->saveToFile('this/path/does/not/exist.conf');
    }

    public function testPrintPriority()
    {
        $this->config
            ->setDebug()
            ->setDaemon()
            ->setQuiet()
            ->addGlobal('maxconn', 256)
            ->addGlobal('stats', ['socket', '/var/run/haproxy.stats', 'user', 'haproxy', 'group', 'haproxy', 'mode', '700', 'level', 'operator'])
            ->addDefaults('mode', 'http')
            ->addDefaults('timeout', ['connect', '5000ms'])
            ->addDefaults('timeout', ['client', '50000ms'])
            ->addDefaults('timeout', ['server', '50000ms'])
            ->addBackend(
                Backend::create('servers')
                    ->addParameter('http-request', ['set-header', 'X-Forwarded-Port', '%[dst_port]'])
                    ->addParameter('http-request', ['set-header', 'X-Forwarded-Proto', 'https', 'if', '{', 'ssl_fc', '}'])
                    ->addServer('server1', '127.0.0.1', 8000, ['maxconn', 32])
                    ->setPrintPriority(1002)
            )
            ->addUserlist(
                Userlist::create('developers')
                    ->addUser('eddy', '$6$mlskxjmqlkcnmlcjsmdl', ['editor', 'admin'])
                    ->addGroup('editor', [])
            )
            ->addFrontend(
                Frontend::create('http-in')
                    ->bind('*', 80)
                    ->bind('::', 80)
                    ->addParameter('option', 'httpclose')
                    ->addParameter('option', 'httplog')
                    ->addAcl('login_page', ['url_beg', '/login'])
                    ->addParameter('default_backend', 'servers')
                    ->setPrintPriority(1001)
            )
            ->addListen(
                Listen::create('ssh')
                    ->addServer('ssh-host', '*', 22, ['maxconn', 3])
            )
        ;

        $this->assertEquals(
            (string)$this->config,
            file_get_contents('tests/haproxy-priority.conf')
        );
    }
}
