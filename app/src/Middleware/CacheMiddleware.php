<?php declare(strict_types=1);

namespace Lubian\NoFramework\Middleware;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class CacheMiddleware implements MiddlewareInterface
{
    public function __construct(private CacheInterface $cache){}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'GET') {
            $key = (string) $request->getUri();
            $key = base64_encode($key);
            $callback = fn () => $handler->handle($request);
            $response = new Response();
            $body = $this->cache->get($key, function (ItemInterface $item) use ($callback) {
                $item->expiresAfter(120);
                return (string) $callback()->getBody();
            });
            $response->getBody()->write($body);
            return $response;
        }
        return $handler->handle($request);
    }
}
