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
    $config->paths([__DIR__ . '/src', __DIR__ . '/config', __DIR__ . '/ecs.php', __DIR__ . '/rector.php']);
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

    // import all namespaces, and event php core functions and classes
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
