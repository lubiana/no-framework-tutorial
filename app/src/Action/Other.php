<?php declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Lubian\NoFramework\Template\MarkdownParser;
use Psr\Http\Message\ResponseInterface;

final class Other
{
    public function someFunctionName(ResponseInterface $response, MarkdownParser $parser): ResponseInterface
    {
        $html = $parser->parse('This *works* **too!**');
        $response->getBody()->write($html);
        return $response->withStatus(200);
    }
}
