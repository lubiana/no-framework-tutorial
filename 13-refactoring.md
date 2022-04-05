[<< previous](12-configuration.md) | [next >>](14-middleware.md)

### Refactoring

By now our Bootstrap.php file has grown quite a bit, and with the addition of our dependency container there is now no
reason not to introduce a lot of classes and interfaces for all the that are happening in the bootstrap file.
After all the bootstrap file should just set up the classes needed for the handling logic and execute them.

At the bottom of our Bootstrap.php we have our Response-Emitter Logic, lets create an Interface and a class for that.
As I am really lazy I just selected the code in PhpStorm, klicken on 'Refactor -> extract method' then selected the
method and clicked on 'Refactor -> extract class'. I choose 'BasicEmitter' for the classname, changed the method to non
static and extracted an interface.

'./src/Http/Emitter.php'
```php
<?php

namespace Lubian\NoFramework\Service\Http;

use Psr\Http\Message\ResponseInterface;

interface Emitter
{
    public function emit(ResponseInterface $response, bool $withoutBody = false): void;
}
```

'./src/Http/BasicEmitter.php'
```php
<?php

declare(strict_types=1);

namespace Lubian\NoFramework\Service\Http;

use Psr\Http\Message\ResponseInterface;

final class BasicEmitter implements Emitter
{
    public function emit(ResponseInterface $response, bool $withoutBody = false): void
    {
        /** @var string $name */
        foreach ($response->getHeaders() as $name => $values) {
            $first = strtolower($name) !== 'set-cookie';
            foreach ($values as $value) {
                $header = sprintf('%s: %s', $name, $value);
                header($header, $first);
                $first = false;
            }
        }

        $statusLine = sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );
        header($statusLine, true, $response->getStatusCode());

        if ($withoutBody) {
            return;
        }
        echo $response->getBody();
    }
}
```
After registering the BasicEmitter to implement the Emitter interface in the dependencies file you can use the following
code in the Bootstrap.php to emit the response:

```php
/** @var Emitter $emitter */
$emitter = $container->get(Emitter::class);
$emitter->emit($response);
```

