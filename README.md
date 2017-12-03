HAProxy Config Processor
========================

[![Build Status](https://travis-ci.org/malc0mn/haproxy-config-builder.svg?branch=master)](https://travis-ci.org/malc0mn/haproxy-config-builder)
[![Latest Stable Version](https://poser.pugx.org/malc0mn/haproxy-config-builder/v/stable)](https://packagist.org/packages/malc0mn/haproxy-config-builder)
[![Total Downloads](https://poser.pugx.org/malc0mn/haproxy-config-builder/downloads)](https://packagist.org/packages/malc0mn/haproxy-config-builder)
[![Latest Unstable Version](https://poser.pugx.org/malc0mn/haproxy-config-builder/v/unstable)](https://packagist.org/packages/malc0mn/haproxy-config-builder)
[![License](https://poser.pugx.org/malc0mn/haproxy-config-builder/license)](https://packagist.org/packages/malc0mn/haproxy-config-builder)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/6b5c0f4f-28db-4714-bb47-9f92ed8f7fbf/mini.png)](https://insight.sensiolabs.com/projects/6b5c0f4f-28db-4714-bb47-9f92ed8f7fbf)


## Install using composer

Open a shell, `cd` to your poject and type:

```sh
composer require malc0mn/haproxy-config-builder
```

or edit composer.json and add:

```json
{
    "require": {
        "malc0mn/haproxy-config-builder": "~1.0"
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

### Output ordering

By default, the builder output will be printed in the same order you have added
parameters.
This is not always desired, especially when working with ACLs that you want to
be present in the output before you set the use_backend calls.

To solve this issue, you can use the `setParameterOrder()` method to indicate
the desired printing order. An exemple:

```php
<?php
require 'vendor/autoload.php';

use HAProxy\Config\Proxy\Frontend;

$frontend = Frontend::create('www_frontend')
    ->setParameterOrder(['bind', 'mode', 'option', 'acl', 'use_backend', 'default_backend'])
    ->addParameter('mode', 'http')
    ->addParameter('default_backend', 'www_backend')
    ->bind('*', 80)
    ->addAcl('is_https', 'hdr(X-Forwarded-Proto) -i https')
    ->addAcl('is_host_com', 'hdr(Host) -i example.com')
    ->addUseBackend('host_com', 'if is_host_com')
    ->addParameter('option', 'forwardfor')
;

echo (string)$frontend;
/*
 frontend www_frontend
 mode http
 default_backend www_backend
 bind *:80
 acl is_https hdr(X-Forwarded-Proto) -i https
 acl is_host_com hdr(Host) -i example.com
 use_backend host_com if is_host_com
 option forwardfor
 */

$frontend->setParameterOrder(['bind', 'mode', 'option', 'acl', 'use_backend', 'default_backend']);

echo (string)$frontend;
/*
 frontend www_frontend
 bind *:80
 mode http
 option forwardfor
 acl is_https hdr(X-Forwarded-Proto) -i https
 acl is_host_com hdr(Host) -i example.com
 use_backend host_com if is_host_com
 default_backend www_backend
 */
```

### Now what?

Once you have the config, you can use the various helper methods to
programatically alter or update the config.
Or you can use those helpers to conditionally add or remove settings...

```php
require 'vendor/autoload.php';

use HAProxy\Config\Config;

$config = Config::fromFile('/etc/haproxy/haproxy.conf');

if ($config->frontendExists('www') && !$config->backendExists('www')) {
   $config->removeFrontend('www');
}

if ($config->listenExists('ssh')) {
   // Do stuff here.
}
```

Have a look at the classes to see what is at your disposal.

## Credits

The concepts used are based on the [Nginx Configuration processor](https://github.com/romanpitak/Nginx-Config-Processor) by
[romanpitak](https://github.com/romanpitak).
