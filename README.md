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

$config = Config::create()
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

echo (string)$config;
```

###### Read from file

```php
require 'vendor/autoload.php';

use HAProxy\Config\Config;

$configFromFile = Config::fromFile('/etc/haproxy/haproxy.conf');

var_export($configFromFile);
```

### Output ordering

#### Keywords ordering within a proxy block

By default, the builder output will be printed in the same order you have added
parameters.
This is not always desired, especially when working with ACLs that you want to
be present in the output before you set the `use_backend` calls.

To solve this issue, you can use the `setParameterOrder()` method to indicate
the desired printing order. An example:

```php
<?php
require 'vendor/autoload.php';

use HAProxy\Config\Proxy\Frontend;

$frontend = Frontend::create('www_frontend')
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
 mode            http
 default_backend www_backend
 bind            *:80
 acl             is_https hdr(X-Forwarded-Proto) -i https
 acl             is_host_com hdr(Host) -i example.com
 use_backend     host_com if is_host_com
 option          forwardfor
 */

$frontend->setParameterOrder(['bind', 'mode', 'option', 'acl', 'use_backend', 'default_backend']);

echo (string)$frontend;
/*
 frontend www_frontend
 bind            *:80
 mode            http
 option          forwardfor
 acl             is_https hdr(X-Forwarded-Proto) -i https
 acl             is_host_com hdr(Host) -i example.com
 use_backend     host_com if is_host_com
 default_backend www_backend
 */

// Whitespace control:
$frontend->setParameterOrder([
    'bind' => false,
    'mode' => false,
    'option' => true, // Add trailing whitespace!
    'acl' => true, // Add trailing whitespace!
    'use_backend' => true, // Add trailing whitespace!
    'default_backend',
]);

echo (string)$frontend;
/*
 frontend www_frontend
 bind            *:80
 mode            http
 option          forwardfor

 acl             is_https hdr(X-Forwarded-Proto) -i https
 acl             is_host_com hdr(Host) -i example.com

 use_backend     host_com if is_host_com

 default_backend www_backend
 */
```

#### Ordering of proxy blocks in the config file

The proxy blocks will be rendered according to their given priority with some
limitations:
1. `global` will **always** be rendered **first** (1st).
2. `defaults` will **always** be rendered **second** (2nd).
3. `resolvers` will **always** be rendered **second** (3rd).
4. `userlist` will **always** be rendered **third** (4th).
5. Attempting to set a print priority on `defaults` will throw an exception.

You can thus only control the print priority of `backend`, `frontend` and
`listen` proxy blocks.
The default priority is set to *1000*. You can change the priority by calling
the setPrintPriority() method on the desired proxy block: **a smaller integer
means a higher priority**!

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

$config = Config::create()
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
    ->addBackend(
        Backend::create('servers')
            ->addServer('server1', '127.0.0.1', 8000, ['maxconn', 32])
            ->setPrintPriority(1002)
    )
    ->addListen(
        Listen::create('ssh')
            ->addServer('ssh-host', '*', 22, 'maxconn 3')
    )
    ->addFrontend(
        Frontend::create('http-in')
            ->bind('*', 80)
            ->addParameter('default_backend', 'servers')
            ->addAcl('login_page', ['url_beg', '/login'])
            ->setPrintPriority(1001)
    )
;

echo (string)$config;
/*
 # Simple configuration for an HTTP proxy listening on port 80 on all
 # interfaces and forwarding requests to a single backend "servers" with a
 # single server "server1" listening on 127.0.0.1:8000
 global
     maxconn 256
     debug
     daemon

 defaults
     mode    http
     timeout connect 5000ms
     timeout client 50000ms
     timeout server 50000ms

 userlist developers
     group editor

     user eddy password $6$mlskxjmqlkcnmlcjsmdl groups editor,admin

 listen ssh
     server ssh-host *:22 maxconn 3

 frontend http-in
     bind            *:80
     default_backend servers
     acl             login_page url_beg /login

 backend servers
     server server1 127.0.0.1:8000 maxconn 32
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

Have a look at the classes to see what is at your disposal. A peek at the tests
will give you a very good idea of what you can do with all available methods.

### More complex stuff

If you need more complex rules that need multiple `use_backend` calls to the
**same** backend, then you can use *tagged backends*.

Consider this HAProxy frontend proxy block:

```
frontend www_frontend
    bind *:8080
    mode http

    http-request set-header X-Original-Path %[path]

    acl is_host_website hdr(host) -i website.example.com
    acl is_host_api hdr(host) -i api.example.com

    acl is_path_admin hdr_beg(X-Original-Path) -i /admin
    acl is_path_api hdr_beg(X-Original-Path) -i /api

    # Path mappings MUST come first AND in separate use_backend statements.
    use_backend website if is_host_website is_path_admin
    use_backend api if is_host_webste is_path_api

    # Regular host mappings.
    use_backend website if is_host_website
    use_backend api if is_host_api
