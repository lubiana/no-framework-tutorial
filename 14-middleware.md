[<< previous](12-refactoring.md) | [next >>](14-invoker.md)

### Middleware

In the last chapter we wrote our RouterClass to implement the middleware interface, and in this chapter I want to explain
a bit more about what this interface does and why it is used in many applications.

The Middlewares are basically a number of wrappers that stand between the client and your application. Each request gets
passed through all the middlewares, gets handled by our controllers and then the response gets passed back through all
the middlewars to the client/emitter.

So every Middleware can modify the request before it goes on to the next middleware (and finally the handler) and the
response after it gets created by our handlers.

So lets take a look at the middleware and the requesthandler interfaces

```php
interface MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
}

interface RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface;
}
```

The RequestHandlerInterface gets only a request and returns a response, the MiddlewareInterface gets a request and a
requesthandler and returns a response. So the logical thing for the Middleware is to use the handler to produce the
response.

But the middleware could just ignore the handler and produce a response on its own as the interface just requires us
to produce a response.

A simple example for that would be a caching middleware. The basic idea is that we want to cache all request from users
that are not logged in. This way we can save a lot of processing power in rendering the html and fetching data from the
database.

In this scenario we assume that we have an authentication middleware that checks if a user is logged in and decorates
the request with an 'isAuthenticated' attribute.

If the 'isAuthenticated' attribute is set to false, we check if we have a cached response and return that, if that
response is not already cached, than we let the handler create the response and store that in the cache for a few
seconds

```php
interface CacheInterface
{
    public function get(string $key, callable $resolver, int $ttl): mixed;
}
```

The first parameter is the identifier for the cache, the second is a callable that produces the value and the last one
defines the seconds that the cache should keep the item. If the cache doesnt have an item with the given key then it uses
the callable to produce the value and stores it for the time specified in ttl.

so lets write our caching middleware:

```php
final class CachingMiddleware implements MiddlewareInterface
{
    public function __construct(private CacheInterface $cache){}
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getAttribute('isAuthenticated', false)) {
            $key = $request->getUri()->getPath();
            return $this->cache->get($key, fn() => $handler->handle($request), 10);
        }
        return $handler->handle($request);
    }
}
```

we can also modify the response after it has been created by our application, for example we could implement a gzip
middleware, or for more simple and silly example a middleware that adds a Dank Meme header to all our response so that the browser
know that our application is used to serve dank memes:

```php
final class DankMemeMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        return $response->withAddedHeader('Meme', 'Dank');
    }
}
```

but for our application we are going to just add two external middlewares:

* [Trailing-slash](https://github.com/middlewares/trailing-slash) to remove the trailing slash from all routes.
* [whoops middleware](https://github.com/middlewares/whoops) to wrap our error handler into a nice middleware

```bash
composer require middlewares/trailing-slash
composer require middlewares/whoops
```

The whoops middleware should be the first middleware to be executed so that we catch any errors that are thrown in the
application as well as the middleware stack.

Our desired request -> response flow looks something like this:

            Client
            |     ^
            v     |
             Kernel
            |     ^
            v     |
         Whoops Middleware
            |     ^
            v     |
          TrailingSlash
            |     ^
            v     |
            Routing
            |     ^
            v     |
         ContainerResolver
            |     ^
            v     |
         Controller/Action

As every middleware expects a RequestHandlerInterface as its second argument we need some extra code that wraps every
middleware as a RequestHandler and chains them together with the ContainerRouteDecoratedResolver as the last Handler.

```php
interface Pipeline
{
    public function dispatch(ServerRequestInterface $request): ResponseInterface;
}
```

And our implementation looks something like this:

```php
final class SimplePipeline implements Pipeline
{
    /**
    * @param MiddlewareInterface[] $middlewares
    * @param RequestHandlerInterface $tip
    */
    public function __construct(
        private readonly array $middlewares,
        private RequestHandlerInterface $tip
    ){}

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $this->buildStack();
        return $this->tip->handle($request);
    }

    private function buildStack(): void
    {
        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = $this->tip;
            $this->tip = new class ($middleware, $next) implements RequestHandlerInterface {
                public function __construct(
                    private readonly MiddlewareInterface $middleware,
                    private readonly RequestHandlerInterface $next
                ){}

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->middleware->process($request, $this->next);
                }
            };
        }
    }
}
```

Here we define our constructor to require two arguments: an array of middlewares and a requesthandler as the final code
that should produce our response.

In the buildStack() method we wrap every middleware as a RequestHandler with the current tip property as the $next argument
and store that itself as the current tip.

There are of course a lot of more sophisticated ways to build a pipeline/dispatcher that you can check out at the [middlewares github](https://github.com/middlewares/awesome-psr15-middlewares#dispatcher)

Lets add a simple factory to our dependencies.php file that creates our middlewarepipeline:
```php
        Pipeline::class => function (
            RouteDecoratedRequestHandler $tip,
            RouteDecorationMiddleware $router,
        ) {
            $middlewares = require __DIR__ . '/middlewares.php';
            $middlewares[] = $router;
            return new SimplePipeline($middlewares, $tip);
        },
```
And of course a new file called middlewares.php in our config folder:
```php
<?php

declare(strict_types=1);

return [
    new \Middlewares\Whoops(),
    new \Middlewares\TrailingSlash(),
];
```

And we need to add the pipeline to our Kernel class. I will leave that as an exercise to you, a simple hint that i can
give you is that the handle()-method of the Kernel should look like this:

```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    return $this->pipeline->dispatch($request);
}
```

Lets try if you can make the kernel work with our created Pipeline implementation. For the future we could improve our
pipeline a little bit, so that it can accept a class-string of a middleware and resolves that with the help of a
dependency container, if you want you can do that as well.

[<< previous](12-refactoring.md) | [next >>](14-invoker.md)
