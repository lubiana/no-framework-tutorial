[<< previous](08-inversion-of-control.md) | [next >>](10-invoker.md)

### Dependency Injector

In the last chapter we rewrote our Actions to require the response-objet as a constructor parameter, and provided it
in the dispatcher section of our `Bootstrap.php`. As we only have one dependency this works really fine, but if we have
different classes with different dependencies our bootstrap file gets complicated quite quickly. Lets look at an example
to explain the problem and work on a solution.

#### Adding a clock service

Lets assume that we want to show the current time in our Hello action. We could easily just call use one of the many
ways to get the current time directly in the handle-method, but lets create a separate class and interface for that so
we can later configure and switch our implementation.

We need a new 'Service\Time' namespace, so lets first create the folder in our 'src' directory 'src/Service/Time'.
There we place a Clock.php interface and a SystemClock.php implementation:


The Clock.php interface:
```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Service\Time;

interface Clock
{
    public function now(): \DateTimeImmutable;
}
```

The SystemClock.php implementation:
```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Service\Time;

final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

}
```

Now we can require the Clockinterface as a depencency in our controller and use it to display the current time.
```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Action;


use Lubian\NoFramework\Service\Time\Clock;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Hello implements RequestHandlerInterface
{
    public function __construct(
        private readonly ResponseInterface $response,
        private readonly Clock $clock
    )
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $name = $request->getAttribute('name', 'Stranger');
        $body = $this->response->getBody();

        $time = $this->clock->now()->format('H:i:s');

        $body->write('Hello ' . $name . '!<br />');
        $body->write('The Time is: ' . $time);

        return $this->response->withBody($body)
            ->withStatus(200);
    }
}
```

But if we try to access the corresponding route in the webbrowser we get an error:
> Too few arguments to function Lubian\NoFramework\Action\Hello::__construct(), 1 passed in /home/lubiana/PhpstormProjects/no-framework/app/src/Bootstrap.php on line 62 and exactly 2 expected

Our current problem is, that we have two Actions defined, which both have different constructor requirements. That means,
that we need to have some code in our Application, that creates our Action Objects and takes care of injection all the
needed dependencies.

