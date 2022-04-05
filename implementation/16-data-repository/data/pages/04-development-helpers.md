[<< previous](03-error-handler.md) | [next >>](05-http.md)

### Development Helpers

I have added some more helpers to my composer.json that help me with development. As these are scripts and programms
used only for development they should not be used in a production environment. Composer has a specific sections in its
file called "dev-dependencies", everything that is required in this section does not get installen in production.

Let's install our dev-helpers and i will explain them one by one:
`composer require --dev phpstan/phpstan php-cs-fixer/shim symfony/var-dumper squizlabs/php_codesniffer`

#### Static Code Analysis with phpstan

Phpstan is a great little tool, that tries to understand your code and checks if you are making any grave mistakes or
create bad defined interfaces and structures. It also helps in finding logic-errors, dead code, access to array elements
that are not (or not always) available, if-statements that always are true and a lot of other stuff.

A very simple example would be a small functions that takes a DateTime-Object and prints it in a human readable format.

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
we usually would not discover easily without speccially looking for this errortype.

We can add a simple configfile called phpstan.neon to our project so that we do not have to specify the errorlevel and
path everytime we want to check our code for errors:

```yaml
parameters:
    level: max
    paths:
        - src
```
now we can just call `./vendor/bin/phpstan analyze` and have the same setting for every developer working in our project

With this settings we have already a great setup to catch some errors before we execute the code, but it still allows us
some silly things, therefore we want to add install some packages that enforce rules that are a little bit more strict.

```shell
composer require --dev phpstan/extension-installer
composer require --dev phpstan/phpstan-strict-rules thecodingmachine/phpstan-strict-rules
```

During the first install you need to allow the extension installer to actually install the extension. The second command
installs some more strict rulesets and activates them in phpstan.

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
that we should be able to fix that. The first error is something we could fix, but I dont want to focus on that specific
problem right now. Phpstan gives us the option to ignore some errors and handle them later. If for example we are working
on an old legacy codebase and wanted to add static analysis to it but cant because we would get 1 Million error messages
everytime we use phpstan, we could add all those errors to a list and tell phpstan to only bother us about new errors we
are adding to our code.

In order to use that we have to add an empty file 'phpstan-baseline.neon' to our project, include that in the
phpstan.neon file and run phpstan with the
'--generate-baseline' option:

```yaml
includes:
    - phpstan-baseline.neon

parameters:
    level: 9
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

#### PHP-CS-Fixer

Another great tool is the php-cs-fixer, which just applies a specific style to your code.

when you run `./vendor/bin/php-cs-fixer fix ./` it applies the psr-12 code style to every php file in you current
directory.

You can read more about its usage and possible rulesets in the [documentation](https://github.com/FriendsOfPHP/PHP-CS-Fixer#documentation)

personally i like to have a more opiniated version with some rules added to the psr-12 standard and have therefore setup
a configuration file that i use in all my projects .php-cs-fixer.php:

```php
<?php declare(strict_types=1);
/*
 * This document has been generated with
 * https://mlocati.github.io/php-cs-fixer-configurator/#version:3.1.0|configurator
 * you can change this configuration by importing this file.
 */
$config = new PhpCsFixer\Config();
return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12:risky' => true,
        '@PSR12' => true,
        '@PHP80Migration' => true,
        '@PHP80Migration:risky' => true,
        '@PHP81Migration' => true,
        'array_indentation' => true,
        'include' => true,
        'blank_line_after_opening_tag' => false,
        'native_constant_invocation' => true,
        'new_with_braces' => false,
        'native_function_invocation' => [
            'include' => ['@all']
        ],
        'no_unused_imports' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'ordered_interfaces' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in([
                __DIR__ . '/src',
            ])
    );
```

#### PHP Codesniffer

The PHPCodesniffer is sort of a combination of the previous tools, it checks for a defined codingstyle and some extra
rules that are not just stylechanges but instead enforces extra rules in if-statements, exception handling etc.

it provides the `phpcs` command to check for violations and the `phpcbf` command to actually fix most of the violations.

Without configuration the tool tries to apply the PSR12 standard just like the php-cs-fixer, but as you might have
guessed we are adding some extra rules.

Lets install the ruleset with composer
```shell
composer require --dev mnapoli/hard-mode
```

and add a configuration file to actually use it '.phpcs.xml.dist'
```xml
<?xml version="1.0"?>
<ruleset>
    <arg name="basepath" value="."/>

    <file>src</file>

    <rule ref="HardMode"/>
</ruleset>
```

running `./vendor/bin/phpcs` now checks our src directory for violations and gives us a detailed list about the findings.

```
[vagrant@archlinux app]$ ./vendor/bin/phpcs

FILE: src/Bootstrap.php
----------------------------------------------------------------------------------------------------
FOUND 4 ERRORS AFFECTING 4 LINES
----------------------------------------------------------------------------------------------------
  7 | ERROR | [x] Use statements should be sorted alphabetically. The first wrong one is Throwable.
  8 | ERROR | [x] Expected 1 lines between different types of use statement, found 0.
 11 | ERROR | [x] Expected 1 lines between different types of use statement, found 0.
 24 | ERROR | [x] String "ERROR: " does not require double quotes; use single quotes instead
----------------------------------------------------------------------------------------------------
PHPCBF CAN FIX THE 4 MARKED SNIFF VIOLATIONS AUTOMATICALLY
----------------------------------------------------------------------------------------------------

Time: 639ms; Memory: 10MB
```

You can then use `./vendor/bin/phpcbf` to try to fix them.


#### Symfony Var-Dumper

another great tool for some quick debugging without xdebug is the symfony var-dumper. This just gives us some small
functions.

dump(); is basically like phps var_dump() but has a better looking output that helps when looking into bigger objects 
or arrays.

dd() on the other hand is a function that dumps its parameters and then exits the php-script.

you could just write dd($whoops) somewhere in your bootstrap.php to check how the output looks.

#### Composer scripts

now we have a few commands that are available on the command line. i personally do not like to type complex commands
with lots of parameters by hand all the time, so i added a few lines to my composer.json:

```json
"scripts": {
    "serve": "php -S 0.0.0.0:1234 -t public",
    "phpstan": "./vendor/bin/phpstan analyze",
    "baseline": "./vendor/bin/phpstan analyze --generate-baseline",
    "check": "./vendor/bin/phpcs",
    "fix": "./vendor/bin/php-cs-fixer fix && ./vendor/bin/phpcbf"
},
```

that way i can just type "composer" followed by the command name in the root of my project. if i want to start the
php devserver i can just type "composer serve" and dont have to type in the hostname, port and targetdirectory all the
time.

You could also configure PhpStorm to automatically run these commands in the background and highlight the violations
directly in the file you are currently editing. I personally am not a fan of this approach because it often disrupts my
flow when programming and always forces me to be absolutely strict even if I am only trying out an idea for debugging.

My workflow is to just write my code the way i currently feel and that execute the phpstan and the fix scripts before
commiting and pushing the code.

[<< previous](03-error-handler.md) | [next >>](05-http.md)
