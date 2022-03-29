<?php declare(strict_types=1);

namespace Lubian\NoFramework;

use Lubian\NoFramework\Exception\InternalServerError;
use Lubian\NoFramework\Http\AddRoute;
use Lubian\NoFramework\Http\Emitter;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

use function assert;

final class Kernel implements RequestHandlerInterface, AddRoute
{
    public function __construct(
        private ContainerInterface $container,
        private Emitter $emitter,
        private MiddlewareInterface&AddRoute $routeMiddleware,
        private RequestHandlerInterface $handler,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->routeMiddleware->process($request, $this->handler);
    }

    public function run(ServerRequestInterface |null $request = null): void
    {
        $request ??= $this->createRequest();
        $response = $this->handle($request);
        $this->emitter->emit($response);
    }

    private function createRequest(): ServerRequestInterface
    {
        try {
            $request = $this->container->get(ServerRequestInterface::class);
            assert($request instanceof ServerRequestInterface);
            return $request;
        } catch (Throwable $t) {
            throw new InternalServerError(
                'could not get Request from container, please configure the container ' .
                'in order to use run() wihtout a request',
                $t->getCode(),
                $t,
            );
        }
    }

    public function addRoute(
        string $method,
        string $path,
        array|string|callable $handler): AddRoute
    {
        $this->routeMiddleware->addRoute($method, $path, $handler);
        return $this;
    }
}
