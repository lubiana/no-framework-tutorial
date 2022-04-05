<?php declare(strict_types=1);

namespace Lubian\NoFramework\Template;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

final class GithubMarkdownRenderer implements MarkdownRenderer
{
    private MarkdownConverter $engine;

    public function __construct(
        CommonMarkCoreExtension $commonMarkCoreExtension,
        GithubFlavoredMarkdownExtension $githubFlavoredMarkdownExtension,
    ) {
        $environment = new Environment([]);
        $environment->addExtension($commonMarkCoreExtension);
        $environment->addExtension($githubFlavoredMarkdownExtension);
        $this->engine = new MarkdownConverter($environment);
    }

    public function render(string $markdown): string
    {
        return (string) $this->engine->convert($markdown);
    }
}
