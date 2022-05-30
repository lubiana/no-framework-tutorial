<?php declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Lubian\NoFramework\Service\Time\Clock;
use Psr\Http\Message\ResponseInterface;

final class Hello
{
    public function __invoke(
        ResponseInterface $response,
        Clock $clock,
        string $name = 'Stranger'
    ): ResponseInterface {
        $body = $response->getBody();

        $body->write('Hello ' . $name . '!<br />');
        $body->write('The time is: ' . $clock->now()->format('H:i:s'));

        return $response->withBody($body)
            ->withStatus(200);
    }
}
