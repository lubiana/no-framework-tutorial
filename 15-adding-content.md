[<< previous](14-middleware.md) | [next >>](16-data-repository.md)

### Adding Content

By now we did not really display anything but some examples to in our application, and it is now time to make our app
display some content. For example we could our app be able to display the Markdown files used in this tutorial as
nicely rendered HTML Pages that can be viewed in the browser instead of the editor you are using.

So lets start by copying the markdown files to our app directory. I have created a new folder 'data/pages' and placed all
the markdown files in there.

Next we need a markdown parser, a pretty simple one is [Parsedown](https://parsedown.org/), if you want more features
you could also use the [Commonmark parser](https://commonmark.thephpleague.com/). I will choose Parsedown here, but you
can use whatever you like.

After installing Parsedown lets write a Markdownparser interface and an implementation using parsedown.

We only need one function that receives a string of Markdown and returns the HTML representation (as a string as well).



```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Template;

interface MarkdownParser
{
    public function parse(string $markdown): string;
}
```

By the namespace you will already have guessed that I placed in interface in a file calles MarkdownParser.php in
the src/Template folder. Let's put our Parsedown implementation right next to it in a file called ParsedownParser.php

```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Template;

use Parsedown;

final class ParsedownRenderer implements MarkdownParser
{
    public function __construct(private Parsedown $parser)
    {
    }

    public function parse(string $markdown): string
    {
        return $this->parser->parse($markdown);
    }
}
```

We could now use the ParsedownRender class directly in our actions by typehinting the classname as an argument to the
constructor or a method, but as we always want to rely on an interface instead of an implementation we need to define
the ParsedownRenderer as the correct implementation for the MarkdownRenderer interface in the dependencies file:

```php
...
    \Lubian\NoFramework\Template\MarkdownParser::class => fn(\Lubian\NoFramework\Template\ParsedownParser $p) => $p,
...
```

You can test that in our "Other.php" action and try out if the Parser works and is able to render Markdown to HTML:

```php
public function someFunctionName(ResponseInterface $response, MarkdownParser $parser): ResponseInterface
{
    $html = $parser->parse('This *works* **too!**');
    $response->getBody()->write($html);
    return $response->withStatus(200);
}
```

But we want to display complete Pages written in Markdown, it would also be neat to be able to display a list of all
available pages. For that we need a few things:

Firstly we need two new Templates, one for the list of the Pages, and the second one for displaying a single pages
content. Create a new folder in `templates/page` with to files:

`templates/page/list.html`
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pages</title>
    <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@latest/css/pico.classless.min.css">
</head>
<body>
<main>
    <ul>
        {{#pages}}
        <li>
            <a href="/page/{{title}}">{{id}}: {{title}}</a>
        </li>
        {{/pages}}
    </ul>
</main>
</body>
</html>
```

This template iterates over a provided array of pages, each element consists of the two properties: an id and a title,
those are simply displayed using an unordered list.

`templates/page/show.html`
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{title}}</title>
    <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@latest/css/pico.classless.min.css">
    <link rel="stylesheet"
          href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.5.0/styles/default.min.css">
    <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.5.0/highlight.min.js"></script>
    <script>hljs.highlightAll();</script> 
</head>
<body>
<main>
    {{{content}}}
</main>
</body>
</html>
```

The second templates displays a single rendered markdown page. As data it expects the title and the content as array.
I used an extra bracket for the content ```{{{content}}}``` so that the Mustache-Renderer does not escape the provided
html and thereby destroys the parsed markdown.

You might have spotted that I added [Pico.css](https://picocss.com/) which is just a very small css framework to make the
pages a little nicer to look at. It mostly provides some typography styles that work great with rendered Markdown,
but you can leave that out or use any other css framework you like. There is also some Javascript that adds syntax
highlighting to the code.

After you have taken care of the templating side we can now create an new Action class with two methods to display use
our markdown files and the templates to create the pages. As we have two templates I propose to use Two methods in our
Action:
`src/Action/Page.php`
```php
function show(string $name): \Psr\Http\Message\ResponseInterface;
function list(): \Psr\Http\Message\ResponseInterface;
```

Let's define two routes. `/page` should display the overview of all pages, and if the add the name of chapter to the
route, `/page/adding-content` for example, the show action should be called with the name as a variable:

`config/routes.php`
```php
$r->addRoute('GET', '/page', [Page::class, 'list']);
$r->addRoute('GET', '/page/{page}', [Page::class, 'show']);
```

Here is my Implementation. I have added a little regex replacement in the show method that replaces the links to the
next and previous chapter so that it works with our routing configuration.

`src/Action/Page.php`
```php
<?php declare(strict_types=1);

namespace Lubian\NoFramework\Action;

use Lubian\NoFramework\Exception\InternalServerError;
use Lubian\NoFramework\Template\MarkdownParser;
use Lubian\NoFramework\Template\Renderer;
use Psr\Http\Message\ResponseInterface;

use function array_filter;
use function array_map;
use function array_values;
use function file_get_contents;
use function glob;
use function preg_replace;
use function str_contains;
use function str_replace;
use function substr;

class Page
{
    public function __construct(
        private ResponseInterface $response,
        private MarkdownParser $parser,
        private Renderer $renderer,
        private string $pagesPath = __DIR__ . '/../../data/pages/'
    ) {
    }

    public function show(
        string $page,
    ): ResponseInterface {
        $page = array_values(
            array_filter(
                $this->getPages(),
                fn (string $filename) => str_contains($filename, $page)
            )
        )[0];
        $markdown = file_get_contents($page);

        // fix the next and previous buttons to work with our routing
        $markdown = preg_replace('/\(\d\d-/m', '(', $markdown);
        $markdown = str_replace('.md)', ')', $markdown);

        $page = str_replace([$this->pagesPath, '.md'], ['', ''], $page);
        $data = [
            'title' => substr($page, 3),
            'content' => $this->parser->parse($markdown),
        ];
        $html = $this->renderer->render('page/show', $data);
        $this->response->getBody()->write($html);
        return $this->response;
    }

    public function list(): ResponseInterface
    {
        $pages = array_map(function (string $page) {
            $page = str_replace([$this->pagesPath, '.md'], ['', ''], $page);
            return [
                'id' => substr($page, 0, 2),
                'title' => substr($page, 3),
            ];
        }, $this->getPages());
        $html = $this->renderer->render('page/list', ['pages' => $pages]);
        $this->response->getBody()->write($html);
        return $this->response;
    }

    /**
     * @return string[]
     */
    private function getPages(): array
    {
        $files = glob($this->pagesPath . '*.md');
        if ($files === false) {
            throw new InternalServerError('cannot read pages');
        }
        return $files;
    }
}
```

You can now navigate your Browser to [localhost:1235/page][http://localhost:1235/page] and try out if everything works.

Of course this code is far from looking good. We heavily rely on the pages being files in the filesystem, and the action
should never be aware of the filesystem in the first place, also we have a lot of string replacements and other repetitive
code in the file. And phpstan is going to scream at us a lot, but if we rewrite the code to satisfy all the checks we would
add even more lines to that simple class, so lets move on to the next chapter where we move all the logic to separate
classes following our holy SOLID principles :)


[<< previous](14-middleware.md) | [next >>](16-data-repository.md)
