<?php declare(strict_types=1);

use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Lubian\NoFramework\Service\Time\Now;
use Lubian\NoFramework\Service\Time\SystemClockNow;
use Lubian\NoFramework\Settings;
use Lubian\NoFramework\Template\MustacheRenderer;
use Lubian\NoFramework\Template\Renderer;
use Mustache_Engine as ME;
use Mustache_Loader_FilesystemLoader as MLF;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

return [
    ResponseInterface::class => fn (ResponseFactory $rf) => $rf->createResponse(),
    ServerRequestInterface::class => fn (ServerRequestFactory $rf) => $rf::fromGlobals(),
    Now::class => fn (SystemClockNow $n) => $n,
    Renderer::class => fn (Mustache_Engine $e) => new MustacheRenderer($e),
    MLF::class => fn (Settings $s) => new MLF($s->templateDir, ['extension' => $s->templateExtension]),
    ME::class => fn (MLF $mfl) => new ME(['loader' => $mfl]),
];
