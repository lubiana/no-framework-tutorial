<?php

declare(strict_types=1);

namespace Lubian\NoFramework;

use const E_ALL;
use Exception;
use FastRoute\Dispatcher;
use Invoker\InvokerInterface;
use Lubian\NoFramework\Exception\InternalServerError;
use Lubian\NoFramework\Exception\MethodNotAllowed;
use Lubian\NoFramework\Exception\NotFound;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use function error_log;
use function error_reporting;
use function FastRoute\simpleDispatcher;
use function getenv;
use function header;
use function sprintf;
use function strtolower;

require __DIR__ . '/../vendor/autoload.php';

$environment = getenv('ENVIRONMENT') ?: 'dev';

error_reporting(E_ALL);

$whoops = new Run();

if ($environment === 'dev') {
    $whoops->pushHandler(new PrettyPageHandler());
} else {
    $whoops->pushHandler(function (Throwable $e): void {
        error_log("ERROR: " . $e->getMessage(), $e->getCode());
        echo 'AN ERROR HAPPENED!!!';
    });
}

$whoops->register();

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../config/dependencies.php';

/** @var InvokerInterface $invoker */
$invoker = $container->get(InvokerInterface::class);

/** @var ServerRequestInterface $request */
$request = $container->get(ServerRequestInterface::class);

/** @var ResponseInterface $response */
$response = $container->get(ResponseInterface::class);

$routeDefinitionCallback = require __DIR__ . '/../config/routes.php';
$dispatcher = simpleDispatcher($routeDefinitionCallback);

$routeInfo = $dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());

try {
    switch ($routeInfo[0]) {
        case Dispatcher::FOUND:
            $handler = $routeInfo[1];
            $args = $routeInfo[2];
            foreach ($routeInfo[2] as $attributeName => $attributeValue) {
                $request = $request->withAttribute($attributeName, $attributeValue);
            }
            $args['request'] = $request;
            $response = $container->call($handler, $args);
            break;
        case Dispatcher::METHOD_NOT_ALLOWED:
            throw new MethodNotAllowed();
        case Dispatcher::NOT_FOUND:
        default:
            throw new NotFound();
    }
} catch (NotFound) {
    $response = $response->withStatus(404);
    $response->getBody()->write('Not Found');
} catch (MethodNotAllowed) {
    $response = $response->withStatus(405);
    $response->getBody()->write('Method not Allowed');
} catch (Exception $e) {
    throw new InternalServerError($e->getMessage(), $e->getCode(), $e);
}



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
    $response->getReasonPhrase(),
);
header($statusLine, true, $response->getStatusCode());

echo $response->getBody();
