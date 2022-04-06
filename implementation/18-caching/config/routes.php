<?php declare(strict_types=1);

use FastRoute\RouteCollector;
use Lubian\NoFramework\Action\Hello;
use Lubian\NoFramework\Action\Other;
use Lubian\NoFramework\Action\Page;
use Psr\Http\Message\ResponseInterface as Response;

return function (RouteCollector $r): void {
    $r->addRoute('GET', '/hello[/{name}]', Hello::class);
    $r->addRoute('GET', '/page', [Page::class, 'list']);
    $r->addRoute('GET', '/page/{page}', [Page::class, 'show']);
    $r->addRoute('GET', '/another-route', [Other::class, 'someFunctionName']);
    $r->addRoute('GET', '/', fn (Response $r) => $r->withStatus(302)->withHeader('Location', '/hello'));
};
