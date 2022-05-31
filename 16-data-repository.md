[<< previous](15-adding-content.md) | [next >>](17-performance.md)

## Data Repository

At the end of the last chapter I mentioned being unhappy with our Pages action, because there is too much stuff happening
there. We are firstly receiving some Arguments, then we are using those to query the filesystem for the given page,
loading the specific file from the filesystem, rendering the markdown, passing the markdown to the template renderer,
adding the resulting html to the response and then returning the response.

In order to make our page-action independent of the filesystem and move the code that is responsible for reading the
files
to a better place I want to introduce
the [Repository Pattern](https://designpatternsphp.readthedocs.io/en/latest/More/Repository/README.html).

I want to start by creating a class that represents the Data that is included in a page so that. For now, I can spot
three
distinct attributes.

* the ID (or chapter-number)
* the title (or name)
* the content

Currently, all those properties are always available, but we might later be able to create new pages and store them, but
at that point in time we are not yet aware of the new available ID, so we should leave that property nullable. This
allows
us to create an object without an id and let the code that actually saves the object to a persistent store define a
valid
id on saving.

Let's create an new Namespace called `Model` and put a `MarkdownPage.php` class in there:

```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Model;

class MarkdownPage
{
    public function __construct(
        public string $title,
        public string $content,
        public int|null $id = null,
    ) {
    }
}
```

These small Model classes are one of my most favorite features in newer PHP-Versions, because they are almost as easy
to create as an on-the-fly array but give us the great benefit of type safety as well as full code completion in our
IDEs.
There is a [great blogpost](https://stitcher.io/blog/evolution-of-a-php-object) that highlights how this kind of
objects
have evolved in PHP from version 5.6 to 8.1, as I personally first started writing proper php with 5.4 it really baffles
me how far the language has evolved in these last years.

Next we can define our interface for the repository, for our current use case I see only two needed methods:

* get all pages
* get one page by name

The `all()` method should return an array of all available pages (or an empty one if there are none), and the
`byName(string $name)` method should either return exactly one page or throw a NotFound-Exception. You decide to return
`false` or `null` if no page with the given name could be found, but I personally prefer exception, as that keeps the
return type checking simpler and we can decide at what layer of the application we want to handle a miss on that
function.

With that said we can now define create a `Repository` namespace and place a `MarkdownPageRepo.php` there:

```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Repository;

use Lubian\NoFramework\Exception\NotFound;
use Lubian\NoFramework\Model\MarkdownPage;

interface MarkdownPageRepo
{
    /** @return MarkdownPage[] */
    public function all(): array;
    /** @throws NotFound */
    public function byName(string $name): MarkdownPage;
}
```

Now we can write an implementation for this interface and move our code from to Action there:
`src/Repository/FilesystemMarkdownPageRepo.php`

```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Repository;

use Lubian\NoFramework\Exception\InternalServerError;
use Lubian\NoFramework\Exception\NotFound;
use Lubian\NoFramework\Model\MarkdownPage;

use function array_filter;
use function array_map;
use function array_values;
use function count;
use function file_get_contents;
use function glob;
use function str_replace;
use function substr;

final class FileSystemMarkdownPageRepo implements MarkdownPageRepo
{
    public function __construct(
        private readonly string $dataPath
    ) {
    }

    /** @inheritDoc  */
    public function all(): array
    {
        $files = glob($this->dataPath . '*.md');
        if ($files === false) {
            throw new InternalServerError('cannot read pages');
        }
        return array_map(function (string $filename) {
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
        }, $files);
    }

    public function byName(string $name): MarkdownPage
    {
        $pages = array_values(
            array_filter(
                $this->all(),
                fn (MarkdownPage $p) => $p->title === $name,
            )
        );

        if (count($pages) !== 1) {
            throw new NotFound;
        }

        return $pages[0];
    }
}
```

With that in place we need to add the required `$pagesPath` to our settings class and add specify that in our
configuration.

`src/Settings.php`

```php
final class Settings
{
    public function __construct(
        public readonly string $environment,
        public readonly string $dependenciesFile,
        public readonly string $middlewaresFile,
        public readonly string $templateDir,
        public readonly string $templateExtension,
        public readonly string $pagesPath,
    ) {
    }
}
```

`config/settings.php`

```php
return new Settings(
    environment: 'prod',
    dependenciesFile: __DIR__ . '/dependencies.php',
    middlewaresFile: __DIR__ . '/middlewares.php',
    templateDir: __DIR__ . '/../templates',
    templateExtension: '.html',
    pagesPath: __DIR__ . '/../data/pages/',
);
```

Of course, we need to define the correct implementation for the container to choose when we are requesting the Repository
interface:
`conf/dependencies.php`

```php
MarkdownPageRepo::class => fn (FileSystemMarkdownPageRepo $r) => $r,
FileSystemMarkdownPageRepo::class => fn (Settings $s) => new FileSystemMarkdownPageRepo($s->pagesPath),
```

Now you can request the MarkdownPageRepo Interface in your page action and use the defined functions to get the
MarkdownPage
Objects. My `src/Action/Page.php` looks like this now:

```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Lubian\NoFramework\Model\MarkdownPage;
use Lubian\NoFramework\Repository\MarkdownPageRepo;
use Lubian\NoFramework\Template\MarkdownParser;
use Lubian\NoFramework\Template\Renderer;
use Psr\Http\Message\ResponseInterface;

use function array_map;
use function assert;
use function is_string;
use function preg_replace;
use function str_replace;

class Page
{
    public function __construct(
        private ResponseInterface $response,
        private MarkdownParser $parser,
        private Renderer $renderer,
        private MarkdownPageRepo $repo,
    ) {
    }

    public function show(
        string $page,
    ): ResponseInterface {
        $page = $this->repo->byName($page);

        // fix the next and previous buttons to work with our routing
        $content = preg_replace('/\(\d\d-/m', '(', $page->content);
        assert(is_string($content));
        $content = str_replace('.md)', ')', $content);

        $data = [
            'title' => $page->title,
            'content' => $this->parser->parse($content),
        ];

        $html = $this->renderer->render('page/show', $data);
        $this->response->getBody()->write($html);
        return $this->response;
    }

    public function list(): ResponseInterface
    {
        $pages = array_map(function (MarkdownPage $page) {
            return [
                'id' => $page->id,
                'title' => $page->content,
            ];
        }, $this->repo->all());

        $html = $this->renderer->render('page/list', ['pages' => $pages]);
        $this->response->getBody()->write($html);
        return $this->response;
    }
}
```

Check the page in your browser if everything still works, don't forget to run phpstan and the others fixers before
committing your changes and moving on to the next chapter.

[<< previous](15-adding-content.md) | [next >>](17-performance.md)
