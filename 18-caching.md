[<< previous](17-performance.md) | [next >>](19-database.md)

**DISClAIMER** I do not really have a lot of experience when it comes to caching, so this chapter is mostly some random
thoughts and ideas I wanted to explore when writing this tutorial, you should definitely take everything that is being
said here with caution and try to read up on some other sources. But that holds true for the whole tutorial anyway :)

## Caching

In the last chapter we greatly improved the perfomance for the lookup of all our classfiles, but currently we do not
have any real bottlenecks in our application like complex queries.

But in a real application we are going to execute some really heavy and time intensive database queries that can take
quite a while to be completed.

We can simulate that by adding a simple delay in our `FileSystemMarkdownPageRepo`.

```php
 return array_map(function (string $filename) {
        usleep(rand(100, 400) * 1000);
        $content = file_get_contents($filename);
        if ($content === false) {
            throw new InternalServerError('cannot read pages');
        }
        $idAndTitle = str_replace([$this->dataPath, '.md'], ['', ''], $filename);
        return new MarkdownPage(
            (int) substr($idAndTitle, 0, 2),
            substr($idAndTitle, 3),
            $content
        );
});
```

Here I added a function that pauses the scripts execution for a random time between 100 and 400ms for every markdownpage
in every call of the `all()` method. 

If you open any page or even the listAction in you browser you will see, that it takes quite a time to render that page.
Although this is a silly example we do not really need to query the database on every request, so lets add a way to cache
the database results between requests.

