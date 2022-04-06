<?php declare(strict_types=1);

namespace Lubian\NoFramework\Http;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_reverse;
use function assert;
use function is_string;

class ContainerPipeline implements Pipeline
{
    /**
     * @param array<MiddlewareInterface|class-string> $middlewares
     * @param RequestHandlerInterface $tip
     * @param ContainerInterface $container
     */
    public function __construct(
        private array $middlewares,
        private RequestHandlerInterface $tip,
        private ContainerInterface $container,
    ) {
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $this->buildStack();
        return $this->tip->handle($request);
    }

    private function buildStack(): void
    {
        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = $this->tip;
            if ($middleware instanceof MiddlewareInterface) {
                $this->tip = $this->wrapMiddleware($middleware, $next);
            }
            if (is_string($middleware)) {
                $this->tip = $this->wrapResolvedMiddleware($middleware, $next);
            }
        }
    }

    private function wrapResolvedMiddleware(string $middleware, RequestHandlerInterface $next): RequestHandlerInterface
    {
        return new class ($middleware, $next, $this->container) implements RequestHandlerInterface {
            public function __construct(
                private readonly string $middleware,
                private readonly RequestHandlerInterface $handler,
                private readonly ContainerInterface $container,
            ) {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $middleware = $this->container->get($this->middleware);
                assert($middleware instanceof MiddlewareInterface);
                return $middleware->process($request, $this->handler);
            }
        };
    }

    private function wrapMiddleware(MiddlewareInterface $middleware, RequestHandlerInterface $next): RequestHandlerInterface
    {
        return new class ($middleware, $next) implements RequestHandlerInterface {
            public function __construct(
                private readonly MiddlewareInterface $middleware,
                private readonly RequestHandlerInterface $handler,
            ) {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->middleware->process($request, $this->handler);
            }
        };
    }
}
