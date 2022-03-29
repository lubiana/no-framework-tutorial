<?php declare(strict_types=1);

namespace Lubian\NoFramework\Factory;

use Psr\Container\ContainerInterface;

interface ContainerProvider
{
    public function getContainer(): ContainerInterface;
}
