<?php declare(strict_types=1);

use Lubian\NoFramework\Settings;

return new Settings(
    environment: 'prod',
    dependenciesFile: __DIR__ . '/dependencies.php',
    middlewaresFile: __DIR__ . '/middlewares.php',
    templateDir: __DIR__ . '/../templates',
    templateExtension: '.html',
    pagesPath: __DIR__ . '/../data/pages/',
    connection: [
        'driver' => 'pdo_sqlite',
        'user' => '',
        'password' => '',
        'path' => __DIR__ . '/../data/db.sqlite',
    ],
    doctrine: [
        'devMode' => true,
        'metadataDirs' => [__DIR__ . '/../src/Model/'],
        'cacheDir' => __DIR__ . '/../data/cache/',
    ],
);
