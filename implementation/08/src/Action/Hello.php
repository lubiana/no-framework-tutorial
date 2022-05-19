<?php declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Hello implements RequestHandlerInterface
{
    public function __construct(private readonly ResponseInterface $response)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $name = $request->getAttribute('name', 'Stranger');
        $body = $this->response->getBody();

        $body->write('Hello ' . $name . '!');

        return $this->response->withBody($body)
            ->withStatus(200);
    }
}
