<?php

declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Lubian\NoFramework\Service\Time\Now;
use Psr\Http\Message\ResponseInterface;

final class Hello
{
    public function __invoke(
        ResponseInterface $response,
        Now $now,
        string $name = 'Stranger',
    ): ResponseInterface {
        $response = $response->withStatus(200);
        $response->getBody()->write('Hello ' . $name . '!');
        $nowString = $now->get()->format('H:i:s');
        $response->getBody()->write(' The Time is ' . $nowString);
        return $response;
    }
}
