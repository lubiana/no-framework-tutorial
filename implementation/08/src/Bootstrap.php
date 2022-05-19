<?php declare(strict_types=1);

namespace Lubian\NoFramework;

use FastRoute\Dispatcher;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Lubian\NoFramework\Exception\InternalServerError;
use Lubian\NoFramework\Exception\MethodNotAllowed;
use Lubian\NoFramework\Exception\NotFound;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

use function assert;
use function error_log;
use function error_reporting;
use function FastRoute\simpleDispatcher;
use function getenv;
use function header;
use function sprintf;
use function strtolower;

use const E_ALL;

require __DIR__ . '/../vendor/autoload.php';

$environment = getenv('ENVIRONMENT') ?: 'dev';

error_reporting(E_ALL);

$whoops = new Run;

if ($environment === 'dev') {
    $whoops->pushHandler(new PrettyPageHandler);
} else {
    $whoops->pushHandler(function (Throwable $t) {
        error_log('ERROR: ' . $t->getMessage(), $t->getCode());
        echo 'Oooopsie';
    });
}

$whoops->register();

$request = ServerRequestFactory::fromGlobals();
$response = new Response;


$routeDefinitionCallback = require __DIR__ . '/../config/routes.php';
$dispatcher = simpleDispatcher($routeDefinitionCallback);

$routeInfo = $dispatcher->dispatch($request->getMethod(), $request->getUri() ->getPath(),);

try {
    switch ($routeInfo[0]) {
        case Dispatcher::FOUND:
            $className = $routeInfo[1];
            $handler = new $className($response);
            assert($handler instanceof RequestHandlerInterface);
            foreach ($routeInfo[2] as $attributeName => $attributeValue) {
                $request = $request->withAttribute($attributeName, $attributeValue);
            }
            $response = $handler->handle($request);
            break;
        case Dispatcher::METHOD_NOT_ALLOWED:
            throw new MethodNotAllowed;
        case Dispatcher::NOT_FOUND:
        default:
            throw new NotFound;
    }
} catch (MethodNotAllowed) {
    $response = (new Response)->withStatus(405);
    $response->getBody()
        ->write('Method not Allowed');
} catch (NotFound) {
    $response = (new Response)->withStatus(404);
    $response->getBody()
        ->write('Not Found');
} catch (Throwable $t) {
    throw new InternalServerError($t->getMessage(), $t->getCode(), $t);
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
    $response->getReasonPhrase()
);
header($statusLine, true, $response->getStatusCode());

echo $response->getBody();
