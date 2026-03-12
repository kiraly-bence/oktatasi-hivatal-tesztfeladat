<?php

declare(strict_types=1);

require '../vendor/autoload.php';

use App\Exceptions\ValidationFailedException;
use App\Requests\BaseRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

$routes = require '../routes/routes.php';

$request = Request::createFromGlobals();
$context = new RequestContext();
$context->fromRequest($request);

$matcher = new UrlMatcher($routes, $context);

try {
    $parameters = $matcher->match($request->getPathInfo());
    $data = [];

    if (!empty($parameters['_request'])) {
        /** @var BaseRequest $validatableRequest */
        $validatableRequest = new $parameters['_request']($request);

        if (!$validatableRequest->isValid()) {
            throw new ValidationFailedException($validatableRequest->errors());
        }

        $data = $validatableRequest->validated();
    }

    [$class, $method] = $parameters['_controller'];
    $controller = new $class();

    /** @var Response $response */
    $response = $controller->$method($request, $data);
    $response->send();
} catch (ResourceNotFoundException $e) {
    $response = new JsonResponse(['error' => 'Not Found'], Response::HTTP_NOT_FOUND);
    $response->send();
} catch (MethodNotAllowedException $e) {
    $response = new JsonResponse(['error' => 'Method Not Allowed'], Response::HTTP_METHOD_NOT_ALLOWED);
    $response->send();
} catch (ValidationFailedException $e) {
    $response = new JsonResponse(['error' => 'Validation Failed', 'violations' => $e->getErrors()], Response::HTTP_BAD_REQUEST);
    $response->send();
} catch (\Throwable $e) {
    $response = new JsonResponse(['error' => 'Internal Server Error'], Response::HTTP_INTERNAL_SERVER_ERROR);
    $response->send();
}