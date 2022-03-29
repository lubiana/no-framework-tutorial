<?php

declare(strict_types=1);
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
        'linebreak_after_opening_tag' => true,
        'native_constant_invocation' => true,
        'native_function_invocation' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'ordered_interfaces' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => [
                'const',
                'class',
                'function',
            ],
        ],
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in([
                __DIR__ . '/src',
                __DIR__ . '/config',
            ])
    );