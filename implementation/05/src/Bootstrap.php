<?php declare(strict_types=1);

namespace Lubian\NoFramework;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

use function error_log;
use function error_reporting;
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
$response->getBody()
    ->write('Hello World! ');
$response->getBody()
    ->write('The Uri is: ' . $request->getUri()->getPath());

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
