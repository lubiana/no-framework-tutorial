<?php declare(strict_types=1);

namespace Lubian\NoFramework\Template;

interface MarkdownParser
{
    public function parse(string $markdown): string;
}
