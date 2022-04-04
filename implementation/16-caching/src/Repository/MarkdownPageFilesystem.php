<?php declare(strict_types=1);

namespace Lubian\NoFramework\Repository;

use Lubian\NoFramework\Exception\NotFound;
use Lubian\NoFramework\Model\MarkdownPage;

use function array_filter;
use function array_map;
use function array_values;
use function assert;
use function count;
use function file_get_contents;
use function glob;
use function is_array;
use function str_replace;
use function substr;
use function usleep;

final class MarkdownPageFilesystem implements MarkdownPageRepo
{
    public function __construct(private readonly string $dataPath)
    {
    }

    /**
     * @return MarkdownPage[]
     */
    public function all(): array
    {
        $fileNames = glob($this->dataPath . '*.md');
        assert(is_array($fileNames));
        return array_map(function (string $name): MarkdownPage {
            usleep(100000);
            $content = file_get_contents($name);
            $name = str_replace($this->dataPath, '', $name);
            $name = str_replace('.md', '', $name);
            $id = (int) substr($name, 0, 2);
            $title = substr($name, 3);
            return new MarkdownPage($id, $title, $content);
        }, $fileNames);
    }

    public function byId(int $id): MarkdownPage
    {
        $callback = fn (MarkdownPage $p): bool => $p->id === $id;
        $filtered = array_values(array_filter($this->all(), $callback));
        if (count($filtered) === 0) {
            throw new NotFound;
        }
        return $filtered[0];
    }

    public function byTitle(string $title): MarkdownPage
    {
        $callback = fn (MarkdownPage $p): bool => $p->title === $title;
        $filtered = array_values(array_filter($this->all(), $callback));
        if (count($filtered) === 0) {
            throw new NotFound;
        }
        return $filtered[0];
    }
}
