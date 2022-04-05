<?php declare(strict_types=1);

namespace Lubian\NoFramework\Model;

use Doctrine\DBAL\Types\Types;

#[Entity]
class MarkdownPage
{
    public function __construct(
        #[Id,
        Column,
        GeneratedValue]
        public int |null $id = null,
        #[Column]
        public string $title,
        #[Column(type: Types::TEXT)]
        public string $content,
    ) {
    }
}
