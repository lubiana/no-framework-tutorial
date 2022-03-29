<?php

declare(strict_types=1);

namespace Lubian\NoFramework;

use const E_ALL;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

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
$response->getBody()->write('Hello World!');

dd($response);

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
