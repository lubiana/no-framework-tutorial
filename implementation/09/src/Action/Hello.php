<?php declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Lubian\NoFramework\Service\Time\Clock;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Hello implements RequestHandlerInterface
{
    public function __construct(
        private readonly ResponseInterface $response,
        private readonly Clock $clock,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $name = $request->getAttribute('name', 'Stranger');
        $body = $this->response->getBody();

        $body->write('Hello ' . $name . '!<br />');
        $body->write('The time is: ' . $this->clock->now()->format('H:i:s'));

        return $this->response->withBody($body)
            ->withStatus(200);
    }
}
