<?php declare(strict_types=1);

namespace Lubian\NoFramework;

use Lubian\NoFramework\Factory\FileSystemSettingsProvider;
use Lubian\NoFramework\Factory\SettingsContainerProvider;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

use function assert;
use function error_log;
use function error_reporting;

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
        error_log('Error: ' . $e->getMessage(), (int) $e->getCode());
        echo 'An Error happened';
    });
}
$whoops->register();

$app = $container->get(Kernel::class);
assert($app instanceof Kernel);

$app->run();
