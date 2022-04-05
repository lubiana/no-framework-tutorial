<?php declare(strict_types=1);

namespace Lubian\NoFramework\Factory;

use Lubian\NoFramework\Http\ContainerPipeline;
use Lubian\NoFramework\Http\Pipeline;
use Lubian\NoFramework\Http\RoutedRequestHandler;
use Lubian\NoFramework\Settings;
use Psr\Container\ContainerInterface;

class PipelineProvider
{
    public function __construct(
        private Settings $settings,
        private RoutedRequestHandler $tip,
        private ContainerInterface $container,
    ) {
    }

    public function getPipeline(): Pipeline
    {
        $middlewares = require $this->settings->middlewaresFile;
        return new ContainerPipeline($middlewares, $this->tip, $this->container);
    }
}
