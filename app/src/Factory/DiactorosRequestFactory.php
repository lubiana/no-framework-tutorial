<?php declare(strict_types=1);

namespace Lubian\NoFramework\Factory;

use Laminas\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

final class DiactorosRequestFactory implements RequestFactory
{
    public function __construct(private readonly ServerRequestFactory $factory)
    {
    }

    public function fromGlobals(): ServerRequestInterface
    {
        return $this->factory::fromGlobals();
    }

    /**
     * @param UriInterface|string $uri
     * @param array<mixed> $serverParams
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return $this->factory->createServerRequest($method, $uri, $serverParams);
    }
}
