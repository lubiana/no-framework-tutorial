<?php declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Lubian\NoFramework\Model\MarkdownPage;
use Lubian\NoFramework\Repository\MarkdownPageFilesystem;
use Lubian\NoFramework\Repository\MarkdownPageRepo;
use Lubian\NoFramework\Template\Renderer;
use Parsedown;
use Psr\Http\Message\ResponseInterface;

use function preg_replace;
use function str_replace;

class Page
{
    public function __construct(
        private ResponseInterface $response,
        private MarkdownPageRepo $repo,
        private Parsedown $parsedown,
        private Renderer $renderer,
    ){}
    public function show(
        string $page,
    ): ResponseInterface {
        $page = $this->repo->byTitle($page);
        $content = $this->linkFilter($page->content);
        $content = $this->parsedown->parse($content);
        $html = $this->renderer->render('page', ['content' => $content, 'title' => $page->title]);
        $this->response->getBody()->write($html);
        return $this->response;
    }

    public function list(): ResponseInterface
    {
        $pages = array_map(
            fn (MarkdownPage $p) => ['title' => $p->title, 'id' => $p->id],
            $this->repo->all()
        );
        $html = $this->renderer->render('pagelist', ['pages' => $pages]);
        $this->response->getBody()->write($html);
        return $this->response;

    }

    private function linkFilter(string $content): string
    {
        $content = preg_replace('/\(\d\d-/m', '(', $content);
        return str_replace('.md)', ')', $content);
    }
}
