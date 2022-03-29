<?php declare(strict_types=1);

namespace Lubian\NoFramework\Factory;

use Lubian\NoFramework\Settings;

final class FileSystemSettingsProvider implements SettingsProvider
{
    public function __construct(
        private string $filePath
    ) {
    }

    public function getSettings(): Settings
    {
        return require $this->filePath;
    }
}
