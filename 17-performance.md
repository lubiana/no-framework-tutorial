[<< previous](15-adding-content.md) | [next >>](17-performance.md)

## Performance

Although our application is still very small and you should not really experience any performance issues right now,
there are still some things we can already consider and take a look at. If I check the network tab in my browser it takes
about 90-400ms to show a simple rendered markdownpage, with is sort of ok but in my opinion way to long as we are not
really doing anything and do not connect to any external services. Mostly we are just reading around 16 markdown files,
a template, some config files here and there and parse some markdown. So that should not really take that long.

The problem is, that we heavily rely on autoloading for all our class files, in the `src` folder. And there are also
quite a lot of other files in composers `vendor` directory. To understand while this is becomming we should make
ourselves familiar with how autoloading in PHP works.

[autoloading in php](https://www.php.net/manual/en/language.oop5.autoload.php)
[composer autoloader optimization](https://getcomposer.org/doc/articles/autoloader-optimization.md)

### Composer autoloading

[<< previous](15-adding-content.md) | [next >>](17-performance.md)
