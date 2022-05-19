<?php declare(strict_types=1);

use FastRoute\RouteCollector;
use Lubian\NoFramework\Action\Hello;
use Lubian\NoFramework\Action\Other;

return function (RouteCollector $r) {
    $r->addRoute('GET', '/hello[/{name}]', Hello::class);
    $r->addRoute('GET', '/other', Other::class);
};
