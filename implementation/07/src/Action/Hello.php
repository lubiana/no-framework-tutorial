<?php declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Hello implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $name = $request->getAttribute('name', 'Stranger');
        $response = (new Response)->withStatus(200);
        $response->getBody()
            ->write('Hello ' . $name . '!');
        return $response;
    }
}
