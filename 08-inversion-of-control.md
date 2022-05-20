[<< previous](07-dispatching-to-a-class.md) | [next >>](09-dependency-injector.md)

### Inversion of Control

In the last part you have set up a controller class and generated our Http-Response-object in that class, but if we
want to switch to a more powerfull Http-Implementation later, or need to create our own for some special purposes, then
we would need to edit every one of our request handlers to call a different constructor of the class.

The sane option is to use [inversion of control](http://en.wikipedia.org/wiki/Inversion_of_control). This means that
instead of giving the class the responsibility of creating the object it needs, you just ask for them. This is done
with [dependency injection](http://en.wikipedia.org/wiki/Dependency_injection).

If this sounds a little complicated right now, don't worry. Just follow the tutorial and once you see how it is
implemented, it will make sense.

Change your `Hello` action to the following:

```php
<?php declare(strict_types = 1);

namespace Lubian\NoFramework\Action;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

final class Hello implements \Psr\Http\Server\RequestHandlerInterface;
{
    public function __construct(
        private Response $response
    ) {}
    public function handle(Request $request): Response
    {
        $name = $request->getAttribute('name', 'Stranger');
        $body = $this->response->getBody();
        
        $body->write('Hello ' . $name . '!');
        
        return $this->response
            ->withBody($body)
            ->withStatus(200);
    }
}
```

Now the code will result in an error because we are not actually injecting anything. So let's fix that in the `Bootstrap.php` where we dispatch when a route was found:

```php
$handler = new $className($response);
```

Of course we need to also update all the other handlers.

[<< previous](07-dispatching-to-a-class.md) | [next >>](09-dependency-injector.md)
