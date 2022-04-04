[<< previous](11-templating.md) | [next >>](13-refactoring.md)

### Configuration

In the last chapter we added some more definitions to our dependencies.php in that definitions
we needed to pass quite a few configuration settings and filesystem strings to the constructors
of the classes. This might work for a small projects, but if we are growing we want to source that out to a more explicit file that holds all the configuration valuse for our project.

As this is not a problem unique to our project there are already a some options available. Some projects use [.env](https://github.com/vlucas/phpdotenv) files, others use [.ini](https://www.php.net/manual/de/function.parse-ini-file.php), there is [yaml](https://www.php.net/manual/de/function.yaml-parse-file.php) as well some frameworks have implemented complex Readers for many configuration file formats that can be used, take a look at the [laminas config component](https://docs.laminas.dev/laminas-config/reader/) for example.

As i am a big fan of writing everything in php, which gives our IDE the chance to autocomplete our code better I am quite happy the PHP8 gives us some tools to achieve easy to use configuration via php. You can take a look at [this blogpost](https://stitcher.io/blog/what-about-config-builders) to read about some considerations on that topic before moving on.

Lets create a 'Settings' class in our './src' Folder:

```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework;

final class Settings
{
    public function __construct(
        public readonly string $environment,
        public readonly string $dependenciesFile,
        public readonly string $templateDir,
        public readonly string $templateExtension,
    ) {
    }
}
```

I am using a new Feature from PHP 8.1 here called [readonly properties](https://stitcher.io/blog/php-81-readonly-properties) to write a small valueobject without the need to write complex getters and setters. The linked article gives a great explanation on how they work.

When creating an instance of the setting class with my project specific values i will use another
new feature called [named arguments](https://stitcher.io/blog/php-8-named-arguments). There is 
a lot of discussion on the topic of named arguments as some argue it creates unclean and
unmaintainable code, but vor simple valueobjects i would argue that they are ok.

here is a small example of creating a settings object using named arguments that I placed in the config folder
under the name settings.php
```php
<?php declare(strict_types=1);

use Lubian\NoFramework\Settings;

return new Settings(
    environment: 'dev',
    dependenciesFile: __DIR__ . '/dependencies.php',
    templateDir: __DIR__ . '/../templates',
    templateExtension: '.html',
);
```


But now we need some more code to include that settings Object and make it available in our container. As I don't want to use requires and includes too much in the dependencies configuration we are going to create a Factory class that gives us an Instance of the config file.

Lets define our Interface first. That way we can later switch to another implementation that creates our Settings object.

src/Factory/SettingsProvider.php:

```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Factory;

use Lubian\NoFramework\Settings;

interface SettingsProvider
{
    public function getSettings(): Settings;
}
```

And write a simple implementation that uses our settings.php to provide our App with the Settingsobject:

```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Factory;

use Lubian\NoFramework\Settings;

final class FileSystemSettingsProvider implements SettingsProvider
{
    public function __construct(
        private string $filePath
    ) {
    }

    public function getSettings(): Settings
    {
        return require $this->filePath;
    }
}
```

If we later want to use yaml or ini files for our Settings we can easily write a different provider to read those files
and craft a settings object from them.

As we have now created a completely new Namespace and Folder and our SettingsProvider is all alone we could add another
factory for our Container because everyone should have a Friend :)

```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Factory;

use Psr\Container\ContainerInterface;

interface ContainerProvider
{
    public function getContainer(): ContainerInterface;
}
```

And a simple implementation that uses our new Settingsprovider to build the container:

```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Factory;

use DI\ContainerBuilder;
use Lubian\NoFramework\Settings;
use Psr\Container\ContainerInterface;

final class SettingsContainerProvider implements ContainerProvider
{
    public function __construct(
        private SettingsProvider $settingsProvider,
    ) {
    }

    public function getContainer(): ContainerInterface
    {
        $builder = new ContainerBuilder;
        $settings = $this->settingsProvider->getSettings();
        $dependencies = require $settings->dependenciesFile;
        $dependencies[Settings::class] = fn () => $settings;
        $builder->addDefinitions($dependencies);
        return $builder->build();
    }
}
```

For this to work we need to change our dependencies.php file to just return the array of definitions:
And here we can instantly use the Settings object to create our template engine.

```php
<?php declare(strict_types=1);

use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Lubian\NoFramework\Service\Time\Now;
use Lubian\NoFramework\Service\Time\SystemClockNow;
use Lubian\NoFramework\Settings;
use Lubian\NoFramework\Template\MustacheRenderer;
use Lubian\NoFramework\Template\Renderer;
use Mustache_Engine as ME;
use Mustache_Loader_FilesystemLoader as MLF;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

return [
    ResponseInterface::class => fn (ResponseFactory $rf) => $rf->createResponse(),
    ServerRequestInterface::class => fn (ServerRequestFactory $rf) => $rf::fromGlobals(),
    Now::class => fn (SystemClockNow $n) => $n,
    Renderer::class => fn (Mustache_Engine $e) => new MustacheRenderer($e),
    MLF::class => fn (Settings $s) => new MLF($s->templateDir, ['extension' => $s->templateExtension]),
    ME::class => fn (MLF $mfl) => new ME(['loader' => $mfl]),
];
```

Now we can change our Bootstrap.php file to use the new Factories for the creation of the Initial Objects:
require __DIR__ . '/../vendor/autoload.php';

```php
...
error_reporting(E_ALL);

$settingsProvider = new FileSystemSettingsProvider(__DIR__ . '/../config/settings.php');
$container = (new SettingsContainerProvider($settingsProvider))->getContainer();

$settings = $settingsProvider->getSettings();

$whoops = new Run;
if ($settings->environment === 'dev') {
    $whoops->pushHandler(new PrettyPageHandler);
} else {
    $whoops->pushHandler(function (Throwable $e): void {
        error_log('Error: ' . $e->getMessage(), $e->getCode());
        echo 'An Error happened';
    });
}
$whoops->register();
...
```

Check if everything still works, run your code quality checks and commit the changes before moving on the the next chapter.

[<< previous](11-templating.md) | [next >>](13-refactoring.md)
