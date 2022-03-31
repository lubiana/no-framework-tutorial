<?php

declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class InternalServerError extends Action
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->response->getBody()->write('Internal Server Error.');
        return $this->response->withStatus(500);
    }
}