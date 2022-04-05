<?php declare(strict_types=1);

namespace Lubian\NoFramework;

use Lubian\NoFramework\Factory\RequestFactory;
use Lubian\NoFramework\Http\Emitter;
use Lubian\NoFramework\Http\Pipeline;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Kernel implements RequestHandlerInterface
{
    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly Pipeline $pipeline,
        private readonly Emitter $emitter,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->pipeline->dispatch($request);
    }

    public function run(): void
    {
        $request = $this->requestFactory->fromGlobals();
        $response = $this->handle($request);
        $this->emitter->emit($response);
    }
}