This code is called a Dependency Injector. If you want you can read [this](https://afilina.com/learn/design/dependency-injection)
great blogpost about that topic, which I highly recommend.

Lets build our own Dependency Injector to make our application work again.

As a starting point we are going to take a look at the [Container Interface])(https://www.php-fig.org/psr/psr-11/) that
is widely adopted in the PHP-World.

#### Building a dependency container

**Short Disclaimer:** *Although it would be fun to write our own great implementation of this interface with everything that
is needed for modern php development I will take a shortcut here and implement very reduced version to show you the
basic concept.*

The `Pst\Container\ContainerIterface` defines two methods:

* has($id): bool
    returns true if the container can provide a value for a given ID
* get($id): mixed
    returns some kind of value that is registered in the container for the given ID

I mostly define an Interface or a fully qualified classname as an ID. That way I can query the container for
the Clock interface or an Action class and get an object of that class or an object implementing the given Interface.

For the sake of this tutorial we will put a new file in our config folder that returns an anonymous class implementing
the containerinterface.

In this class we will configure all services required for our application and make them accessible via the get($id)
method.

`config/container.php`:
```php
<?php declare(strict_types=1);

return new class () implements \Psr\Container\ContainerInterface {

    private readonly array $services;

    public function __construct()
    {
        $this->services = [
            \Psr\Http\Message\ServerRequestInterface::class => fn () => \Laminas\Diactoros\ServerRequestFactory::fromGlobals(),
            \Psr\Http\Message\ResponseInterface::class => fn () => new \Laminas\Diactoros\Response(),
            \FastRoute\Dispatcher::class => fn () => \FastRoute\simpleDispatcher(require __DIR__ . '/routes.php'),
            \Lubian\NoFramework\Service\Time\Clock::class => fn () => new \Lubian\NoFramework\Service\Time\SystemClock(),
            \Lubian\NoFramework\Action\Hello::class => fn () => new \Lubian\NoFramework\Action\Hello(
                $this->get(\Psr\Http\Message\ResponseInterface::class),
                $this->get(\Lubian\NoFramework\Service\Time\Clock::class)
            ),
            \Lubian\NoFramework\Action\Other::class => fn () => new \Lubian\NoFramework\Action\Other(
                $this->get(\Psr\Http\Message\ResponseInterface::class)
            ),
        ];
    }

    public function get(string $id)
    {
        if (! $this->has($id)) {
            throw new class () extends \Exception implements \Psr\Container\NotFoundExceptionInterface {
            };
        }
        return $this->services[$id]();
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services);
    }
};
```

Here I have declared a services array, that has a class- or interfacename as the keys, and the values are short
closures that return an Object of the defined class or interface. The `has` method simply checks if the given id is
defined in our services array, and the `get` method calls the closure defined in the array for the given id key and then
returns the result of that closure.

To use the container we need to update our Bootstrap.php. Firstly we need to get an instance of our container, and then
use that to create our Request-Object as well as the Dispatcher. So remove the manual instantion of those objects and
replace that with the following code:

```php
$container = require __DIR__ . '/../config/container.php';
assert($container instanceof \Psr\Container\ContainerInterface);

$request = $container->get(\Psr\Http\Message\ServerRequestInterface::class);
assert($request instanceof \Psr\Http\Message\ServerRequestInterface);

$dispatcher = $container->get(FastRoute\Dispatcher::class);
assert($dispatcher instanceof \FastRoute\Dispatcher);
```

In the Dispatcher switch block we manually build our handler object with this two lines:


```php
$handler = new $className($response);
assert($handler instanceof RequestHandlerInterface);
```

Instead of manually creating the Handler-Instance we are going to kindly ask the Container to build it for us:

```php
$handler = $container->get($className);
assert($handler instanceof RequestHandlerInterface);
```

If you now open the `/hello` route in your browser everything should work again!

#### Using Autowiring



A dependency injector resolves the dependencies of your class and makes sure that the correct objects are injected when
the class is instantiated.

Again the FIG has defined an [interface](https://www.php-fig.org/psr/psr-11/) for dependency injection that we can work
with. Almost all common dependency injection containers implement this interface, so it is a good starting point to look
for a [suitable solution on packagist](https://packagist.org/providers/psr/container-implementation).

I choose the [PHP-DI](https://packagist.org/packages/php-di/php-di) container, as it is easy to configure and provides some very [powerfull features](https://php-di.org/#autowiring) 
out of the box.

After installing the container through composer create a new file with the name `dependencies.php` in your config folder:

```php
<?php declare(strict_types = 1);

$builder = new \DI\ContainerBuilder();
$builder->addDefinitions([
    \Psr\Http\Message\ResponseInterface::class => \DI\create(\Laminas\Diactoros\Response::class),
    \Psr\Http\Message\ServerRequestInterface::class => fn () => \Laminas\Diactoros\ServerRequestFactory::fromGlobals(),
]);

return $builder->build();
```

In this file we create a containerbuilder, add some definitions to it and return the container.
As the container supports autowiring we only need to define services where we want to use a specific implementation of
an interface.

In the example i used two different ways of defining the service. The first is by using the 'create' method of PHP-DI to
tell the container that it should create a Diactoros\Response object when ever I query a ResponseInterface, in the second
exampler I choose to write a small factory closure that wraps the Laminas Requestfactory. 

Make sure to read the documentation on definition types on the [PHP-DI website](https://php-di.org/doc/php-definitions.html#definition-types),
as we will use that extensively.

Of course your `Bootstrap.php` will also need to be changed. Before you were setting up `$request` and `$response` with `new` calls. Switch that to the dependency container. We do not need to get the response here, as the container will create and use it internally
to create our Handler-Object

```php
$container = require __DIR__ . '/../config/dependencies.php';
assert($container instanceof \Psr\Container\ContainerInterface);

$request = $container->get(\Psr\Http\Message\ServerRequestInterface::class);
assert($request instanceof \Psr\Http\Message\ServerRequestInterface);
```

The other part that has to be changed is the dispatching of the route. Before you had the following code:

```php
$className = $routeInfo[1];
$handler = new $className($response);
assert($handler instanceof \Psr\Http\Server\RequestHandlerInterface)
foreach ($routeInfo[2] as $attributeName => $attributeValue) {
    $request = $request->withAttribute($attributeName, $attributeValue);
}
$response = $handler->handle($request);
```

Change that to the following:

```php
/** @var RequestHandlerInterface $handler */
$className = $routeInfo[1];
$handler = $container->get($className);
assert($handler instanceof RequestHandlerInterface);
foreach ($routeInfo[2] as $attributeName => $attributeValue) {
    $request = $request->withAttribute($attributeName, $attributeValue);
}
$response = $handler->handle($request);
```

Make sure to use the container fetch the response object in the catch blocks as well:

```php
} catch (MethodNotAllowed) {
    $response = $container->get(ResponseInterface::class);
    assert($response instanceof ResponseInterface);
    $response = $response->withStatus(405);
    $response->getBody()->write('Not Allowed');
} catch (NotFound) {
    $response = $container->get(ResponseInterface::class);
    assert($response instanceof ResponseInterface);
    $response = $response->withStatus(404);
    $response->getBody()->write('Not Found');
}
```

Now all your controller constructor dependencies will be automatically resolved with PHP-DI.

We can now use that to inject all kinds of services. Often we need to work with the Current time to do some comparisons
in an application. Of course we are writing S.O.L.I.D. and testable code so that we would never be so crazy as to call
`$time = new \DateTimeImmutable();` in our Action directly, because then we would need to change the system time of we
want to work with a different date in a test.

Therefore we are creating a new Namespace called 'Service\Time' where we introduce a Now-Interface and an Implementation
that creates us a DateTimeImmutable object with the current date and time.

src/Service/Time/Now.php:
```php
namespace Lubian\NoFramework\Service\Time;

interface Now
{
    public function __invoke(): \DateTimeImmutable;
}
```
src/Service/Time/SystemClockNow.php:
```php
namespace Lubian\NoFramework\Service\Time;

final class SystemClockNow implements Now
{

    public function __invoke(): \DateTimeImmutable
    {
        return new \DateTimeImmutable;
    }
}
```
If we want to use that Service in our HelloAction we just need to add it as another argument for the Constructor and
update the handle-method to use the new class property:

```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Lubian\NoFramework\Service\Time\SystemClockNow;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Hello implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private SystemClockNow $now,
    )
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $name = $request->getAttribute('name', 'Stranger');
        $nowAsString = ($this->now)()->format('H:i:s');
        $body = $this->response->getBody();

        $body->write('Hello ' . $name . '!');
        $body->write(' The Time is ' . $nowAsString);

        return $this->response
            ->withBody($body)
            ->withStatus(200);
    }
}
```

If you open the route in your browser you should see that the current time gets displayed. This happens because PHP-DI
automatically figures out what classes are requested in the constructor and tries to create the objects needed.

But we do not want to depend on the SystemClockNow implementation in our class because that would violate our sacred
S.O.L.I.D. principles therefore we need to change the Typehint to the Now interface:

```php
    public function __construct(
        private ResponseInterface $response,
        private Now $now,
    )
```

When we are now accessing the Handler in the Browser we get an Error because we have not defined which implementation
should be use to satisfy dependencies on the Now interface. So lets add that definition to our dependencies file:

```php
\Lubian\NoFramework\Service\Time\Now::class => fn () => new \Lubian\NoFramework\Service\Time\SystemClockNow(),
```

we could also use the PHP-DI create method to delegate the object creation to the container implementation:
```php
\Lubian\NoFramework\Service\Time\Now::class => DI\create(\Lubian\NoFramework\Service\Time\SystemClockNow::class),
```

this way the container can try to resolve any dependencies that the class might have internally, but prefer the other
method because we are not depending on this specific dependency injection implementation.

Either way the container should now be able to correctly resolve the dependency on the Now interfacen when you are
requesting the Hello action.

If you run phpstan now, you will get some errors, because the get method on the ContainerInterface returns 'mixed'. As
we will adress these issues later, lets tell phpstan that we know about the issue and we can ignore it for now. This way
we wont get any warnings for this particular issue, but for any other issues we add to our code.

Update the phpstan.neon file to include a "baseline" file:

```
includes:
    - phpstan-baseline.neon

parameters:
    level: 9
    paths:
        - src
```

if we run phpstan with './vendor/bin/phpstan analyse --generate-baseline' it will add all current errors to that file and
ignore them in the future. You can also add that command to your composer.json for easier access. I have called it just
'baseline'

[<< previous](08-inversion-of-control.md) | [next >>](10-invoker.md)