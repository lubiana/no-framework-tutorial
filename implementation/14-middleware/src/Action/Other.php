<?php declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Psr\Http\Message\ResponseInterface;

final class Other
{
    public function someFunctionName(ResponseInterface $response): ResponseInterface
    {
        $body = $response->getBody();

        $body->write('This works too!');

        return $response
            ->withStatus(200)
            ->withBody($body);
    }
}
