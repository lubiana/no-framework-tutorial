<?php declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Other implements RequestHandlerInterface
{
    public function __construct(private readonly ResponseInterface $response)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->response->getBody();

        $body->write('This works too!');

        return $this->response->withBody($body)
            ->withStatus(200);
    }
}
