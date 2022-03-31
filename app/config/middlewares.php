<?php declare(strict_types=1);

use Lubian\NoFramework\Http\RouteMiddleware;
use Middlewares\TrailingSlash;
use Middlewares\Whoops;

return [
    Whoops::class,
    TrailingSlash::class,
    \Lubian\NoFramework\Middleware\CacheMiddleware::class,
    RouteMiddleware::class,
];
