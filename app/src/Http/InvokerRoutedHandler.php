<?php declare(strict_types=1);

namespace Lubian\NoFramework\Http;

use Invoker\InvokerInterface;
use Lubian\NoFramework\Exception\InternalServerError;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class InvokerRoutedHandler implements RoutedRequestHandler
{
    public function __construct(
        private readonly InvokerInterface $invoker,
        private string $routeAttributeName = '__route_handler',
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $handler = $request->getAttribute($this->routeAttributeName, false);
        $vars = $request->getAttributes();
        $vars['request'] = $request;
        $response = $this->invoker->call($handler, $vars);
        if (! $response instanceof ResponseInterface) {
            throw new InternalServerError('Handler returned invalid response');
        }
        return $response;
    }

    public function setRouteAttributeName(string $routeAttributeName = '__route_handler'): void
    {
        $this->routeAttributeName = $routeAttributeName;
    }
}
