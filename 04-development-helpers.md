[<< previous](03-error-handler.md) | [next >>](05-http.md)

### Development Helpers

I have added some more helpers to my composer.json that help me with development. As these are scripts and programms
used only for development they should not be used in a production environment. Composer has a specific sections in its
file called "dev-dependencies", everything that is required in this section does not get installed in production.

Let's install our dev-helpers and i will explain them one by one:
`composer require --dev phpstan/phpstan symfony/var-dumper slevomat/coding-standard symplify/easy-coding-standard rector/rector`

#### Static Code Analysis with phpstan

Phpstan is a great little tool, that tries to understand your code and checks if you are making any grave mistakes or
create bad defined interfaces and structures. It also helps in finding logic-errors, dead code, access to array elements
that are not (or not always) available, if-statements that always are true and a lot of other stuff.

A very simple example would be a small functions that takes a DateTime-Object and prints it in a human-readable format.

```php
/**
 * @param \DateTime $date
 * @return void
 */
function printDate($date) {
    $date->format('Y-m-d H:i:s');
}

printDate('now');
```
if we run phpstan with the command `./vendor/bin/phpstan analyse --level 9 ./src/`

It firstly tells us that calling "format" on a DateTime-Object without outputting or returning the function result has
no use, and secondly, that we are calling the function with a string instead of a datetime object.

```shell
1/1 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

 ------ --------------------------------------------------------------------------------------------- 
Line   Bootstrap.php
 ------ --------------------------------------------------------------------------------------------- 
30     Call to method DateTime::format() on a separate line has no effect.                          
33     Parameter #1 $date of function Lubian\NoFramework\printDate expects DateTime, string given.
 ------ --------------------------------------------------------------------------------------------- 
```

The second error is something that "declare strict-types" already catches for us, but the first error is something that
we usually would not discover easily without specially looking for this error-type.

We can add a simple config file called `phpstan.neon` to our project so that we do not have to specify the error level and
path everytime we want to check our code for errors:

```yaml
parameters:
    level: max
    paths:
        - src
```
now we can just call `./vendor/bin/phpstan analyze` and have the same setting for every developer working in our project

With this settings we have already a great setup to catch some errors before we execute the code, but it still allows us
some silly things, therefore we want to add install some packages that enforce rules that are a little stricter.

```shell
composer require --dev phpstan/extension-installer
composer require --dev phpstan/phpstan-strict-rules thecodingmachine/phpstan-strict-rules
```

During the first install you need to allow the extension installer to actually install the extension. The second command
installs some more strict rules and activates them in phpstan.

If we now rerun phpstan it already tells us about some errors we have made:

```
 ------ ----------------------------------------------------------------------------------------------- 
Line   Bootstrap.php
 ------ ----------------------------------------------------------------------------------------------- 
10     Short ternary operator is not allowed. Use null coalesce operator if applicable or consider    
       using long ternary.                                                                            
25     Do not throw the \Exception base class. Instead, extend the \Exception base class. More info:  
       http://bit.ly/subtypeexception                                                                 
26     Unreachable statement - code above always terminates.
 ------ ----------------------------------------------------------------------------------------------- 
```

The last two Errors are caused by the Exception we have used to test the ErrorHandler in the last chapter if we remove
that we should be able to fix that. The first error is something we could fix, but I don't want to focus on that specific
problem right now. Phpstan gives us the option to ignore some errors and handle them later. If for example we are working
on an old legacy codebase and wanted to add static analysis to it but can't because we would get 1 Million error messages
everytime we use phpstan, we could add all those errors to a list and tell phpstan to only bother us about new errors we
are adding to our code.

In order to use that we have to add an empty file `phpstan-baseline.neon` to our project, include that in the
`phpstan.neon` file and run phpstan with the `--generate-baseline` option:

```yaml
includes:
    - phpstan-baseline.neon

parameters:
    level: max
    paths:
        - src
```
```shell
[vagrant@archlinux app]$ ./vendor/bin/phpstan analyze --generate-baseline
Note: Using configuration file /home/vagrant/app/phpstan.neon.
 1/1 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%


                                                                                                                        
 [OK] Baseline generated with 1 error.                                                                                  
                                                                                                                        

```

