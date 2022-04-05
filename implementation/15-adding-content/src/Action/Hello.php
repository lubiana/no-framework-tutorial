<?php declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Lubian\NoFramework\Service\Time\Now;
use Lubian\NoFramework\Template\Renderer;
use Psr\Http\Message\ResponseInterface;

final class Hello
{
    public function __invoke(
        ResponseInterface $response,
        Now $now,
        Renderer $renderer,
        string $name = 'Stranger',
    ): ResponseInterface {
        $body = $response->getBody();
        $data = [
            'now' => $now()->format('H:i:s'),
            'name' => $name,
        ];

        $content = $renderer->render('hello', $data);

        $body->write($content);

        return $response
            ->withStatus(200)
            ->withBody($body);
    }
}
