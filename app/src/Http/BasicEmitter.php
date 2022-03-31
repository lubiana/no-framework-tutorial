<?php declare(strict_types=1);

namespace Lubian\NoFramework\Http;

use Psr\Http\Message\ResponseInterface;

use function header;
use function sprintf;
use function strtolower;

final class BasicEmitter implements Emitter
{
    public function emit(ResponseInterface $response, bool $withoutBody = false): void
    {
        foreach ($response->getHeaders() as $name => $values) {
            $first = strtolower($name) !== 'set-cookie';
            foreach ($values as $value) {
                $header = sprintf('%s: %s', $name, $value);
                header($header, $first);
                $first = false;
            }
        }

        $statusLine = sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );
        header($statusLine, true, $response->getStatusCode());

        if ($withoutBody) {
            return;
        }

        echo $response->getBody();
    }
}
