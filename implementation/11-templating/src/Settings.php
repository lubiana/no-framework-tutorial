<?php declare(strict_types=1);

namespace Lubian\NoFramework;

final class Settings
{
    public function __construct(
        public readonly string $environment,
        public readonly string $templateDir,
        public readonly string $templateExtension,
    ) {
    }
}
