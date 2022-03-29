<?php declare(strict_types=1);

namespace Lubian\NoFramework;

use FastRoute\Dispatcher;
use Invoker\InvokerInterface;
use Laminas\Diactoros\ResponseFactory;
use Lubian\NoFramework\Exception\InternalServerError;
use Lubian\NoFramework\Exception\MethodNotAllowed;
use Lubian\NoFramework\Exception\NotFound;
use Lubian\NoFramework\Factory\FileSystemSettingsProvider;
use Lubian\NoFramework\Factory\SettingsContainerProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

use function assert;
use function error_log;
use function error_reporting;
use function FastRoute\simpleDispatcher;
use function header;
use function sprintf;
use function strtolower;

use const E_ALL;

require __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);

$settingsProvider = new FileSystemSettingsProvider(__DIR__ . '/../config/settings.php');
$container = (new SettingsContainerProvider($settingsProvider))->getContainer();

$settings = $settingsProvider->getSettings();

$whoops = new Run;
if ($settings->environment === 'dev') {
    $whoops->pushHandler(new PrettyPageHandler);
} else {
    $whoops->pushHandler(function (Throwable $e): void {
        error_log('Error: ' . $e->getMessage(), $e->getCode());
        echo 'An Error happened';
    });
}
$whoops->register();


$request = $container->get(ServerRequestInterface::class);
assert($request instanceof ServerRequestInterface);

$responseFactory = $container->get(ResponseFactory::class);
assert($responseFactory instanceof ResponseFactory);

$routeDefinitionCallback = require __DIR__ . '/../config/routes.php';
$dispatcher = simpleDispatcher($routeDefinitionCallback);

$routeInfo = $dispatcher->dispatch(
    $request->getMethod(),
    $request->getUri()->getPath(),
);

try {
    switch ($routeInfo[0]) {
        case Dispatcher::FOUND:
            $handler = $routeInfo[1];
            $vars = $routeInfo[2] ?? [];
            foreach ($vars as $attributeName => $attributeValue) {
                $request = $request->withAttribute($attributeName, $attributeValue);
            }
            $vars['request'] = $request;
            $invoker = $container->get(InvokerInterface::class);
            assert($invoker instanceof InvokerInterface);
            $response = $invoker->call($handler, $vars);
            assert($response instanceof ResponseInterface);
            break;
        case Dispatcher::METHOD_NOT_ALLOWED:
            throw new MethodNotAllowed;

        case Dispatcher::NOT_FOUND:
        default:
            throw new NotFound;
    }
} catch (MethodNotAllowed) {
    $response = $responseFactory->createResponse(405);
} catch (NotFound) {
    $response = $responseFactory->createResponse(404);
    $response->getBody()->write('Not Found');
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
