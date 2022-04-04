[<< previous](10-invoker.md) | [next >>](12-configuration.md)

### Templating

A template engine is not necessary with PHP because the language itself can take care of that. But it can make things
like escaping values easier. They also make it easier to draw a clear line between your application logic and the
template files which should only put your variables into the HTML code.

A good quick read on this is [ircmaxell on templating](http://blog.ircmaxell.com/2012/12/on-templating.html). Please
also read [this](http://chadminick.com/articles/simple-php-template-engine.html) for a different opinion on the topic.
Personally I don't have a strong opinion on the topic, so decide yourself which approach works better for you.

For this tutorial we will use a PHP implementation of [Mustache](https://github.com/bobthecow/mustache.php). So install
that package before you continue (`composer require mustache/mustache`).

Another well known alternative would be [Twig](http://twig.sensiolabs.org/).

Now please go and have a look at the source code of the
[engine class](https://github.com/bobthecow/mustache.php/blob/master/src/Mustache/Engine.php). As you can see, the class
does not implement an interface.

You could just type hint against the concrete class. But the problem with this approach is that you create tight
coupling.

In other words, all your code that uses the engine will be coupled to this mustache package. If you want to change the
implementation you have a problem. Maybe you want to switch to Twig, maybe you want to write your own class or you want
to add functionality to the engine. You can't do that without going back and changing all your code that is tightly
coupled.

What we want is loose coupling. We will type hint against an interface and not a class/implementation. So if you need
another implementation, you just implement that interface in your new class and inject the new class instead. 

Instead of editing the code of the package we will use the [adapter pattern](http://en.wikipedia.org/wiki/Adapter_pattern).
This sounds a lot more complicated than it is, so just follow along.

First let's define the interface that we want. Remember the [interface segregation principle](http://en.wikipedia.org/wiki/Interface_segregation_principle). 
This means that instead of large interfaces with a lot of methods we want to make each interface as small as possible.
A class can implement multiple interfaces if necessary.

So what does our template engine actually need to do? For now we really just need a simple `render` method. Create a 
new folder in your `src/` folder with the name `Template` where you can put all the template related things.

In there create a new interface `Renderer.php` that looks like this:

```php
<?php declare(strict_types = 1);

namespace Lubian\NoFramework\Template;

interface Renderer
{
    /**
     * @param string $template
     * @param array<string, mixed> $data
     * @return string
     */
    public function render(string $template, array $data = []) : string;
}
```

Now that this is sorted out, let's create the implementation for mustache. In the same folder, create the file
`MustacheRenderer.php` with the following content:

```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Template;

final class MustacheRenderer implements Renderer
{
    public function __construct(private \Mustache_Engine $engine){}

    public function render(string $template, array $data = []): string
    {
        return $this->engine->render($template, $data);
    }
}
```

As you can see the adapter is really simple. While the original class had a lot of methods, our adapter is really simple
and only fulfills the interface.

Of course we also have to add a definition in our `dependencies.php` file because otherwise the container won't know
which implementation he has to inject when you hint for the interface. Add this line:

```php
[
   ...
   \Lubian\NoFramework\Template\Renderer::class => DI\create(\Lubian\NoFramework\Template\MustacheRenderer::class)
        ->constructor(new Mustache_Engine),
]
```

Now update the Hello.php class to require an implementation of our renderer interface
and use that to render a string using mustache syntax.


```php
final class Hello
{
    public function __invoke(
        ResponseInterface $response,
        Now $now,
        Renderer $renderer,
        string $name = 'Stranger',
    ): ResponseInterface {
        $body = $response->getBody();
        $data = [
            'now' => $now()->format('H:i:s'),
            'name' => $name,
        ];

        $content = $renderer->render(
            'Hello {{name}}, the time is {{now}}!',
            $data,
        );

        $body->write($content);

        return $response
            ->withStatus(200)
            ->withBody($body);
    }
}
```

Now go check quickly in your browser if everything works. By default Mustache uses a simple string handler.
But what we want is template files, so let's go back and change that.

To make this change we need to pass an options array to the `Mustache_Engine` constructor. So let's go back to the 
`dependencies.php` file and add the following code:

```php
[
    ...
    Mustache_Loader_FilesystemLoader::class => fn() => new Mustache_Loader_FilesystemLoader(__DIR__ . '/../templates', ['extension' => '.html']),
    Mustache_Engine::class => fn (Mustache_Loader_FilesystemLoader $MLFsl) => new Mustache_Engine(['loader' => $MLFsl]),
]
```

We are passing an options array because we want to use the `.html` extension instead of the default `.mustache` extension.
Why? Other template languages use a similar syntax and if we ever decide to change to something else then we won't have
to rename all the template files.

To let PHP-DI use its magic for creating our MustacheRenderer class we need to tell it exactly how to wire all the
dependencies, therefore I defined how to create the Filesystemloader, on the next line we typehinted that loader
in the short closure which acts as a factory method for the Mustache_Engine, as PHP-DI automatically injects the Object
we can then use it in the factory.

In your project root folder, create a `templates` folder. In there, create a file `hello.html`. The content of the file should look like this:

```
<h1>Hello World</h1>
Hello {{ name }}
```

Now you can go back to your `Hello` action and change the render line to `$html = $this->renderer->render('hello', $data);`

Navigate to the hello page in your browser to make sure everything works.

One thing that still bothers me is the fact that we have some configuration paths scattered in our dependencies
file. We could add a simple valueobject to our code that gives us a typesafe access to our configuration
values.

Lets create a 'Settings' class in our './src' Folder:

```php
<?php

declare(strict_types=1);

namespace Lubian\NoFramework;

final class Settings
{
    public function __construct(
        public readonly string $environment,
        public readonly string $templateDir,
        public readonly string $templateExtension,
    ){}
}
```

I am using a new Feature from PHP 8.1 here called [readonly properties](https://stitcher.io/blog/php-81-readonly-properties) to write a small valueobject without the need to write complex getters and setters. The linked article gives a great explanation on how they work.

When creating an instance of the setting class with my project specific values i will use another
new feature called [named arguments](https://stitcher.io/blog/php-8-named-arguments). There is 
a lot of discussion on the topic of named arguments as some argue it creates unclean and
unmaintainable code, but vor simple valueobjects i would argue that they are ok.

here is a small example of creating a settings object using named arguments.
```php
    $setting = new Settings(
        environment: getenv('ENVIRONMENT') ?: 'dev',
        templateDir: getenv('TEMPLATE_DIR') ?: __DIR__ . '/../templates',
        templateExtension: getenv('TEMPLATE_EXTENSION') ?: '.html',
    );
```

lets put that code in a file called settings.php in our config folder, and return the settings object
from there

Here is my updated dependencies.php with some imports and aliases added for my convenience:

```php
<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Lubian\NoFramework\Settings;
use Lubian\NoFramework\Template\Mustache;
use Lubian\NoFramework\Template\Renderer;
use Mustache_Engine as ME;
use Mustache_Loader_FilesystemLoader as MLFsl;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function DI\create;

$builder = new ContainerBuilder();
$builder->addDefinitions([
    Settings::class => fn () => require __DIR__ '/settings.php',
    ResponseInterface::class => create(Response::class),
    ServerRequestInterface::class => fn () => ServerRequestFactory::fromGlobals(),
    Renderer::class => fn (ME $me) => new Mustache($me),
    MLFsl::class => fn (Settings $s) => new MLFsl($s->templateDir, ['extension' => $s->templateExtension]),
    ME::class => fn (MLFsl $MLFsl) => new ME(['loader' => $MLFsl]),
]);

return $builder->build();
```



And as always, don't forget to commit your changes.


[<< previous](10-invoker.md) | [next >>](12-configuration.md)
