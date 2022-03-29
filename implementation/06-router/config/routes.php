<?php

declare(strict_types=1);

return function (\FastRoute\RouteCollector $r): void {
    $r->addRoute('GET', '/hello[/{name}]', function (\Psr\Http\Message\ServerRequestInterface $request) {
        $name = $request->getAttribute('name', 'Stranger');
        $response = (new \Laminas\Diactoros\Response())->withStatus(200);
        $response->getBody()->write('Hello ' . $name . '!');
        return $response;
    });
    $r->addRoute('GET', '/another-route', function (\Psr\Http\Message\ServerRequestInterface $request) {
        $response = (new \Laminas\Diactoros\Response())->withStatus(200);
        $response->getBody()->write('This works too!');
        return $response;
    });
};
