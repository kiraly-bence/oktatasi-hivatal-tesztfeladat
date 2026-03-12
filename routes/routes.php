<?php

declare(strict_types=1);

use Symfony\Component\Routing\RouteCollection;

$api = require __DIR__ . '/api.php';
$web = require __DIR__ . '/web.php';

$routes = new RouteCollection();
$routes->addCollection($api);
$routes->addCollection($web);

return $routes;