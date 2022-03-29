<?php

declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Psr\Http\Message\ResponseInterface;

final class Other
{
    public function __construct(private ResponseInterface $response)
    {
    }
    public function someFunctionName(): ResponseInterface
    {
        $response = $this->response->withStatus(200);
        $response->getBody()->write('This works too');
        return $response;
    }
}
