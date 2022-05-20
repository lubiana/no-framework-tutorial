[<< previous](06-router.md) | [next >>](08-inversion-of-control.md)

### Dispatching to a Class

In this tutorial we won't implement [MVC (Model-View-Controller)](http://martinfowler.com/eaaCatalog/modelViewController.html). 
MVC can't be implemented properly in PHP anyway, at least not in the way it was originally conceived. If you want to
learn more about this, read [A Beginner's Guide To MVC](http://blog.ircmaxell.com/2014/11/a-beginners-guide-to-mvc-for-web.html)
and the followup posts.

So forget about MVC and instead let's worry about [separation of concerns](http://en.wikipedia.org/wiki/Separation_of_concerns).

We will need a descriptive name for the classes that handle the requests. For this tutorial I will use `Handler`, other
common names are 'Controllers' or 'Actions'.

Create a new folder inside the `src/` folder with the name `Action`. In this folder we will place all our action classes.
In there, create a `Hello.php` file.

```php
<?php declare(strict_types = 1);

namespace Lubian\NoFramework\Action;

final class Hello implements \Psr\Http\Server\RequestHandlerInterface
{
    public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $name = $request->getAttribute('name', 'Stranger');
        $response = (new \Laminas\Diactoros\Response)->withStatus(200);
        $response->getBody()->write('Hello ' . $name . '!');
        return $response;
    }
}
```

You can see that we implement the [RequestHandlerInterface](https://github.com/php-fig/http-server-handler/blob/master/src/RequestHandlerInterface.php)
that has a 'handle'-Method with requires a Request object as its parameter and returns a Response-object. For now this is
fine, but we may have to change our approach later. In any way it is good to know about this interface as we will implement
it in some other parts of our application as well. In order to use that Interface we have to require it with composer:
`composer require psr/http-server-handler`.

The autoloader will only work if the namespace of a class matches the file path and the file name equals the class name. 
At the beginning I defined `Lubian\NoFramework` as the root namespace of the application so this is referring to the `src/` folder.

Now let's change the hello world route so that it calls your new class method instead of the closure. Change your `routes.php` to this:

```php
return function(\FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/hello[/{name}]', \Lubian\NoFramework\Action\Hello::class);
    $r->addRoute('GET', '/another-route', \Lubian\NoFramework\Action\Another::class);
};
```

Instead of a callable we are now passing the fully namespaced class identifier to the route-definition. I also declared
the class 'Another' as the target for the second route, you can create it by copying the Hello.php file and changing
the response to the one we defined for the second route.

To make this work, you will also have to do a small refactor to the routing part of the `Bootstrap.php`:

```php
case \FastRoute\Dispatcher::FOUND:
     $handler = new $routeInfo[1];
     if (! $handler instanceof \Psr\Http\Server\RequestHandlerInterface) {
        throw new \Exception('Invalid Requesthandler');
     }
     foreach ($routeInfo[2] as $attributeName => $attributeValue) {
        $request = $request->withAttribute($attributeName, $attributeValue);
     }
    $response = $handler->handle($request);
    assert($response instanceof \Psr\Http\Message\ResponseInterface)
    break;
```

So instead of just calling a method you are now instantiating an object and then calling the method on it.

Now if you visit `http://localhost:1235/` everything should work. If not, go back and debug.

And of course don't forget to commit your changes.

Something that still bothers me is the fact, that we do have classes for our Handlers, but the Error responses are still
generated in the routing-matching section and not in special classes. Also, we have still left some cases to chance, for
example if there is an error in creating our RequestHandler class or if the call to the 'handle' function fails. We still
have our whoopsie error-handler, but I like to be more explicit in my control flow.

In order to do that we need to define some special Exceptions that we can throw and catch explicitly. Lets add a new
Folder/Namespace to our src directory called Exceptions. And define the classes NotFound, MethodNotAllowed and
InternalServerError. All three should extend phps Base Exception class.

Here is my NotFound.php for example.

```php
<?php

declare(strict_types=1);

namespace Lubian\NoFramework\Exception;

final class NotFound extends Exception{}
```

Use that example to create a MethodNotAllowedException.php and InternalServerErrorException.php as well.

After you have created those we update our Router code to use the new Exceptions.

```php
try {
    switch ($routeInfo[0]) {
        case Dispatcher::FOUND:
            $className = $routeInfo[1];
            $handler = new $className;
            assert($handler instanceof RequestHandlerInterface);
            foreach ($routeInfo[2] as $attributeName => $attributeValue) {
                $request = $request->withAttribute($attributeName, $attributeValue);
            }
            $response = $handler->handle($request);
            break;
        case Dispatcher::METHOD_NOT_ALLOWED:
            throw new MethodNotAllowed;

        case Dispatcher::NOT_FOUND:
        default:
            throw new NotFound;
    }
} catch (MethodNotAllowed) {
    $response = (new Response)->withStatus(405);
    $response->getBody()->write('Not Allowed');
} catch (NotFound) {
    $response = (new Response)->withStatus(404);
    $response->getBody()->write('Not Found');
} catch (Throwable $t) {
    throw new InternalServerError($t->getMessage(), $t->getCode(), $t);
}
```

Check if our code still works, try to trigger some errors, run phpstan and the fix command
and don't forget to commit your changes.

[<< previous](06-router.md) | [next >>](08-inversion-of-control.md)
