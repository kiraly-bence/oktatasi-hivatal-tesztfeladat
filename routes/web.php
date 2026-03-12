<?php

declare(strict_types=1);

use App\Controllers\AppController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();

$routes->add('app', new Route('/{path}', [
    '_controller' => [AppController::class, 'index'],
], requirements: [
    'path' => '.*',
], methods: ['GET']));

return $routes;