<?php declare(strict_types=1);

namespace Lubian\NoFramework\Http;

use Psr\Http\Server\RequestHandlerInterface;

interface RoutedRequestHandler extends RequestHandlerInterface
{
    public function setRouteAttributeName(string $routeAttributeName = '__route_handler'): void;
}
