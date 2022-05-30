<?php declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Lubian\NoFramework\Service\Time\Clock;
use Lubian\NoFramework\Template\Renderer;
use Psr\Http\Message\ResponseInterface;

final class Hello
{
    public function __invoke(
        ResponseInterface $response,
        Clock $clock,
        Renderer $renderer,
        string $name = 'Stranger',
    ): ResponseInterface {
        $data = [
            'name' => $name,
            'time' => $clock->now()
                ->format('H:i:s'),
        ];

        $content = $renderer->render('hello', $data,);

        $body = $response->getBody();
        $body->write($content);

        return $response->withBody($body)
            ->withStatus(200);
    }
}
