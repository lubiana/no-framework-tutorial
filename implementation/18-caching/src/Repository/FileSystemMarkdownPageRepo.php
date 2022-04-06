<?php declare(strict_types=1);

namespace Lubian\NoFramework\Repository;

use Lubian\NoFramework\Exception\InternalServerError;
use Lubian\NoFramework\Exception\NotFound;
use Lubian\NoFramework\Model\MarkdownPage;

use function array_filter;
use function array_map;
use function array_values;
use function count;
use function file_get_contents;
use function glob;
use function str_replace;
use function substr;

final class FileSystemMarkdownPageRepo implements MarkdownPageRepo
{
    public function __construct(
        private readonly string $dataPath
    ) {
    }

    /** @inheritDoc  */
    public function all(): array
    {
        $files = glob($this->dataPath . '*.md');
        if ($files === false) {
            throw new InternalServerError('cannot read pages');
        }
        return array_map(function (string $filename) {
            $content = file_get_contents($filename);
            if ($content === false) {
                throw new InternalServerError('cannot read pages');
            }
            $idAndTitle = str_replace([$this->dataPath, '.md'], ['', ''], $filename);
            return new MarkdownPage(
                (int) substr($idAndTitle, 0, 2),
                substr($idAndTitle, 3),
                $content
            );
        }, $files);
    }

    public function byName(string $name): MarkdownPage
    {
        $pages = array_values(
            array_filter(
                $this->all(),
                fn (MarkdownPage $p) => $p->title === $name,
            )
        );

        if (count($pages) !== 1) {
            throw new NotFound;
        }

        return $pages[0];
    }
}