The PHP-Community has already adressed the issue of having easy to use access to cache libraries, there is the 
[PSR-6 Caching Interface](https://www.php-fig.org/psr/psr-6) which gives us easy access to many different implementations,
then there is also a much simpler [PSR-16 Simple Cache](https://www.php-fig.org/psr/psr-16) which makes the use even more
easy, and most Caching Libraries implement Both interfaces anyway. You would think that this is more than enough solutions
to satisfy all the Caching needs around, but the Symfony People decided that Caching should be even simpler and easier 
to use and defined their own [Interface](https://symfony.com/doc/current/components/cache.html#cache-component-contracts)
which only needs two methods. You should definitely take a look at the linked documentation as it really blew my mind
when I first encountered it.

The basic idea is that you provide a callback that computes the requested value. The Cache implementation then checks
if it already has the value stored somewhere and if it doesnt it just executes the callback and stores the value for
future calls.

It is really simple and great to use. In a real world application you should definitely use that or a PSR-16 implementation
but for this tutorial I wanted to roll out my own solution, so here we go.

As always we are going to define an interface first, I am going to call it EasyCache and place it in the `Service/Cache`
namespace. I will require only one method which is base on the Symfony Cache Contract, and hast a key, a callback, and
the duration that the item should be cached as arguments.

```php
<?php

declare(strict_types=1);

namespace Lubian\NoFramework\Service\Cache;

interface EasyCache
{
    /** @param callable(): mixed $callback */
    public function get(string $key, callable $callback, int $ttl = 0): mixed;
}
```

For the implementation I am going to use the [APCu Extension](https://www.php.net/manual/en/ref.apcu.php) for PHP, but
if you are particularly adventurous you can write an implementation using memcache, the filesystem, a database, redis
or whatever you desire.

For the sake of writing as less code as possible here is my simple `ApcuCache.php`
```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Service\Cache;

use function apcu_add;
use function apcu_fetch;

final class ApcuCache implements EasyCache
{
    public function get(string $key, callable $callback, int $ttl = 0): mixed
    {
        $success = false;
        $result = apcu_fetch($key, $success);
        if ($success === true) {
            return $result;
        }
        $result = $callback();
        apcu_add($key, $result, $ttl);
        return $result;
    }
}
```

Now that we have a usable implementation for our cache we can write an implementation of our `MarkdownPageRepo` interface
that usese the Cache and a Repository implementation to speed up the time exepensive calls.

So lets create a new class called `CachedMarkdownPageRepo`:

```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Repository;

use Lubian\NoFramework\Model\MarkdownPage;
use Lubian\NoFramework\Service\Cache\EasyCache;

use function base64_encode;

final class CachedMarkdownPageRepo implements MarkdownPageRepo
{
    public function __construct(
        private EasyCache $cache,
        private MarkdownPageRepo $repo,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        $key = base64_encode(self::class . 'all');
        return $this->cache->get(
            $key,
            fn () => $this->repo->all(),
            300
        );
    }

    public function byName(string $name): MarkdownPage
    {
        $key = base64_encode(self::class . 'byName' . $name);
        return $this->cache->get(
            $key,
            fn () => $this->repo->byName($name),
            300
        );
    }
}
```

This simple wrapper just requires an EasyCache implementation and a MarkdownPageRepo in the constructor and uses them
to cache all queries for 5 minutes. The beauty is that we are not dependent on any implementation here, so we can switch
out the Repository or the Cache at any point down the road if we want to.

In order to use that we need to update our `config/dependencies.php` to add an alias for the EasyCache interface as well
as defining our CachedMarkdownPageRepo as implementation for the MarkdownPageRepo interface:

```php
MarkdownPageRepo::class => fn (CachedMarkdownPageRepo $r) => $r,
EasyCache::class => fn (ApcuCache $c) => $c,
```

If we try to access our webpage now, we are getting an error, as PHP-DI has detected a circular dependency that cannot
be autowired.

The Problem is that our CachedMarkdownPageRepo ist defined as the implementation for the MarkdownPageRepo, but it also
requires that exact interface as a dependency. To resolve this issue we need to manually tell the container how to build
the CachedMarkdownPageRepo by adding another line to the `config/dependencies.php` file:

```php
CachedMarkdownPageRepo::class => fn (EasyCache $c, FileSystemMarkdownPageRepo $r) => new CachedMarkdownPageRepo($c, $r),
```

Here we explicitly require the FileSystemMarkdownPageRepo and us that to create the CachedMarkdownPageRepo object.

When you now navigate to the pages list or to a specific page the first load should take a while (because of our added delay)
but the following request should be answered blazingly fast.

Before moving on to the next chapter we can take the caching approach even further, in the middleware chapter I talked
about a simple CachingMiddleware that caches all the GET-Request for some seconds, as they should not change that often,
and we can bypass most of our application logic if we just complelety cache away the responses our application generates,
and return them quite early in our Middleware-Pipeline befor the router gets called, or the invoker calls the action,
which itself uses some other services to fetch all the needed data.

We will introduce a new `Middleware` namespace to place our `Cache.php` middleware:
```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Middleware;

use Laminas\Diactoros\Response\Serializer;
use Lubian\NoFramework\Service\Cache\EasyCache;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function base64_encode;

final class Cache implements MiddlewareInterface
{
    public function __construct(
        private readonly EasyCache $cache,
        private readonly Serializer $serializer,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() !== 'GET') {
            return $handler->handle($request);
        }
        $keyHash = base64_encode($request->getUri()->getPath());
        $result = $this->cache->get(
            $keyHash,
            fn () => $this->serializer::toString($handler->handle($request)),
            300
        );
        return $this->serializer::fromString($result);
    }
}
```

The code is quite straight forward, but you might be confused by the Responseserializer I have added here, we need this
because the response body is a stream object, which doesnt always gets serialized correctly, therefore I use a class from
the laminas project to to all the heavy lifting for us. 

We need to add the now middleware to the `config/middlewares.php` file.

```php
<?php declare(strict_types=1);

use Lubian\NoFramework\Http\RouteMiddleware;
use Lubian\NoFramework\Middleware\Cache;
use Middlewares\TrailingSlash;
use Middlewares\Whoops;

return [
    Whoops::class,
    Cache::class,
    TrailingSlash::class,
    RouteMiddleware::class,
];
```

You can now use your browser to look if everything works as expected.

**Disclaimer** in a real application you would take some more consideration when it comes to caching and this simple
response cache would quickly get in you way, but as I said earlier this chapter was mostly me playing around with some
ideas I had in writing this tutorial.

[<< previous](17-performance.md) | [next >>](19-database.md)