you can read more about the possible parameters and usage options in the [documentation](https://phpstan.org/user-guide/getting-started)

#### Easy-Coding-Standard

There are two great tools that help us with applying a consistent coding style to our project as well as check and
automatically fix some other errors and oversights that we might not bother with when writing our code.

The first one is [PHP Coding Standards Fixer](https://cs.symfony.com/) which can automatically detect violations of
a defined coding standard and fix them. The second tool is [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)
which basically does the same has in my experience some more Rules available that we can apply to our code.

But we are going to use neither of those tools directly and instead choose the [Easy Coding Standard](https://github.com/symplify/easy-coding-standard)
which allows us to combine rules from both mentioned tools, and also claims to run much faster. You could check out the
documentation and decide on your own coding standard. Or use the one provided by me, which is base on PSR-12 but adds
some highly opiniated options. First create a file 'ecs.php' and either add your own configuration or copy the
prepared one:

```php
<?php declare(strict_types=1);

use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Operator\NewWithBracesFixer;
use PhpCsFixer\Fixer\PhpTag\BlankLineAfterOpeningTagFixer;
use SlevomatCodingStandard\Sniffs\Classes\ClassConstantVisibilitySniff;
use SlevomatCodingStandard\Sniffs\ControlStructures\NewWithoutParenthesesSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\AlphabeticallySortedUsesSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\DisallowGroupUseSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\MultipleUsesPerLineSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\NamespaceSpacingSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\ReferenceUsedNamesOnlySniff;
use SlevomatCodingStandard\Sniffs\Namespaces\UseSpacingSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\DeclareStrictTypesSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\UnionTypeHintFormatSniff;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $config): void {
    $config->parallel();
    $config->paths([__DIR__ . '/src', __DIR__ . '/ecs.php', __DIR__ . '/rector.php']);
    $config->skip([BlankLineAfterOpeningTagFixer::class, OrderedImportsFixer::class, NewWithBracesFixer::class]);

    $config->sets([
        SetList::PSR_12,
        SetList::STRICT,
        SetList::ARRAY,
        SetList::SPACES,
        SetList::DOCBLOCK,
        SetList::CLEAN_CODE,
        SetList::COMMON,
        SetList::COMMENTS,
        SetList::NAMESPACES,
        SetList::SYMPLIFY,
        SetList::CONTROL_STRUCTURES,
    ]);

    // force visibility declaration on class constants
    $config->ruleWithConfiguration(ClassConstantVisibilitySniff::class, [
        'fixable' => true,
    ]);

    // sort all use statements
    $config->rules([
        AlphabeticallySortedUsesSniff::class,
        DisallowGroupUseSniff::class,
        MultipleUsesPerLineSniff::class,
        NamespaceSpacingSniff::class,
    ]);

    // import all namespaces, and even php core functions and classes
    $config->ruleWithConfiguration(
        ReferenceUsedNamesOnlySniff::class,
        [
            'allowFallbackGlobalConstants' => false,
            'allowFallbackGlobalFunctions' => false,
            'allowFullyQualifiedGlobalClasses' => false,
            'allowFullyQualifiedGlobalConstants' => false,
            'allowFullyQualifiedGlobalFunctions' => false,
            'allowFullyQualifiedNameForCollidingClasses' => true,
            'allowFullyQualifiedNameForCollidingConstants' => true,
            'allowFullyQualifiedNameForCollidingFunctions' => true,
            'searchAnnotations' => true,
        ]
    );

    // define newlines between use statements
    $config->ruleWithConfiguration(UseSpacingSniff::class, [
        'linesCountBeforeFirstUse' => 1,
        'linesCountBetweenUseTypes' => 1,
        'linesCountAfterLastUse' => 1,
    ]);

    // strict types declaration should be on same line as opening tag
    $config->ruleWithConfiguration(DeclareStrictTypesSniff::class, [
        'declareOnFirstLine' => true,
        'spacesCountAroundEqualsSign' => 0,
    ]);

    // disallow ?Foo typehint in favor of Foo|null
    $config->ruleWithConfiguration(UnionTypeHintFormatSniff::class, [
        'withSpaces' => 'no',
        'shortNullable' => 'no',
        'nullPosition' => 'last',
    ]);

    // Remove useless parentheses in new statements
    $config->rule(NewWithoutParenthesesSniff::class);
};

```
You can now use `./vendor/bin/ecs` to list all violations of the defined standard and `./vendor/bin/ecs --fix` to
automatically fix them.

#### Rector

The next tool helps us with automatic refactorings and upgrades to newer PHP versions. 

Place a file called `rector.php` in your app directory and put in the following content:

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([__DIR__ . '/src', __DIR__ . '/rector.php', __DIR__ . '/ecs.php']);

    $rectorConfig->importNames();

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
    ]);
};
```

This config fixes your code and replaces function call and constructs that are deprecated in modern php versions. This
includes all fixes from PHP 5.2 up to PHP 8.1. You can take a look at all the rules [here](https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#php52).

To run this tool simply type `./vendor/bin/rector process` in your console. This should not to much right now, but will
be quite useful when php 8.2 or newer versions are released.

#### Symfony Var-Dumper

another great tool for some quick debugging without xdebug is the symfony var-dumper. This just gives us some small
functions.

dump(); is basically like phps var_dump() but has a better looking output that helps when looking into bigger objects
or arrays.

dd() on the other hand is a function that dumps its parameters and then exits the php-script.

you could just write dd($whoops) somewhere in your bootstrap.php to check how the output looks.

#### Composer scripts

now we have a few commands that are available on the command line. I personally do not like to type complex commands
with lots of parameters by hand all the time, so I added a few lines to my `composer.json`:

```json
"scripts": {
    "serve": [
      "Composer\\Config::disableProcessTimeout",
      "php -S 0.0.0.0:1235 -t public"       
    ],
    "phpstan": "./vendor/bin/phpstan analyze",
    "baseline": "./vendor/bin/phpstan analyze --generate-baseline",
    "check": "./vendor/bin/ecs",
    "fix": "./vendor/bin/ecs --fix",
    "rector": "./vendor/bin/rector process"
},
```

that way I can just type "composer" followed by the command name in the root of my project. if I want to start the
php dev server I can just type "composer serve" and don't have to type in the hostname, port and target directory all the
time.

You could also configure PhpStorm to automatically run these commands in the background and highlight the violations
directly in the file you are currently editing. I personally am not a fan of this approach because it often disrupts my
flow when programming and always forces me to be absolutely strict even if I am only trying out an idea for debugging.

My workflow is to just write my code the way I currently feel and that execute the phpstan and the fix scripts before
committing and pushing the code. There is a [highly opiniated blogpost](https://tomasvotruba.com/blog/2019/06/24/do-you-use-php-codesniffer-and-php-cs-fixer-phpstorm-plugin-you-are-slow-and-expensive/)
discussing that topic further. That you can read. But in the end it boils down to what you are most comfortable with.

[<< previous](03-error-handler.md) | [next >>](05-http.md)