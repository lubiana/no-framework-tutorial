<?php declare(strict_types=1);

namespace Lubian\NoFramework\Model;

class MarkdownPage
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $content,
    ) {
    }
}
