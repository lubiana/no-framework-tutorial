[<< previous](09-dependency-injector.md) | [next >>](11-templating.md)

### Invoker

Currently, all our Actions need to implement the RequestHandlerInterface, which forces us to accept the Request as the
one and only argument to our handle function, but most of the time we only need a few attributes in our Action a long
with some services and not the whole Request object with all its various properties.

If we take our Hello action for example we only need a response object, the clock service and the 'name' information from
the request-uri. And as that class only provides one simple method we could easily make that invokable as we already named
the class hello, and it would be redundant to also call the method hello. So an updated version of that class could
look like this:

```php
final class Hello
{
    public function __invoke(
        ResponseInterface $response,
        Clock $clock,
        string $name = 'Stranger'
    ): ResponseInterface
    {
        $body = $response->getBody();

        $body->write('Hello ' . $name . '!<br />');
        $body->write('The time is: ' . $clock->now()->format('H:i:s'));

        return $response->withBody($body)
            ->withStatus(200);
    }
}
```

It would also be neat if we could define a classname plus a method as target handler in our routes, or even a short
closure function if we want to redirect all requests from '/' to '/hello' because we have not defined a handler for the 
root path of our application yet.

```php
$r->addRoute('GET', '/hello[/{name}]', Hello::class);
$r->addRoute('GET', '/other-route', [Other::class, 'handle']);
$r->addRoute('GET', '/', fn (Response $r) => $r->withStatus(302)->withHeader('Location', '/hello'));
```

In order to support this crazy route definitions we would need to write a lot of code for actually calling the result of
the route dispatcher. If the result is a name of an invokable class we would use the container to create an instance of
that class for us and then use the [reflection api](https://www.php.net/manual/en/book.reflection.php) to figure out what
arguments the __invoke function has, try to fetch all arguments from the container and then add some more from the router
if they are needed and available. The same if we have an array of a class name with a function to call, and for a simple
callable we would need to manually use reflection as well to resolve all the arguments.

But we are quite lucky as the PHP-DI container provides us with a [great 'call' method](https://php-di.org/doc/container.html#call)
which handles all of that for us.

After you added the described changes to your routes file you can modify the Dispatcher::FOUND case of you $routeInfo
switch section in the Bootstrap.php file to use the container->call() method:

```php
$handler = $routeInfo[1];
$args = $routeInfo[2];
foreach ($routeInfo[2] as $attributeName => $attributeValue) {
    $request = $request->withAttribute($attributeName, $attributeValue);
}
$args['request'] = $request;
$response = $container->call($handler, $args);
```

Try to open [localhost:1235/](http://localhost:1235/) in your browser and check if you are getting redirected to '/hello'.

But by now you should know that I do not like to depend on specific implementations and the call method is not defined in
the psr/container interface. Therefore, we would not be able to use that if we are ever switching to the symfony container
or any other implementation.

Fortunately for us (or me) the PHP-CI container ships that function as its own class that is independent of the specific
container implementation, so we could use it with any container that implements the ContainerInterface. And best of all
the class ships with its own [Interface](https://github.com/PHP-DI/Invoker/blob/master/src/InvokerInterface.php) that
we could implement if we ever want to write our own implementation, or we could write an adapter that uses a different
class that solves the same problem.

But for now we are using the solution provided by PHP-DI.
So lets request a Service implementing the InvokerInterface from the container and use that inside of the switch-case block

```php
$handler = $routeInfo[1];
$args = $routeInfo[2] ?? [];
foreach ($routeInfo[2] as $attributeName => $attributeValue) {
    $request = $request->withAttribute($attributeName, $attributeValue);
}
$args['request'] = $request;
$invoker = $container->get(InvokerInterface::class);
assert($invoker instanceof InvokerInterface);
$response = $invoker->call($handler, $args);
assert($response instanceof ResponseInterface);
```

Now we are able to define absolutely everything in routes that is considered a [callable](https://www.php.net/manual/de/language.types.callable.php)
by php, and even some more.

But let us move on to something more fun and add some templating functionality to our application as we are trying to build
a website in the end.

[<< previous](09-dependency-injector.md) | [next >>](11-templating.md)
