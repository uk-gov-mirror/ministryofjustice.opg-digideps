<?php

use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;

ini_set('display_errors', 'on');

// If you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#configuration-and-setup for more information
//umask(0000);

$loader = $loader = require __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../app/AppKernel.php';

$kernel = file_exists(__DIR__ . '/../.enableProdMode')
    ? new AppKernel('prod', false)
    : new AppKernel('dev', true);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
