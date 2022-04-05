<?php declare(strict_types=1);

namespace Lubian\NoFramework\Repository;

use Lubian\NoFramework\Model\MarkdownPage;
use Lubian\NoFramework\Settings;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedMarkdownPageRepo implements MarkdownPageRepo
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly MarkdownPageRepo $repo,
        private readonly Settings $settings,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        $callback = fn () => $this->repo->all();
        if ($this->settings->isDev()) {
            return $callback();
        }
        return $this->cache->get('ALLPAGES', function (ItemInterface $item) use ($callback) {
            $item->expiresAfter(30);
            return $callback();
        });
    }

    public function byId(int $id): MarkdownPage
    {
        $callback = fn () => $this->repo->byId($id);
        if ($this->settings->isDev()) {
            return $callback();
        }
        return $this->cache->get('PAGE' . $id, function (ItemInterface $item) use ($callback) {
            $item->expiresAfter(30);
            return $callback();
        });
    }

    public function byTitle(string $title): MarkdownPage
    {
        $callback = fn () => $this->repo->byTitle($title);
        if ($this->settings->isDev()) {
            return $callback();
        }
        return $this->cache->get('PAGE' . $title, function (ItemInterface $item) use ($callback) {
            $item->expiresAfter(30);
            return $callback();
        });
    }

    public function save(MarkdownPage $page): MarkdownPage
    {
        return $this->repo->save($page);
    }
}
