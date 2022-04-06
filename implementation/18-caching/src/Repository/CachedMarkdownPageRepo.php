<?php declare(strict_types=1);

namespace Lubian\NoFramework\Repository;

use Lubian\NoFramework\Model\MarkdownPage;
use Lubian\NoFramework\Service\Cache\EasyCache;

use function assert;
use function base64_encode;
use function is_array;

final class CachedMarkdownPageRepo implements MarkdownPageRepo
{
    public function __construct(
        private EasyCache $cache,
        private MarkdownPageRepo $repo,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        $key = base64_encode(self::class . 'all');
        $result = $this->cache->get(
            $key,
            fn () => $this->repo->all(),
            300
        );
        assert(is_array($result));
        foreach ($result as $page) {
            assert($page instanceof MarkdownPage);
        }
        return $result;
    }

    public function byName(string $name): MarkdownPage
    {
        $key = base64_encode(self::class . 'byName' . $name);
        $result = $this->cache->get(
            $key,
            fn () => $this->repo->byName($name),
            300
        );
        assert($result instanceof MarkdownPage);
        return $result;
    }
}
