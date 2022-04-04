<?php declare(strict_types=1);

namespace Lubian\NoFramework;

final class Settings
{
    /**
     * @param array{driver: string, user: string, password: string, path: string} $connection
     * @param array{devMode: bool, metadataDirs: string[], cacheDir: string} $doctrine
     */
    public function __construct(
        public readonly string $environment,
        public readonly string $dependenciesFile,
        public readonly string $middlewaresFile,
        public readonly string $templateDir,
        public readonly string $templateExtension,
        public readonly string $pagesPath,
        /**
         * @var array{driver: string, user: string, password: string, path: string}
         */
        public readonly array $connection,
        /**
         * @var array{devMode: bool, metadataDirs: string[], cacheDir: string}
         */
        public readonly array $doctrine,
    ) {
    }

    public function isDev(): bool
    {
        return $this->environment === 'dev';
    }
}
