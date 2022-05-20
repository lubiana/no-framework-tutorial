[<< previous](04-development-helpers.md) | [next >>](06-router.md)

### HTTP

PHP already has a few things built in to make working with HTTP easier. For example there are the
[superglobals](http://php.net/manual/en/language.variables.superglobals.php) that contain the request information.

These are good if you just want to get a small script up and running, something that won't be hard to maintain. However,
if you want to write clean, maintainable, [SOLID](http://en.wikipedia.org/wiki/SOLID_%28object-oriented_design%29) code,
then you will want a class with a nice object-oriented interface that you can use in your application instead.

Fortunately for us there has been a standard developed in the PHP-Community that is adopted by several Frameworks. The
standard is called [PSR-7](https://www.php-fig.org/psr/psr-7/) and has several interfaces defined that a lot of php
projects implement. This makes it easier for us to use modules developed for other frameworks in our projects.

As this is a widely adopted standard there are already several implementations available for us to use. I will choose
the laminas/laminas-diactoros package as i am an old time fan of the laminas (previously zend) project.

Some alternatives are [slim-psr7](https://github.com/slimphp/Slim-Psr7), [Guzzle](https://github.com/guzzle/psr7) and a
[lot more](https://packagist.org/providers/psr/http-message-implementation) are available for you to choose from.

Symfony ships its own Request and Response objects that do not implement the psr-7 interfaces. Therefore, I will not use
that in this tutorial, but if you understand how the psr-7 interfaces work you should have no problem in understanding
the [symfony http-foundation](https://symfony.com/doc/current/components/http_foundation.html#request).


to install the laminas psr-packages just type `composer require laminas/laminas-diactoros` into your console and hit
enter

Now you can add the following below your error handler code in your `Bootstrap.php` (and don't forget to remove the exception):

```php
$request = \Laminas\Diactoros\ServerRequestFactory::fromGlobals();
$response = new \Laminas\Diactoros\Response;
$response->getBody()->write('Hello World! ');
$response->getBody()->write('The Uri is: ' . $request->getUri()->getPath());
```

This sets up the `Request` and `Response` objects that you can use in your other classes to get request data and send a response back to the browser.

In order to actually add content to the response you have to access the body stream object of the Response and use the
write()-Method on that object.


To actually send something back, you will also need to add the following snippet at the end of your `Bootstrap.php` file:

```php
echo $response->getBody();
```

This will send the response data to the browser. If you don't do this, nothing happens as the `Response` object only
stores data. 

You can play around with the other methods of the Request object and take a look at its content with the dd() function.

```php
dd($response)
```

Something you have to keep in mind is that the Response and Request objects are Immutable which means that they cannot
be changed after creation. Whenever you want to modify a property you have to call one of the "with" functions, which
creates a copy of the request object with the changed property and returns that clone:

```php
$response = $response->withStatus(200);
$response = $response->withAddedHeader('Content-type', 'application/json');
```

If you have ever struggled with Mutation-problems in an DateTime-Object you might understand why the standard has been
defined this way.

But if you have been keeping attention you might argue that the following line should not work if the request object is
immutable.

```php
$response->getBody()->write('Hello World!');
```

The response-body implements a stream interface which is immutable for some reasons that are described in the 
[meta-document](https://www.php-fig.org/psr/psr-7/meta/#why-are-streams-mutable). For me the important thing is to be
aware of the problems that can occur with mutable objects. Here is a small [Blogpost](http://andrew.carterlunn.co.uk/programming/2016/05/22/psr-7-is-not-immutable.html) that gives some context. Beware that the Middleware-Example in 
the post is based on a deprecated middleware standard. But more on middlewares will be discussed in later chapters.
I, for one, am happy about that fact, as it saves me from writing at least 3 lines of code whenever i want to add content
to a response object.

```php
$body = $response->getBody();
$body->write('Hello World!');
$response = $response->withBody($body);
```

Right now we are just outputting the Response-Body without any headers or http-status. So we need to expand our
output-logic a little more. Replace the line that echos the response-body with the following:

```php
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

echo $response->getBody();
```

This code is still fairly simple and there is a lot more stuff that can be considered when emitting a response to a 
browser, if you want a more complete solution you can take a look at the [httpsoft/http-emitter](https://github.com/httpsoft/http-emitter/blob/master/src/SapiEmitter.php) package on github.

Remember that the object is only storing data, so if you set multiple status codes before you send the response, only the last one will be applied.

Be sure to run composer phpstan, composer fix and composer check before moving on to the next chapter


[<< previous](04-development-helpers.md) | [next >>](06-router.md)
