<?php declare(strict_types=1);

use Lubian\NoFramework\Settings;

return new Settings(
    environment: 'dev',
    templateDir: __DIR__ . '/../templates',
    templateExtension: '.html',
);
