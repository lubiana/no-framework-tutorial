<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Lubian\NoFramework\Factory\FileSystemSettingsProvider;
use Lubian\NoFramework\Factory\SettingsContainerProvider;

$settingsProvider = new FileSystemSettingsProvider(__DIR__ . '/config/settings.php');
$container = (new SettingsContainerProvider($settingsProvider))->getContainer();

return ConsoleRunner::createHelperSet($container->get(EntityManagerInterface::class));
