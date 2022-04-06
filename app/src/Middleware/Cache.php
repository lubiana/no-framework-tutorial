<?php declare(strict_types=1);

namespace Lubian\NoFramework\Middleware;

use Laminas\Diactoros\Response\Serializer;
use Lubian\NoFramework\Service\Cache\EasyCache;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;
use function base64_encode;
use function is_string;

final class Cache implements MiddlewareInterface
{
    public function __construct(
        private readonly EasyCache $cache,
        private readonly Serializer $serializer,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() !== 'GET') {
            return $handler->handle($request);
        }
        $keyHash = base64_encode($request->getUri()->getPath());
        $result = $this->cache->get(
            $keyHash,
            fn () => $this->serializer::toString($handler->handle($request)),
            300
        );
        assert(is_string($result));
        return $this->serializer::fromString($result);
    }
}
