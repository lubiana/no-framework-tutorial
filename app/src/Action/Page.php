<?php declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Lubian\NoFramework\Exception\InternalServerError;
use Lubian\NoFramework\Template\MarkdownParser;
use Lubian\NoFramework\Template\Renderer;
use Psr\Http\Message\ResponseInterface;

use function array_filter;
use function array_map;
use function array_values;
use function file_get_contents;
use function glob;
use function preg_replace;
use function str_contains;
use function str_replace;
use function substr;

class Page
{
    public function __construct(
        private ResponseInterface $response,
        private MarkdownParser $parser,
        private Renderer $renderer,
        private string $pagesPath = __DIR__ . '/../../data/pages/'
    ) {
    }

    public function show(
        string $page,
    ): ResponseInterface {
        $page = array_values(
            array_filter(
                $this->getPages(),
                fn (string $filename) => str_contains($filename, $page)
            )
        )[0];
        $markdown = file_get_contents($page);

        // fix the next and previous buttons to work with our routing
        $markdown = preg_replace('/\(\d\d-/m', '(', $markdown);
        $markdown = str_replace('.md)', ')', $markdown);

        $page = str_replace([$this->pagesPath, '.md'], ['', ''], $page);
        $data = [
            'title' => substr($page, 3),
            'content' => $this->parser->parse($markdown),
        ];
        $html = $this->renderer->render('page/show', $data);
        $this->response->getBody()->write($html);
        return $this->response;
    }

    public function list(): ResponseInterface
    {
        $pages = array_map(function (string $page) {
            $page = str_replace([$this->pagesPath, '.md'], ['', ''], $page);
            return [
                'id' => substr($page, 0, 2),
                'title' => substr($page, 3),
            ];
        }, $this->getPages());
        $html = $this->renderer->render('page/list', ['pages' => $pages]);
        $this->response->getBody()->write($html);
        return $this->response;
    }

    /**
     * @return string[]
     */
    private function getPages(): array
    {
        $files = glob($this->pagesPath . '*.md');
        if ($files === false) {
            throw new InternalServerError('cannot read pages');
        }
        return $files;
    }
}
