<?php declare(strict_types=1);

namespace Lubian\NoFramework;

use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

require __DIR__ . '/../vendor/autoload.php';

$environment = getenv('ENVIRONMENT') ?: 'dev';

error_reporting(E_ALL);

$whoops = new Run();

if ($environment === 'dev') {
    $whoops->pushHandler(new PrettyPageHandler());
} else {
    $whoops->pushHandler(function (\Throwable $t) {
        error_log('ERROR: ' . $t->getMessage(), $t->getCode());
        echo 'Oooopsie';
    });
}

$whoops->register();

echo 'Hello World!';
