<?php declare(strict_types=1);

namespace Lubian\NoFramework\Repository;

use Lubian\NoFramework\Exception\NotFound;
use Lubian\NoFramework\Model\MarkdownPage;

interface MarkdownPageRepo
{
    /** @return MarkdownPage[] */
    public function all(): array;

    /** @throws NotFound */
    public function byName(string $name): MarkdownPage;
}
