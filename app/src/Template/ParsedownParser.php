<?php declare(strict_types=1);

namespace Lubian\NoFramework\Template;

use Parsedown;

final class ParsedownParser implements MarkdownParser
{
    public function __construct(private Parsedown $parser)
    {
    }

    public function parse(string $markdown): string
    {
        return $this->parser->parse($markdown);
    }
}
