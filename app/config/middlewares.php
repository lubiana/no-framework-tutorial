<?php declare(strict_types=1);

use Lubian\NoFramework\Http\RouteMiddleware;
use Middlewares\TrailingSlash;
use Middlewares\Whoops;

return [
    Whoops::class,
    TrailingSlash::class,
    RouteMiddleware::class,
];
