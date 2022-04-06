<?php declare(strict_types=1);

namespace Lubian\NoFramework\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface Pipeline
{
    public function dispatch(ServerRequestInterface $request): ResponseInterface;
}
