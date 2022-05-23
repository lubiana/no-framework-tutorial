<?php declare(strict_types=1);

use FastRoute\RouteCollector;
use Lubian\NoFramework\Action\Hello;
use Lubian\NoFramework\Action\Other;
use Psr\Http\Message\ResponseInterface as RI;

return function (RouteCollector $r) {
    $r->addRoute('GET', '/hello[/{name}]', Hello::class);
    $r->addRoute('GET', '/other', [Other::class, 'handle']);
    $r->addRoute('GET', '/', fn (RI $r) => $r->withStatus(302)->withHeader('Location', '/hello'));
};
