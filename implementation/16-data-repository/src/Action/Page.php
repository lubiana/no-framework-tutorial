<?php declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Lubian\NoFramework\Model\MarkdownPage;
use Lubian\NoFramework\Repository\MarkdownPageRepo;
use Lubian\NoFramework\Template\MarkdownParser;
use Lubian\NoFramework\Template\Renderer;
use Psr\Http\Message\ResponseInterface;

use function array_map;
use function assert;
use function is_string;
use function preg_replace;
use function str_replace;

class Page
{
    public function __construct(
        private ResponseInterface $response,
        private MarkdownParser $parser,
        private Renderer $renderer,
        private MarkdownPageRepo $repo,
    ) {
    }

    public function show(
        string $page,
    ): ResponseInterface {
        $page = $this->repo->byName($page);

        // fix the next and previous buttons to work with our routing
        $content = preg_replace('/\(\d\d-/m', '(', $page->content);
        assert(is_string($content));
        $content = str_replace('.md)', ')', $content);

        $data = [
            'title' => $page->title,
            'content' => $this->parser->parse($content),
        ];

        $html = $this->renderer->render('page/show', $data);
        $this->response->getBody()->write($html);
        return $this->response;
    }

    public function list(): ResponseInterface
    {
        $pages = array_map(function (MarkdownPage $page) {
            return [
                'id' => $page->id,
                'title' => $page->content,
            ];
        }, $this->repo->all());

        $html = $this->renderer->render('page/list', ['pages' => $pages]);
        $this->response->getBody()->write($html);
        return $this->response;
    }
}
