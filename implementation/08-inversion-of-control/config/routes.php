<?php

declare(strict_types=1);

return function (\FastRoute\RouteCollector $r): void {
    $r->addRoute('GET', '/hello[/{name}]', [\Lubian\NoFramework\Action\Hello::class, 'handle']);
    $r->addRoute('GET', '/another-route', [\Lubian\NoFramework\Action\Another::class, 'handle']);
};
