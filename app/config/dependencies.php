<?php declare(strict_types=1);

use FastRoute\Dispatcher;
use Laminas\Diactoros\ResponseFactory;
use Lubian\NoFramework\Factory\DiactorosRequestFactory;
use Lubian\NoFramework\Factory\PipelineProvider;
use Lubian\NoFramework\Factory\RequestFactory;
use Lubian\NoFramework\Http\BasicEmitter;
use Lubian\NoFramework\Http\Emitter;
use Lubian\NoFramework\Http\InvokerRoutedHandler;
use Lubian\NoFramework\Http\Pipeline;
use Lubian\NoFramework\Http\RoutedRequestHandler;
use Lubian\NoFramework\Http\RouteMiddleware;
use Lubian\NoFramework\Repository\CachedMarkdownPageRepo;
use Lubian\NoFramework\Repository\MarkdownPageFilesystem;
use Lubian\NoFramework\Repository\MarkdownPageRepo;
use Lubian\NoFramework\Service\Time\Now;
use Lubian\NoFramework\Service\Time\SystemClockNow;
use Lubian\NoFramework\Settings;
use Lubian\NoFramework\Template\MustacheRenderer;
use Lubian\NoFramework\Template\Renderer;
use Mustache_Engine as ME;
use Mustache_Loader_FilesystemLoader as MLF;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\CacheInterface;

use function FastRoute\simpleDispatcher;

return [
    ResponseInterface::class => fn (ResponseFactory $rf) => $rf->createResponse(),
    ServerRequestInterface::class => fn (RequestFactory $rf) => $rf->fromGlobals(),
    Now::class => fn (SystemClockNow $n) => $n,
    Renderer::class => fn (Mustache_Engine $e) => new MustacheRenderer($e),
    MLF::class => fn (Settings $s) => new MLF($s->templateDir, ['extension' => $s->templateExtension]),
    ME::class => fn (MLF $mfl) => new ME(['loader' => $mfl]),
    ResponseFactoryInterface::class => fn (ResponseFactory $rf) => $rf,
    Emitter::class => fn (BasicEmitter $e) => $e,
    RoutedRequestHandler::class => fn (InvokerRoutedHandler $h) => $h,
    MiddlewareInterface::class => fn (RouteMiddleware $r) => $r,
    Dispatcher::class => fn () => simpleDispatcher(require __DIR__ . '/routes.php'),
    RequestFactory::class => fn (DiactorosRequestFactory $rf) => $rf,
    Pipeline::class => fn (PipelineProvider $p) => $p->getPipeline(),
    CacheInterface::class => fn (FilesystemAdapter $a) => $a,
    MarkdownPageFilesystem::class => fn (Settings $s) => new MarkdownPageFilesystem($s->pagesPath),
    CachedMarkdownPageRepo::class => fn (CacheInterface $c, MarkdownPageFilesystem $r) => new CachedMarkdownPageRepo($c, $r),
    MarkdownPageRepo::class => fn (MarkdownPageFilesystem $r) => $r,
];