```

This example uses path mappings to direct traffic to a different backend. It
should be obvious that you **cannot** write this to achieve the same: 

```
frontend www_frontend
    bind *:8080
    mode http

    http-request set-header X-Original-Path %[path]

    acl is_host_website hdr(host) -i website.example.com
    acl is_host_api hdr(host) -i api.example.com

    acl is_path_admin hdr_beg(X-Original-Path) -i /admin
    acl is_path_api hdr_beg(X-Original-Path) -i /api

    use_backend website if is_host_website is_path_admin || is_host_website
    use_backend api if is_host_webste is_path_api || is_host_api
```

The above example will **never** redirect you to the API backend when calling
the `website.example.com/api` url because the *first* `use_backend` statement
will:
1. see that the host **is** `website.example.com` **and** the path **is not**
`/admin` so it will go on to the next condition
2. see that the host **is** `website.example.com` and redirect you to the
`website` backend.

To recreate the first, correct, example using this library, you can use *tagged
backends*:

```php
$frontend = Frontend::create('www_frontend')
    ->bind('*', 8080)
    ->addParameter('mode', 'http')

    ->addParameter('http-request', 'set-header X-Original-Path %[path]')

    ->addAcl('is_host_website', 'hdr(host) -i website.example.com')
    ->addAcl('is_host_api', 'hdr(host) -i api.example.com')

    ->addAcl('is_path_admin', 'hdr_beg(X-Original-Path) -i /admin')
    ->addAcl('is_path_api', 'hdr_beg(X-Original-Path) -i /api')

    // Here come the 'tagged' backends.
    ->addUseBackendWithConditions(
        'website',
        ['is_host_website', 'is_path_admin'],
        'if', // This is the condition, 'if' is the default.
        'path_acl' // This is the tag.
    )
    ->addUseBackendWithConditions(
        'api',
        ['is_host_website', 'is_path_api'],
        'if', // This is the condition, 'if' is the default.
        'path_acl' // This is the tag.
    )

    // The 'regular' backends.
    ->addUseBackendWithConditions('website', ['is_host_website'])
    ->addUseBackendWithConditions('api', ['is_host_api'])
;

echo (string)$frontend;
/*
frontend www_frontend
bind         *:8080
mode         http
http-request set-header X-Original-Path %[path]
acl          is_host_website hdr(host) -i website.example.com
acl          is_host_api hdr(host) -i api.example.com
acl          is_path_admin hdr_beg(X-Original-Path) -i /admin
acl          is_path_api hdr_beg(X-Original-Path) -i /api
use_backend  website if is_host_website is_path_admin
use_backend  api if is_host_website is_path_api
use_backend  website if is_host_website
use_backend  api if is_host_api
*/
```

## Credits

The concepts used are based on the [Nginx Configuration processor](https://github.com/romanpitak/Nginx-Config-Processor) by
[romanpitak](https://github.com/romanpitak).
