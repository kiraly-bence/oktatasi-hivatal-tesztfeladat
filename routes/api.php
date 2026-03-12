<?php

declare(strict_types=1);

use App\Controllers\Api\PointCalculatorController;
use App\Requests\PointCalculatorRequest;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();

$routes->add('calculate_points', new Route('/api/calculate-points', [
    '_controller' => [PointCalculatorController::class, 'calculate'],
    '_request' => PointCalculatorRequest::class,
], methods: ['POST']));

return $routes;