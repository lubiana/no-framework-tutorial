<?php declare(strict_types=1);

namespace Lubian\NoFramework\Model;

class MarkdownPage
{
    public function __construct(
        public int |null $id = null,
        public string $title,
        public string $content,
    ) {
    }
}
