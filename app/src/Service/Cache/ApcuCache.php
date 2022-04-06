<?php declare(strict_types=1);

namespace Lubian\NoFramework\Service\Cache;

use function apcu_add;
use function apcu_fetch;

final class ApcuCache implements EasyCache
{
    public function get(string $key, callable $callback, int $ttl = 0): mixed
    {
        $success = false;
        $result = apcu_fetch($key, $success);
        if ($success === true) {
            return $result;
        }
        $result = $callback();
        apcu_add($key, $result, $ttl);
        return $result;
    }
}
