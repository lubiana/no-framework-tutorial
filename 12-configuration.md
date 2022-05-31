[<< previous](11-templating.md) | [next >>](13-refactoring.md)

### Configuration

In the last chapter we added some more definitions to our dependencies.php in that definitions
we needed to pass quite a few configuration settings and filesystem strings to the constructors
of the classes. This might work for a small projects, but if we are growing we want to source that out to a more
explicit file that holds all the configuration values for our project.

As this is not a problem unique to our project there are already a some options available. Some projects use
[.env](https://github.com/vlucas/phpdotenv) files, others use
[.ini](https://www.php.net/manual/de/function.parse-ini-file.php), there is
[yaml](https://www.php.net/manual/de/function.yaml-parse-file.php) as well some frameworks have implemented complex
Readers for many configuration file formats that can be used, take a look at the
[laminas config component](https://docs.laminas.dev/laminas-config/reader/) for example.

As I am a big fan of writing everything in php, which gives our IDE the chance to autocomplete our code better I am
quite happy that PHP8 gives us some tools to achieve easy to use configuration via php. You can take a look at
[this blogpost](https://stitcher.io/blog/what-about-config-builders) to read about some considerations on that topic
before moving on.

For the purpose of this Tutorial I will use a simple ValueObject that has all our configuration values as properties.
create a `Configuration.php` class in the `./src` folder:

```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework;

final class Configuration
{
    public function __construct(
        public readonly string $environment = 'dev',
        public readonly string $routesFile = __DIR__ . '/../config/routes.php',
        public readonly string $templateDir = __DIR__ . '/../templates',
        public readonly string $templateExtension = '.html',
    ) {
    }
}
```

I am using a new Feature from PHP 8.1 here called 
[readonly properties](https://stitcher.io/blog/php-81-readonly-properties) to write a small valueobject without the need
to write complex getters and setters. The linked article gives a great explanation on how they work. You can see, that
I have added working default values for every configuration parameter. In my personal opinion, project should always
have working default values without you needing to set up anything. This greatly improves usability and reduces errors.

We can now update our `container.php` file to use the configuration. Currently, the Mustache_Loader, as well as the 
Fastroute-Dispatcher use values that we have defined in our Configuration, lets update those definitions:

```php
    Dispatcher::class => fn (Configuration $c) => simpleDispatcher(require $c->routesFile),
    Mustache_Loader_FilesystemLoader::class => fn (Configuration $c) => new Mustache_Loader_FilesystemLoader(
        $c->templateDir,
        [
            'extension' => $c->templateExtension,
        ]
    ),
```

Magically this is all we need to do, as the PHP-DI container knows that all constructor parameters of our configuration
class have default values and can create the needed object on its own.

There is one small problem: If we want to change environment from `dev` to `prod` we would need to update the
configuration class in the src directory. This is something we don't want to do on every deployment. So lets add a file
in our `./config` directory called `settings.php` that returns a Configuration object.
```php
<?php declare(strict_types=1);

return new \Lubian\NoFramework\Configuration(
    environment: 'prod',
);
```

here I am using a new feature called [named arguments](https://stitcher.io/blog/php-8-named-arguments). There is 
a lot of discussion on the topic of named arguments as some argue it creates unclean and
unmaintainable code, but for simple value-objects I would argue that they are ok.

We now need to add a line to our container to use the `settings.php` file to create the Configuration-object:

```php
    \Lubian\NoFramework\Configuration::class => fn () => require __DIR__ . '/settings.php',
```

One small oversight to fix is in the registration of our error-handler in the bootstrap-file. There we read the
environment with the getenv-method. Lets change the line:
```php
$environment = getenv('ENVIRONMENT') ?: 'dev';
```

to:
```php
$config = require __DIR__ . '/../config/settings.php';
assert($config instanceof \Lubian\NoFramework\Configuration);
$environment = $config->environment;
```

Check if everything still works, run your code quality checks and commit the changes before moving on the next chapter.
You might notice that phpstan throws an error as there is a documented violation missing. You can either regenerate the
baseline, or simply remove that line from the `phpstan-baseline.neon` file.

[<< previous](11-templating.md) | [next >>](13-refactoring.md)
