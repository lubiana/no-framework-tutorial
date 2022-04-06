<?php declare(strict_types=1);

namespace Lubian\NoFramework\Factory;

use Lubian\NoFramework\Settings;

use function assert;

final class FileSystemSettingsProvider implements SettingsProvider
{
    public function __construct(
        private string $filePath
    ) {
    }

    public function getSettings(): Settings
    {
        $settings = require $this->filePath;
        assert($settings instanceof Settings);
        return $settings;
    }
}
