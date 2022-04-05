<?php declare(strict_types=1);

namespace Lubian\NoFramework\Http;

use FastRoute\Dispatcher;
use Lubian\NoFramework\Exception\InternalServerError;
use Lubian\NoFramework\Exception\MethodNotAllowed;
use Lubian\NoFramework\Exception\NotFound;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class RouteMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly string $routeAttributeName = '__route_handler',
    ) {
    }

    private function decorateRequest(
        ServerRequestInterface $request,
    ): ServerRequestInterface {
        $routeInfo = $this->dispatcher->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath(),
        );

        if ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            throw new MethodNotAllowed;
        }

        if ($routeInfo[0] === Dispatcher::FOUND) {
            foreach ($routeInfo[2] as $attributeName => $attributeValue) {
                $request = $request->withAttribute($attributeName, $attributeValue);
            }
            return $request->withAttribute(
                $this->routeAttributeName,
                $routeInfo[1]
            );
        }

        throw new NotFound;
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
}
