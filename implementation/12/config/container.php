<?php declare(strict_types=1);

use DI\ContainerBuilder;
use FastRoute\Dispatcher;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Lubian\NoFramework\Configuration;
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
        Clock::class => fn () => new SystemClock,
        Renderer::class => fn (MustacheRenderer $me) => $me,
        Dispatcher::class => fn (Configuration $c) => simpleDispatcher(require $c->routesFile),
        Mustache_Loader_FilesystemLoader::class => fn (Configuration $c) => new Mustache_Loader_FilesystemLoader(
            $c->templateDir,
            [
                'extension' => $c->templateExtension,
            ]
        ),
        Mustache_Engine::class => fn (Mustache_Loader_FilesystemLoader $loader) => new Mustache_Engine([
            'loader' => $loader,
        ]),
        Configuration::class => fn () => require __DIR__ . '/settings.php',
    ]
);

return $builder->build();
