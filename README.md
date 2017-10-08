HAProxy Config Processor
========================

[![Build Status](https://travis-ci.org/malc0mn/haproxy-config-processor.svg?branch=master)](https://travis-ci.org/malc0mn/haproxy-config-processor)
[![Latest Stable Version](https://poser.pugx.org/malc0mn/haproxy-config-processor/v/stable)](https://packagist.org/packages/malc0mn/haproxy-config-processor)
[![Total Downloads](https://poser.pugx.org/malc0mn/haproxy-config-processor/downloads)](https://packagist.org/packages/malc0mn/haproxy-config-processor)
[![Latest Unstable Version](https://poser.pugx.org/malc0mn/haproxy-config-processor/v/unstable)](https://packagist.org/packages/malc0mn/haproxy-config-processor)
[![License](https://poser.pugx.org/malc0mn/haproxy-config-processor/license)](https://packagist.org/packages/malc0mn/haproxy-config-processor)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/6b5c0f4f-28db-4714-bb47-9f92ed8f7fbf/mini.png)](https://insight.sensiolabs.com/projects/24e6faf8-0baa-4bf8-a921-77b24e84faa3)


## Install using composer

Open a shell, `cd` to your poject and type:

```sh
composer require malc0mn/haproxy-config-processor dev-master
```

or edit composer.json and add:

```json
{
    "require": {
        "malc0mn/haproxy-config-processor": "~1.0"
    }
}
```

## Usage examples

###### Create from scratch

```php
require 'vendor/autoload.php';

use HAProxy\Config\Comment;
use HAProxy\Config\Proxy\Backend;
use HAProxy\Config\Proxy\Frontend;
use HAProxy\Config\Proxy\Listen;
use HAProxy\Config\Userlist;
use HAProxy\Config\Config;

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

var_export($config);
```

###### Read from file

```php
require 'vendor/autoload.php';

use HAProxy\Config\Config;

$configFromFile = Config::fromFile('/etc/haproxy/haproxy.conf');

var_export($configFromFile);
```


## Credits

The concepts used are based on the [Nginx Configuration processor](https://github.com/romanpitak/Nginx-Config-Processor) by
[romanpitak](https://github.com/romanpitak).
