<?php declare(strict_types=1);

namespace Lubian\NoFramework\Http;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Lubian\NoFramework\Exception\InternalServerError;
use Lubian\NoFramework\Exception\MethodNotAllowed;
use Lubian\NoFramework\Exception\NotFound;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use function FastRoute\simpleDispatcher;

final class RouteDecorationMiddleware implements MiddlewareInterface, AddRoute
{
    /**
     * @param array<int, array{string, string, array{class-string, string}|class-string|callable}> $routes
     */
    public function __construct(
        private                 readonly ResponseFactoryInterface $responseFactory,
        private                 readonly string $routeAttributeName = '__route_handler',
        private array           $routes = [],
        private Dispatcher|null $dispatcher = null,
    )
    {
    }

    /**
     * @throws MethodNotAllowed
     * @throws NotFound
     */
    private function decorateRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $this->dispatcher ??= $this->createDispatcher();
        $routeInfo = $this->dispatcher->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath()
        );

        if ($routeInfo[0] === Dispatcher::NOT_FOUND) {
            throw new NotFound;
        }

        if ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            throw new MethodNotAllowed;
        }

        foreach ($routeInfo[2] as $attributeName => $attributeValue) {
            $request = $request->withAttribute($attributeName, $attributeValue);
        }

        return $request->withAttribute($this->routeAttributeName, $routeInfo[1]);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $request = $this->decorateRequest($request);
        } catch (NotFound) {
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write('Not Found');
            return $response;
        } catch (MethodNotAllowed) {
            return $this->responseFactory->createResponse(405);
        } catch (Throwable $t) {
            throw new InternalServerError($t->getMessage(), $t->getCode(), $t);
        }

        if ($handler instanceof RoutedRequestHandler) {
            $handler->setRouteAttributeName($this->routeAttributeName);
        }

        return $handler->handle($request);
    }

    private function createDispatcher(): Dispatcher
    {
        return simpleDispatcher(function (RouteCollector $r) {
            foreach ($this->routes as $route) {
                $r->addRoute($route[0], $route[1], $route[2]);
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function addRoute(string $method, string $path, array|string|callable $handler,): AddRoute
    {
        $this->routes[] = [$method, $path, $handler];
        return $this;
    }
}
