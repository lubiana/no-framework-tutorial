<?php declare(strict_types=1);

namespace Lubian\NoFramework\Factory;

use Lubian\NoFramework\Settings;

interface SettingsProvider
{
    public function getSettings(): Settings;
}
