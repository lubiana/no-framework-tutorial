[<< previous](08-inversion-of-control.md) | [next >>](10-invoker.md)

### Dependency Injector

In the last chapter we rewrote our Actions to require the response-objet as a constructor parameter, and provided it
in the dispatcher section of our `Bootstrap.php`. As we only have one dependency this works really fine, but if we have
different classes with different dependencies our bootstrap file gets complicated quite quickly. Let's look at an example
to explain the problem and work on a solution.

#### Adding a clock service

Lets assume that we want to show the current time in our Hello action. We could easily just call use one of the many
ways to get the current time directly in the handle-method, but let's create a separate class and interface for that so
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

The Clock interface is modelled after the [proposed clock interface psr](https://github.com/php-fig/fig-standards/blob/master/proposed/clock.md)
which may or may not one day be accepted as an official standard.

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

Now we can require the Clock interface as a dependency in our controller and use it to display the current time.
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

But if we try to access the corresponding route in the browser we get an error:
> Too few arguments to function Lubian\NoFramework\Action\Hello::__construct(), 1 passed in /home/lubiana/PhpstormProjects/no-framework/app/src/Bootstrap.php on line 62 and exactly 2 expected

Our current problem is, that we have two Actions defined, which both have different constructor requirements. That means,
that we need to have some code in our Application, that creates our Action Objects and takes care of injection all the
needed dependencies.

This code is called a Dependency Injector. If you want you can read [this](https://afilina.com/learn/design/dependency-injection)
great blogpost about that topic, which I highly recommend.

Lets build our own Dependency Injector to make our application work again.

As a starting point we are going to take a look at the [Container Interface](https://www.php-fig.org/psr/psr-11/) that
is widely adopted in the PHP-World.

#### Building a dependency container

**Short Disclaimer:** *Although it would be fun to write our own great implementation of this interface with everything that
is needed for modern php development I will take a shortcut here and implement very reduced version to show you the
basic concept.*

The `Psr\Container\ContainerIterface` defines two methods:

* has($id): bool
    returns true if the container can provide a value for a given ID
* get($id): mixed
    returns some kind of value that is registered in the container for the given ID

I mostly define an Interface or a fully qualified classname as an ID. That way I can query the container for
the Clock interface or an Action class and get an object of that class or an object implementing the given Interface.

For the sake of this tutorial we will put a new file in our config folder that returns an anonymous class implementing
the container-interface.

In this class we will configure all services required for our application and make them accessible via the get($id)
method.

Before we can implement the interface we need to install its definition with composer `composer require "psr/container:^1.0"`.
now we can create a file with a Class that implements that interface.

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

Here I have declared a services array, that has a class- or interface name as the keys, and the values are short
closures that return an Object of the defined class or interface. The `has` method simply checks if the given id is
defined in our services array, and the `get` method calls the closure defined in the array for the given id key and then
returns the result of that closure.

To use the container we need to update our Bootstrap.php. Firstly we need to get an instance of our container, and then
use that to create our Request-Object as well as the Dispatcher. So remove the manual instantiation of those objects and
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

#### Using Auto wiring

If you take a critical look at the services array you might see that we need to manually define how our Hello- and
Other-Action are getting constructed. This is quite repetitive, as we have already declared what objects to create
when asking for the ResponseInterface and the Clock-Interface. We would need to write way less code, if our Container
was smart enough to automatically figure our which services to Inject by looking at the constructor of a class.

PHP provides us with the great Reflection Api that is capable of showing us, [what arguments a constructor of any
given class requires](https://www.php.net/manual/de/reflectionclass.getconstructor.php). We could implement that
functionality ourselves, or just try to use a library that takes care of that for us.

You can query the composer database to find all [libraries that implement the container interface](https://packagist.org/providers/psr/container-implementation).

I choose the [PHP-DI](https://packagist.org/packages/php-di/php-di) container, as it is easy to configure and provides some very [powerfull features](https://php-di.org/#autowiring) 
out of the box, and also solves the auto wiring problem.

Let's rewrite our `container.php` file to use the PHP-DI container and only define the Services the Container cannot
automatically build.

```php
<?php declare(strict_types=1);

$builder = new \DI\ContainerBuilder;

$builder->addDefinitions([
    \Psr\Http\Message\ServerRequestInterface::class => fn () => \Laminas\Diactoros\ServerRequestFactory::fromGlobals(),
    \Psr\Http\Message\ResponseInterface::class => fn () => new \Laminas\Diactoros\Response(),
    \FastRoute\Dispatcher::class => fn () => \FastRoute\simpleDispatcher(require __DIR__ . '/routes.php'),
    \Lubian\NoFramework\Service\Time\Clock::class => fn () => new \Lubian\NoFramework\Service\Time\SystemClock(),
]);

return $builder->build();
```

As the PHP-DI container that is return by the `$builder->build()` method implements the same container interface as our
previously used ad-hoc container we won't need to update the Bootstrap file and everything still works.


[<< previous](08-inversion-of-control.md) | [next >>](10-invoker.md)