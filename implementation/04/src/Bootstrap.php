<?php declare(strict_types=1);

namespace Lubian\NoFramework;

use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

use function error_log;
use function error_reporting;
use function getenv;

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

echo 'Hello World!';
