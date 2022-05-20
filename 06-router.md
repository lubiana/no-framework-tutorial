[<< previous](05-http.md) | [next >>](07-dispatching-to-a-class.md)

### Router

A router dispatches to different handlers depending on rules that you have set up.

With your current setup it does not matter what URL is used to access the application, it will always result in the same
response. So let's fix that now.

I will use [nikic/fast-route](https://github.com/nikic/FastRoute) in this tutorial. But as always, you can pick your own
favorite package.

Alternative packages: [symfony/Routing](https://github.com/symfony/Routing), [Aura.Router](https://github.com/auraphp/Aura.Router), [fuelphp/routing](https://github.com/fuelphp/routing), [Klein](https://github.com/chriso/klein.php)

By now you know how to install Composer packages, so I will leave that to you.

Now add this code block to your `Bootstrap.php` file where you added the 'hello world' message in the last chapter.

```php
$dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/hello[/{name}]', function (\Psr\Http\Message\ServerRequestInterface $request) {
        $name = $request->getAttribute('name', 'Stranger');
        $response = (new \Laminas\Diactoros\Response)->withStatus(200);
        $response->getBody()->write('Hello ' . $name . '!');
        return $response;
    });
    $r->addRoute('GET', '/another-route', function (\Psr\Http\Message\ServerRequestInterface $request) {
        $response = (new \Laminas\Diactoros\Response)->withStatus(200);
        $response->getBody()->write('This works too!');
        return $response;
    });
});

$routeInfo = $dispatcher->dispatch(
    $request->getMethod(),
    $request->getUri()->getPath(),
);

switch ($routeInfo[0]) {
    case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $response = (new \Laminas\Diactoros\Response)->withStatus(405);
        $response->getBody()->write('Method not allowed');
        $response = $response->withStatus(405);
        break;
    case \FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        foreach ($routeInfo[2] as $attributeName => $attributeValue) {
            $request = $request->withAttribute($attributeName, $attributeValue);
        }
        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = call_user_func($handler, $request);
        break;
    case \FastRoute\Dispatcher::NOT_FOUND:
    default:
        $response = (new \Laminas\Diactoros\Response)->withStatus(404);
        $response->getBody()->write('Not Found!');
        break;
}
```

In the first part of the code, you are registering the available routes for your application. In the second part, the
dispatcher gets called and the appropriate part of the switch statement will be executed. If a route was found, 
we collect any variable parameters of the route, store them in the request parameterbag and call the handler callable.
If the route dispatcher returns a wrong value in the first entry of the routeMatch array we handle it the same as a 404.

This setup might work for tiny applications, but once you start adding a few routes your bootstrap file will
quickly get cluttered. So let's move them out into a separate file.

Create a new directory in you project root named 'config' and add a 'routes.php' file with the following content;

```php
<?php declare(strict_types = 1);

return function(\FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/hello[/{name}]', function (\Psr\Http\Message\ServerRequestInterface $request) {
        $name = $request->getAttribute('name', 'Stranger');
        $response = (new \Laminas\Diactoros\Response)->withStatus(200);
        $response->getBody()->write('Hello ' . $name . '!');
        return $response;
    });
    $r->addRoute('GET', '/another-route', function (\Psr\Http\Message\ServerRequestInterface $request) {
        $response = (new Laminas\Diactoros\Response)->withStatus(200);
        $response->getBody()->write('This works too!');
        return $response;
    });
};
```

Now let's rewrite the route dispatcher part to use the `routes.php` file.

```php
$routeDefinitionCallback = require __DIR__ . '/../config/routes.php';
$dispatcher = \FastRoute\simpleDispatcher($routeDefinitionCallback);
```

This is already an improvement, but now all the handler code is in the `routes.php` file. This is not optimal, so let's fix that in the next part.

Of course, we now need to add the 'config' folder to the configuration files of our
dev helpers so that they can scan that directory as well.

[<< previous](05-http.md) | [next >>](07-dispatching-to-a-class.md)
