<?php declare(strict_types=1);

use DI\ContainerBuilder;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Lubian\NoFramework\Service\Time\Clock;
use Lubian\NoFramework\Service\Time\SystemClock;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function FastRoute\simpleDispatcher;

$builder = new ContainerBuilder;
$builder->addDefinitions(
    [
        ServerRequestInterface::class => fn () => ServerRequestFactory::fromGlobals(),
        ResponseInterface::class => fn () => new Response,
        FastRoute\Dispatcher::class => fn () => simpleDispatcher(require __DIR__ . '/routes.php'),
        Clock::class => fn () => new SystemClock,
    ]
);

return $builder->build();
