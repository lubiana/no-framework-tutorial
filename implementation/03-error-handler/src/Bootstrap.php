<?php declare(strict_types=1);

namespace Lubian\NoFramework;

use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

require __DIR__ . '/../vendor/autoload.php';

$environment = getenv('ENVIRONMENT') ?: 'dev';

error_reporting(E_ALL);

$whoops = new Run;
if ($environment == 'dev') {
    $whoops->pushHandler(new PrettyPageHandler);
} else {
    $whoops->pushHandler(function (\Throwable $e) {
        error_log("Error: " . $e->getMessage(), $e->getCode());
        echo 'An Error happened';
    });
}
$whoops->register();

throw new \Exception("Ooooopsie");
