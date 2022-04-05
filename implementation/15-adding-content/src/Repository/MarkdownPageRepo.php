<?php declare(strict_types=1);

namespace Lubian\NoFramework\Repository;

use Lubian\NoFramework\Model\MarkdownPage;

interface MarkdownPageRepo
{
    /**
     * @return MarkdownPage[]
     */
    public function all(): array;

    public function byId(int $id): MarkdownPage;

    public function byTitle(string $title): MarkdownPage;

    public function save(MarkdownPage $page): MarkdownPage;
}
