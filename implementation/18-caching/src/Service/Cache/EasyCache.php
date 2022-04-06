<?php declare(strict_types=1);

namespace Lubian\NoFramework\Service\Cache;

interface EasyCache
{
    /** @param callable(): mixed $callback */
    public function get(string $key, callable $callback, int $ttl = 0): mixed;
}
