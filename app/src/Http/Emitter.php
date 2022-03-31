<?php declare(strict_types=1);

namespace Lubian\NoFramework\Http;

use Psr\Http\Message\ResponseInterface;

interface Emitter
{
    public function emit(ResponseInterface $response, bool $withoutBody = false): void;
}
