<?php declare(strict_types=1);

namespace Lubian\NoFramework\Template;

interface Renderer
{
    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data): string;
}
