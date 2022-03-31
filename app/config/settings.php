<?php declare(strict_types=1);

use Lubian\NoFramework\Settings;

return new Settings(
    environment: 'dev',
    dependenciesFile: __DIR__ . '/dependencies.php',
    middlewaresFile: __DIR__ . '/middlewares.php',
    templateDir: __DIR__ . '/../templates',
    templateExtension: '.html',
    pagesPath: __DIR__ . '/../data/pages/'
);
