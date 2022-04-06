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
use Lubian\NoFramework\Repository\FileSystemMarkdownPageRepo;
use Lubian\NoFramework\Repository\MarkdownPageRepo;
use Lubian\NoFramework\Service\Cache\ApcuCache;
use Lubian\NoFramework\Service\Cache\EasyCache;
use Lubian\NoFramework\Service\Time\Now;
use Lubian\NoFramework\Service\Time\SystemClockNow;
use Lubian\NoFramework\Settings;
use Lubian\NoFramework\Template\MarkdownParser;
use Lubian\NoFramework\Template\MustacheRenderer;
use Lubian\NoFramework\Template\ParsedownParser;
use Lubian\NoFramework\Template\Renderer;
use Mustache_Engine as ME;
use Mustache_Loader_FilesystemLoader as MLF;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

use function FastRoute\simpleDispatcher;

return [
    // alias
    Now::class => fn (SystemClockNow $n) => $n,
    ResponseFactoryInterface::class => fn (ResponseFactory $rf) => $rf,
    Emitter::class => fn (BasicEmitter $e) => $e,
    MiddlewareInterface::class => fn (RouteMiddleware $r) => $r,
    RoutedRequestHandler::class => fn (InvokerRoutedHandler $h) => $h,
    RequestFactory::class => fn (DiactorosRequestFactory $rf) => $rf,
    MarkdownParser::class => fn (ParsedownParser $p) => $p,
    MarkdownPageRepo::class => fn (CachedMarkdownPageRepo $r) => $r,
    EasyCache::class => fn (ApcuCache $c) => $c,
    CachedMarkdownPageRepo::class => fn (EasyCache $c, FileSystemMarkdownPageRepo $r) => new CachedMarkdownPageRepo($c, $r),


    // Factories
    ResponseInterface::class => fn (ResponseFactory $rf) => $rf->createResponse(),
    ServerRequestInterface::class => fn (RequestFactory $rf) => $rf->fromGlobals(),
    Renderer::class => fn (Mustache_Engine $e) => new MustacheRenderer($e),
    MLF::class => fn (Settings $s) => new MLF($s->templateDir, ['extension' => $s->templateExtension]),
    ME::class => fn (MLF $mfl) => new ME(['loader' => $mfl]),
    Dispatcher::class => fn () => simpleDispatcher(require __DIR__ . '/routes.php'),
    Pipeline::class => fn (PipelineProvider $p) => $p->getPipeline(),
    FileSystemMarkdownPageRepo::class => fn (Settings $s) => new FileSystemMarkdownPageRepo($s->pagesPath),
];
