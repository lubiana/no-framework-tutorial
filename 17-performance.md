[<< previous](16-data-repository.md) | [next >>](18-caching.md)

## Autoloading performance

Although our application is still very small, and you should not really experience any performance issues right now,
there are still some things we can already consider and take a look at. If I check the network tab in my browser it takes
about 90-400ms to show a simple rendered markdown, with is sort of ok but in my opinion way to long as we are not
really doing anything and do not connect to any external services. Mostly we are just reading around 16 markdown files,
a template, some config files here and there and parse some markdown. So that should not really take that long.

The problem is, that we heavily rely on autoload for all our class files, in the `src` folder. And there are also
quite a lot of other files in composers `vendor` directory. To understand while this is becoming we should make
ourselves familiar with how [autoloading in php](https://www.php.net/manual/en/language.oop5.autoload.php) works.

The basic idea is, that every class that php encounters has to be loaded from somewhere in the filesystem, we could
just require the files manually but that is tedious, unflexible and can often cause errors.

The problem we are now facing is that the composer autoloader has some rules to determine from where in the filesystem
a class definition might be placed, then the autoloader tries to locate a file by the namespace and classname and if it
exists includes that file.

If we only have a handful of classes that does not take a lot of time, but as we are growing with our application this
easily takes longer than necessary, but fortunately composer has some options to speed up the class loading.

Take a few minutes to read the documentation about [composer autoloader optimization](https://getcomposer.org/doc/articles/autoloader-optimization.md)

You can try all 3 levels of optimizations, but we are going to stick with the first one for now, so lets create an
optimized classmap.

`composer dump-autoload -o`

After composer has finished you can start the devserver again with `composer serve` and take a look at the network tab
in your browsers devtools.

In my case the response time falls down to under an average of 30ms with some spikes in between, but all in all it looks really good.
You can also try out the different optimization levels and see if you can spot any differences.

Although the composer manual states not to use the optimization in a dev environment I personally have not encountered
any errors with the first level of optimizations, so we can use that level here. If you add the line from the documentation
to your `composer.json` so that the autoloader gets optimized everytime we install new packages.


[<< previous](16-data-repository.md) | [next >>](18-caching.md)
