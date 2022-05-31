<?php declare(strict_types=1);

namespace Lubian\NoFramework;

use FastRoute\Dispatcher;
use Invoker\InvokerInterface;
use Laminas\Diactoros\Response;
use Lubian\NoFramework\Exception\InternalServerError;
use Lubian\NoFramework\Exception\MethodNotAllowed;
use Lubian\NoFramework\Exception\NotFound;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

use function assert;
use function error_log;
use function error_reporting;
use function getenv;
use function header;
use function sprintf;
use function strtolower;

use const E_ALL;

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/settings.php';
assert($config instanceof Configuration);
$environment = $config->environment;

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

$container = require __DIR__ . '/../config/container.php';
assert($container instanceof ContainerInterface);

$request = $container->get(ServerRequestInterface::class);
assert($request instanceof ServerRequestInterface);

$dispatcher = $container->get(Dispatcher::class);
assert($dispatcher instanceof Dispatcher);

$routeInfo = $dispatcher->dispatch($request->getMethod(), $request->getUri() ->getPath(),);

try {
    switch ($routeInfo[0]) {
        case Dispatcher::FOUND:
            $routeTarget = $routeInfo[1];
            $args = $routeInfo[2];
            foreach ($routeInfo[2] as $attributeName => $attributeValue) {
                $request = $request->withAttribute($attributeName, $attributeValue);
            }
            $args['request'] = $request;
            $invoker = $container->get(InvokerInterface::class);
            assert($invoker instanceof InvokerInterface);
            $response = $invoker->call($routeTarget, $args);
            assert($response instanceof ResponseInterface);
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