If at some point you need a [more advanced emitter](https://github.com/httpsoft/http-emitter), you could now easily 
write an adapter that implements your emitter interface and wraps that more advanced emitter

Now that we have our Emitter in a seperate class we need to take care of the big block that handles our routing and
calling the routerhandler that in the passes the request to a function and gets the response.

For this to steps to be seperated we are going to create two more classes:
1. a RouteDecorator, that finds the correct handler for the requests and adds its findings to the Request Object
2. A Requesthandler that implements the RequestHandlerInterface, gets the information for the request handler from the
    requestobject, fetches the correct object from the container and calls it to create a response.

Lets create the HandlerInterface first:

```php
<?php

declare(strict_types=1);

namespace Lubian\NoFramework\Service\Http;

use Psr\Http\Server\RequestHandlerInterface;

interface RoutedRequestHandler extends RequestHandlerInterface
{
    public function setRouteAttributeName(string $routeAttributeName = '__route_handler'): void;
}
```

By looking at the namespace and interfacename you should be able to figure out where to place the file and how to name
it.

We define a public function that the router can use to tell the handler which attribute name to look for in the request.

Now write an implementation that uses a container to satisfy the interface.

```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Http;

use Invoker\InvokerInterface;
use Lubian\NoFramework\Exception\InternalServerError;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function assert;

final class InvokerRoutedHandler implements RoutedRequestHandler
{
    public function __construct(
        private readonly InvokerInterface $invoker,
        private string $routeAttributeName = '__route_handler',
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $handler = $request->getAttribute($this->routeAttributeName, false);
        assert($handler !== false);
        $vars = $request->getAttributes();
        $vars['request'] = $request;
        $response = $this->invoker->call($handler, $vars);
        if (! $response instanceof ResponseInterface) {
            throw new InternalServerError('Handler returned invalid response');
        }
        return $response;
    }

    public function setRouteAttributeName(string $routeAttributeName = '__route_handler'): void
    {
        $this->routeAttributeName = $routeAttributeName;
    }
}

```

We will define our routing class to implement the MiddlewareInterface, you can install that with 'composer require psr/http-server-middleware'.
The interface requires us to implement a method called 'process' a Request as its first argument and an RequestHandler
as the second one. The return value of the method needs to be a Responseobject. We will learn more about Middlewares in
the next chapter.

```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Http;

use FastRoute\Dispatcher;
use Lubian\NoFramework\Exception\InternalServerError;
use Lubian\NoFramework\Exception\MethodNotAllowed;
use Lubian\NoFramework\Exception\NotFound;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class RouteMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly string $routeAttributeName = '__route_handler',
    ) {
    }

    private function decorateRequest(
        ServerRequestInterface $request,
    ): ServerRequestInterface {
        $routeInfo = $this->dispatcher->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath(),
        );

        if ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            throw new MethodNotAllowed;
        }

        if ($routeInfo[0] === Dispatcher::FOUND) {
            foreach ($routeInfo[2] as $attributeName => $attributeValue) {
                $request = $request->withAttribute($attributeName, $attributeValue);
            }
            return $request->withAttribute(
                $this->routeAttributeName,
                $routeInfo[1]
            );
        }

        throw new NotFound;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $request = $this->decorateRequest($request);
        } catch (NotFound) {
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write('Not Found');
            return $response;
        } catch (MethodNotAllowed) {
            return $this->responseFactory->createResponse(405);
        } catch (Throwable $t) {
            throw new InternalServerError($t->getMessage(), $t->getCode(), $t);
        }

        if ($handler instanceof RoutedRequestHandler) {
            $handler->setRouteAttributeName($this->routeAttributeName);
        }
        return $handler->handle($request);
    }
}
```

Before we can use all the new services in our Bootstrap file we need to add the definitions to our container.
```php
[
        '...',
        Emitter::class => fn (BasicEmitter $e) => $e,
        RoutedRequestHandler::class => fn (InvokerRoutedHandler $h) => $h,
        MiddlewareInterface::class => fn (RouteMiddleware $r) => $r,
        Dispatcher::class => fn (Settings $s) => simpleDispatcher(require __DIR__ . '/routes.php'),
        ResponseFactoryInterface::class => fn (ResponseFactory $rf) => $rf,
],
```

And then we can update our Bootstrap.php to fetch all the services and let them handle the request.

```php
...
$routeMiddleWare = $container->get(MiddlewareInterface::class);
assert($routeMiddleWare instanceof MiddlewareInterface);
$handler = $container->get(RoutedRequestHandler::class);
assert($handler instanceof RequestHandlerInterface);
$emitter = $container->get(Emitter::class);
assert($emitter instanceof Emitter);

$request = $container->get(ServerRequestInterface::class);
assert($request instanceof ServerRequestInterface);

$response = $routeMiddleWare->process($request, $handler);
$emitter->emit($response);
```
Now we have wrapped all the important parts in our Bootstrap.php into seperate classes, but it is still quite a lot of
code and also many calls the container (and i have to write way too many docblocks to that phpstan doenst yell at me).

So we should just add another class that wraps all of our Request-Handling Classes into a clearly defined structure.

I will follow symfonys example and call this class our kernel. Before i create that class i will recap what our class
should require to function properly.

* A RequestFactory
  We want our Kernel to be able to build the request itself
* An Emitter
  Without an Emitter we will not be able to send the response to the client
* RouteMiddleware
  To decore the request with the correct handler for the requested route
* RequestHandler
  To delegate the request to the correct funtion that creates the response

As the Psr ContainerInterface leaves us to much handiwork to easily create a Serverrequest I will extend that interface
to give us easier access to a requestobject and wrap the Diactorors RequestFactory in an Adapter that satisfies our
interface:

```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Factory;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RequestFactory extends ServerRequestFactoryInterface
{
    public function fromGlobals(): ServerRequestInterface;
}
```

```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Factory;

use Laminas\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;

final class DiactorosRequestFactory implements RequestFactory
{
    public function __construct(private readonly ServerRequestFactory $factory)
    {
    }

    public function fromGlobals(): ServerRequestInterface
    {
        return $this->factory::fromGlobals();
    }

    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return $this->factory->createServerRequest($method, $uri, $serverParams);
    }
}
```

For later shenanigans I will let our Kernel implement the RequestHandlerInterface, this is how my version looks now:

```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework;

use Lubian\NoFramework\Factory\RequestFactory;
use Lubian\NoFramework\Http\Emitter;
use Lubian\NoFramework\Http\RoutedRequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Kernel implements RequestHandlerInterface
{
    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly MiddlewareInterface $routeMiddleware,
        private readonly RoutedRequestHandler $handler,
        private readonly Emitter $emitter,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->routeMiddleware->process($request, $this->handler);
    }

    public function run(): void
    {
        $request = $this->requestFactory->fromGlobals();
        $response = $this->handle($request);
        $this->emitter->emit($response);
    }
}

```

We can now replace everything after the ErrorHandler in our Bootstrap.php with these few lines

```php
$app = $container->get(Kernel::class);
assert($app instanceof Kernel);

$app->run();
```

You might get some Errors here because the Container cannot resolve all the dependencies, try to fix those errors by looking
at the Whoops output and adding the needed definitions to the dependencies.php file.

And as always, don't forget to commit your changes.

[<< previous](12-configuration.md) | [next >>](14-middleware.md)
