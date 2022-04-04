<?php declare(strict_types=1);

namespace Lubian\NoFramework\Middleware;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

use function base64_encode;

final class CacheMiddleware implements MiddlewareInterface
{
    public function __construct(private CacheInterface $cache, private Response\Serializer $serializer)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'GET') {
            $key = (string) $request->getUri();
            $key = base64_encode($key);
            $callback = fn () => $handler->handle($request);
            $cached = $this->cache->get($key, function (ItemInterface $item) use ($callback) {
                $item->expiresAfter(120);
                $response = $callback();
                return $this->serializer::toString($response);
            });
            return $this->serializer::fromString($cached);
        }
        return $handler->handle($request);
    }
}
