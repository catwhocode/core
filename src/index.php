<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Zikula_Request_Http as Request;

require 'lib/bootstrap.php';

$request = Request::createFromGlobals();

try{
    $core->init(Zikula_Core::STAGE_ALL, $request);
} catch (Doctrine\DBAL\Exception\DriverException $ex) {
    die('Database error: ' . $ex->getMessage());
}

// this event for BC only. remove in 2.0.0
$core->getDispatcher()->dispatch('frontcontroller.predispatch', new \Zikula\Core\Event\GenericEvent());

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
