<?php

declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class Action implements \Psr\Http\Server\RequestHandlerInterface
{
    public function __construct(
        protected ResponseInterface $response
    ) {
    }

    abstract public function handle(ServerRequestInterface $request): ResponseInterface;
}
