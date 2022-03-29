<?php

declare(strict_types=1);

namespace Lubian\NoFramework;

use const E_ALL;
use FastRoute\Dispatcher;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use function call_user_func;
use function error_log;
use function error_reporting;
use function FastRoute\simpleDispatcher;
use function getenv;
use function header;
use function sprintf;

require __DIR__ . '/../vendor/autoload.php';

$environment = getenv('ENVIRONMENT') ?: 'dev';

error_reporting(E_ALL);
$whoops = new Run();
if ($environment == 'dev') {
    $whoops->pushHandler(new PrettyPageHandler());
} else {
    $whoops->pushHandler(function (Throwable $e): void {
        error_log("Error: " . $e->getMessage(), $e->getCode());
        echo 'An Error happened';
    });
}
$whoops->register();

$request = ServerRequestFactory::fromGlobals();
$response = new Response();

$routeDefinitionCallback = require __DIR__ . '/../config/routes.php';
$dispatcher = simpleDispatcher($routeDefinitionCallback);

$routeInfo = $dispatcher->dispatch(
    $request->getMethod(),
    $request->getUri()->getPath(),
);

switch ($routeInfo[0]) {
    case Dispatcher::METHOD_NOT_ALLOWED:
        $response = (new Response())->withStatus(405);
        $response->getBody()->write('Method not allowed');
        $response = $response->withStatus(405);
        break;
    case Dispatcher::FOUND:
        $handler = $routeInfo[1];
        foreach ($routeInfo[2] as $attributeName => $attributeValue) {
            $request = $request->withAttribute($attributeName, $attributeValue);
        }
        /** @var ResponseInterface $response */
        $response = call_user_func($handler, $request);
        break;
    case Dispatcher::NOT_FOUND:
    default:
        $response = (new Response())->withStatus(404);
        $response->getBody()->write('Not Found!');
        break;
}

/** @var string $name */
foreach ($response->getHeaders() as $name => $values) {
    $first = strtolower($name) !== 'set-cookie';
    foreach ($values as $value) {
        $header = sprintf('%s: %s', $name, $value);
        header($header, $first);
        $first = false;
    }
}

$statusLine = sprintf(
    'HTTP/%s %s %s',
    $response->getProtocolVersion(),
    $response->getStatusCode(),
    $response->getReasonPhrase()
);
header($statusLine, true, $response->getStatusCode());

echo $response->getBody();
