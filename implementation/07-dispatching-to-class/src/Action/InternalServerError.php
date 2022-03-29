<?php

declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class InternalServerError implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = (new Response())->withStatus(500);
        $response->getBody()->write('Internal Server Error.');
        return $response;
    }
}
