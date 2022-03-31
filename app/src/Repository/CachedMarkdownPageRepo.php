<?php declare(strict_types=1);

namespace Lubian\NoFramework\Repository;

use Lubian\NoFramework\Model\MarkdownPage;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedMarkdownPageRepo implements MarkdownPageRepo
{
    public function __construct(
        private CacheInterface $cache,
        private MarkdownPageRepo $repo,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        $callback = fn () => $this->repo->all();
        return $this->cache->get('ALLPAGES', function (ItemInterface $item) use ($callback) {
            $item->expiresAfter(30);
            return $callback();
        });
    }

    public function byId(int $id): MarkdownPage
    {
        $callback = fn () => $this->repo->byId($id);
        return $this->cache->get('PAGE' . $id, function (ItemInterface $item) use ($callback) {
            $item->expiresAfter(30);
            return $callback();
        });
    }

    public function byTitle(string $title): MarkdownPage
    {
        $callback = fn () => $this->repo->byTitle($title);
        return $this->cache->get('PAGE' . $title, function (ItemInterface $item) use ($callback) {
            $item->expiresAfter(30);
            return $callback();
        });
    }
}
