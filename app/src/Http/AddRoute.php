<?php

declare(strict_types=1);

namespace Lubian\NoFramework\Http;

interface AddRoute
{
    /**
     * @param array<class-string, string>|class-string|callable $handler
     */
    public function addRoute(
        string $method,
        string $path,
        array|string|callable $handler,
    ): self;
}