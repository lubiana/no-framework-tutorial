<?php declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Lubian\NoFramework\Repository\MarkdownPageRepo;
use Lubian\NoFramework\Template\Renderer;
use Parsedown;
use Psr\Http\Message\ResponseInterface;

use function preg_replace;
use function str_replace;

class Page
{
    public function __invoke(
        string $page,
        ResponseInterface $response,
        MarkdownPageRepo $repo,
        Parsedown $parsedown,
        Renderer $renderer,
    ): ResponseInterface {
        $page = $repo->byTitle($page);
        $content = $this->linkFilter($page->content);
        $content = $parsedown->parse($content);
        $html = $renderer->render('page', ['content' => $content]);
        $response->getBody()->write($html);
        return $response;
    }

    private function linkFilter(string $content): string
    {
        $content = preg_replace('/\(\d\d-/m', '(', $content);
        return str_replace('.md)', ')', $content);
    }
}
