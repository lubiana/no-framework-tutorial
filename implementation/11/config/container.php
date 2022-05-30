<?php declare(strict_types=1);
use FastRoute\Dispatcher;

use DI\ContainerBuilder;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Lubian\NoFramework\Service\Time\Clock;
use Lubian\NoFramework\Service\Time\SystemClock;
use Lubian\NoFramework\Template\MustacheRenderer;
use Lubian\NoFramework\Template\Renderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function FastRoute\simpleDispatcher;

$builder = new ContainerBuilder;
$builder->addDefinitions(
    [
        ServerRequestInterface::class => fn () => ServerRequestFactory::fromGlobals(),
        ResponseInterface::class => fn () => new Response,
        Dispatcher::class => fn () => simpleDispatcher(require __DIR__ . '/routes.php'),
        Clock::class => fn () => new SystemClock,
        Renderer::class => fn (MustacheRenderer $me) => $me,
        Mustache_Loader_FilesystemLoader::class => fn () => new Mustache_Loader_FilesystemLoader(
            __DIR__ . '/../templates',
            [
                'extension' => '.html',
            ]
        ),
        Mustache_Engine::class => fn (Mustache_Loader_FilesystemLoader $loader) => new Mustache_Engine([
            'loader' => $loader,
        ]),
    ]
);

return $builder->build();
