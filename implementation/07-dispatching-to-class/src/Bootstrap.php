<?php

declare(strict_types=1);

namespace Lubian\NoFramework;

use const E_ALL;
use Exception;
use FastRoute\Dispatcher;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Lubian\NoFramework\Action\InternalServerError;
use Lubian\NoFramework\Action\NotAllowed;
use Lubian\NoFramework\Action\NotFound;
use Lubian\NoFramework\Exception\InternalServerErrorException;
use Lubian\NoFramework\Exception\NotAllowedException;
use Lubian\NoFramework\Exception\NotFoundException;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
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

try {
    switch ($routeInfo[0]) {
        case Dispatcher::METHOD_NOT_ALLOWED:
            throw new NotAllowedException();
        case Dispatcher::FOUND:
            /** @var RequestHandlerInterface $handler */
            $handler = new $routeInfo[1][0]();
            $method = $routeInfo[1][1];
            if (!$handler instanceof RequestHandlerInterface) {
                throw new InternalServerErrorException();
            }
            foreach ($routeInfo[2] as $attributeName => $attributeValue) {
                $request = $request->withAttribute($attributeName, $attributeValue);
            }
            $response = $handler->$method($request);
            break;
        case Dispatcher::NOT_FOUND:
        default:
            throw new NotFoundException();
    }
} catch (NotAllowedException) {
    $response = (new NotAllowed())->handle($request);
} catch (NotFoundException) {
    $response = (new NotFound())->handle($request);
} catch (Exception) {
    $response = (new InternalServerError())->handle($request);
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
