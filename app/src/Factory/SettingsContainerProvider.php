<?php declare(strict_types=1);

namespace Lubian\NoFramework\Factory;

use DI\ContainerBuilder;
use Lubian\NoFramework\Settings;
use Psr\Container\ContainerInterface;

final class SettingsContainerProvider implements ContainerProvider
{
    public function __construct(
        private SettingsProvider $settingsProvider,
    ) {
    }

    public function getContainer(): ContainerInterface
    {
        $builder = new ContainerBuilder;
        $settings = $this->settingsProvider->getSettings();
        $dependencies = require $settings->dependenciesFile;
        $dependencies[Settings::class] = $settings;
        $builder->addDefinitions($dependencies);
        // $builder->enableCompilation('/tmp');
        return $builder->build();
    }
}
