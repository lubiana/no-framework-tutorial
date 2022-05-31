<?php declare(strict_types=1);

namespace Lubian\NoFramework;

final class Configuration
{
    public function __construct(
        public readonly string $environment = 'dev',
        public readonly string $routesFile = __DIR__ . '/../config/routes.php',
        public readonly string $templateDir = __DIR__ . '/../templates',
        public readonly string $templateExtension = '.html',
    ) {
    }
}
