<?php declare(strict_types=1);

use FastRoute\RouteCollector;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ServerRequestInterface;

return function (RouteCollector $r) {
    $r->addRoute('GET', '/hello[/{name}]', function (ServerRequestInterface $request) {
        $name = $request->getAttribute('name', 'Stranger');
        $response = (new Response)->withStatus(200);
        $response->getBody()
            ->write('Hello ' . $name . '!');
        return $response;
    });
    $r->addRoute('GET', '/other', function (ServerRequestInterface $request) {
        $response = (new Response)->withStatus(200);
        $response->getBody()
            ->write('This works too!');
        return $response;
    });
};
