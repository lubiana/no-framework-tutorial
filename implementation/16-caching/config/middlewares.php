<?php declare(strict_types=1);

use Lubian\NoFramework\Http\RouteMiddleware;
use Lubian\NoFramework\Middleware\CacheMiddleware;
use Middlewares\TrailingSlash;
use Middlewares\Whoops;

return [
    Whoops::class,
    TrailingSlash::class,
    CacheMiddleware::class,
    RouteMiddleware::class,
];
