<?php declare(strict_types=1);

use DI\ContainerBuilder;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Lubian\NoFramework\Service\Time\Now;
use Lubian\NoFramework\Service\Time\SystemClockNow;
use Lubian\NoFramework\Settings;
use Lubian\NoFramework\Template\MustacheRenderer;
use Lubian\NoFramework\Template\Renderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$builder = new ContainerBuilder;
$builder->addDefinitions([
    Settings::class => fn () => require __DIR__ . '/settings.php',
    ResponseInterface::class => fn (ResponseFactory $rf) => $rf->createResponse(),
    ServerRequestInterface::class => fn (ServerRequestFactory $rf) => $rf::fromGlobals(),
    Now::class => fn (SystemClockNow $n) => $n,
    Renderer::class => fn (Mustache_Engine $e) => new MustacheRenderer($e),
    Mustache_Loader_FilesystemLoader::class => function (Settings $s) {
        return new Mustache_Loader_FilesystemLoader(
            $s->templateDir,
            [
                'extension' => $s->templateExtension,
            ],
        );
    },
    Mustache_Engine::class => fn (Mustache_Loader_FilesystemLoader $mfl) => new Mustache_Engine(['loader' => $mfl]),
]);

return $builder->build();
