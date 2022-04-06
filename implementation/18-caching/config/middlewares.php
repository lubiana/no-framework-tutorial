<?php declare(strict_types=1);

use Lubian\NoFramework\Http\RouteMiddleware;
use Lubian\NoFramework\Middleware\Cache;
use Middlewares\TrailingSlash;
use Middlewares\Whoops;

return [
    Whoops::class,
    Cache::class,
    TrailingSlash::class,
    RouteMiddleware::class,
];
