<?php declare(strict_types=1);

return new class () implements \Psr\Container\ContainerInterface {

    private readonly array $services;

    public function __construct()
    {
        $this->services = [
            \Psr\Http\Message\ServerRequestInterface::class => fn () => \Laminas\Diactoros\ServerRequestFactory::fromGlobals(),
            \Psr\Http\Message\ResponseInterface::class => fn () => new \Laminas\Diactoros\Response(),
            \FastRoute\Dispatcher::class => fn () => \FastRoute\simpleDispatcher(require __DIR__ . '/routes.php'),
            \Lubian\NoFramework\Service\Time\Clock::class => fn () => new \Lubian\NoFramework\Service\Time\SystemClock(),
            \Lubian\NoFramework\Action\Hello::class => fn () => new \Lubian\NoFramework\Action\Hello(
                $this->get(\Psr\Http\Message\ResponseInterface::class),
                $this->get(\Lubian\NoFramework\Service\Time\Clock::class)
            ),
            \Lubian\NoFramework\Action\Other::class => fn () => new \Lubian\NoFramework\Action\Other(
                $this->get(\Psr\Http\Message\ResponseInterface::class)
            ),
        ];
    }

    public function get(string $id)
    {
        if (! $this->has($id)) {
            throw new class () extends \Exception implements \Psr\Container\NotFoundExceptionInterface {
            };
        }
        return $this->services[$id]();
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services);
    }
};
