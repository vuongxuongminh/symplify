<?php

declare(strict_types=1);

namespace Symplify\EasyCodingStandard\DependencyInjection\CompilerPass;

use PHP_CodeSniffer\Standards\Generic\Sniffs\Arrays\DisallowLongArraySyntaxSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Arrays\DisallowShortArraySyntaxSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\AssignmentInConditionSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineEndingsSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\DisallowMultipleStatementsSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\LowerCaseConstantSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\LowerCaseKeywordSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\WhiteSpace\DisallowSpaceIndentSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\WhiteSpace\DisallowTabIndentSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\WhiteSpace\ScopeIndentSniff;
use PHP_CodeSniffer\Standards\PEAR\Sniffs\WhiteSpace\ScopeClosingBraceSniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Classes\ClassDeclarationSniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Classes\PropertyDeclarationSniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Files\ClosingTagSniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Files\EndFileNewlineSniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Namespaces\NamespaceDeclarationSniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Namespaces\UseDeclarationSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Classes\LowercaseClassKeywordsSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Functions\FunctionDeclarationArgumentSpacingSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Strings\DoubleQuoteUsageSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\LanguageConstructSpacingSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\SuperfluousWhitespaceSniff;
use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\Basic\BracesFixer;
use PhpCsFixer\Fixer\Casing\ConstantCaseFixer;
use PhpCsFixer\Fixer\Casing\LowercaseKeywordsFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassDefinitionFixer;
use PhpCsFixer\Fixer\ClassNotation\SingleClassElementPerStatementFixer;
use PhpCsFixer\Fixer\ClassNotation\VisibilityRequiredFixer;
use PhpCsFixer\Fixer\ControlStructure\IncludeFixer;
use PhpCsFixer\Fixer\ControlStructure\NoUnneededControlParenthesesFixer;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\FunctionNotation\FunctionTypehintSpaceFixer;
use PhpCsFixer\Fixer\FunctionNotation\ReturnTypeDeclarationFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Import\SingleImportPerStatementFixer;
use PhpCsFixer\Fixer\Import\SingleLineAfterImportsFixer;
use PhpCsFixer\Fixer\NamespaceNotation\BlankLineAfterNamespaceFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocScalarFixer;
use PhpCsFixer\Fixer\PhpTag\NoClosingTagFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;
use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;
use PhpCsFixer\Fixer\Whitespace\IndentationTypeFixer;
use PhpCsFixer\Fixer\Whitespace\LineEndingFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use PhpCsFixer\Fixer\Whitespace\SingleBlankLineAtEofFixer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RemoveMutualCheckersCompilerPass implements CompilerPassInterface
{
    /**
     * List of checkers with the same functionality. If found, only the first one is used.
     *
     * @var string[][]
     */
    private const DUPLICATED_CHECKER_GROUPS = [
        [IndentationTypeFixer::class, DisallowTabIndentSniff::class],
        [IndentationTypeFixer::class, DisallowSpaceIndentSniff::class],
        [StrictComparisonFixer::class, 'SlevomatCodingStandard\Sniffs\Operators\DisallowEqualOperatorsSniff'],
        [VisibilityRequiredFixer::class, 'SlevomatCodingStandard\Sniffs\Classes\ClassConstantVisibilitySniff'],
        [ArraySyntaxFixer::class, DisallowShortArraySyntaxSniff::class],
        [ArraySyntaxFixer::class, DisallowLongArraySyntaxSniff::class],
        [LowercaseKeywordsFixer::class, LowercaseClassKeywordsSniff::class],
        [LowercaseKeywordsFixer::class, LowerCaseKeywordSniff::class],
        [SingleImportPerStatementFixer::class, UseDeclarationSniff::class],
        [SingleImportPerStatementFixer::class, 'SlevomatCodingStandard\Sniffs\Namespaces\DisallowGroupUseSniff'],
        [SingleImportPerStatementFixer::class, 'SlevomatCodingStandard\Sniffs\Namespaces\MultipleUsesPerLineSniff'],
        [PhpdocScalarFixer::class, 'SlevomatCodingStandard\Sniffs\TypeHints\LongTypeHintsSniff'],
        [OrderedImportsFixer::class, 'SlevomatCodingStandard\Sniffs\Namespaces\AlphabeticallySortedUsesSniff'],
        [NoUnusedImportsFixer::class, 'SlevomatCodingStandard\Sniffs\Namespaces\UnusedUsesSniff'],
        [TrailingCommaInMultilineFixer::class, 'SlevomatCodingStandard\Sniffs\Arrays\TrailingArrayCommaSniff'],
        [
            NoUnneededControlParenthesesFixer::class,
            'SlevomatCodingStandard\Sniffs\ControlStructures\LanguageConstructWithParenthesesSniff',
        ],
        [ReturnTypeDeclarationFixer::class, 'SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSpacingSniff'],
        [FunctionTypehintSpaceFixer::class, 'SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSpacingSniff'],
        [FunctionTypehintSpaceFixer::class, FunctionDeclarationArgumentSpacingSniff::class],
        [
            GeneralPhpdocAnnotationRemoveFixer::class,
            'SlevomatCodingStandard\Sniffs\Commenting\ForbiddenAnnotationsSniff',
        ],
        [NoExtraBlankLinesFixer::class, SuperfluousWhitespaceSniff::class],
        [IncludeFixer::class, LanguageConstructSpacingSniff::class],
        [
            AssignmentInConditionSniff::class,
            'SlevomatCodingStandard\Sniffs\ControlStructures\AssignmentInConditionSniff',
        ],
        [SingleQuoteFixer::class, DoubleQuoteUsageSniff::class],

        // PSR2
        [BracesFixer::class, ScopeClosingBraceSniff::class],
        [BlankLineAfterNamespaceFixer::class, NamespaceDeclarationSniff::class],
        [SingleLineAfterImportsFixer::class, DisallowMultipleStatementsSniff::class],
        [LineEndingFixer::class, LineEndingsSniff::class],
        [ConstantCaseFixer::class, LowerCaseConstantSniff::class],
        [LowercaseKeywordsFixer::class, LowerCaseKeywordSniff::class],
        [SingleBlankLineAtEofFixer::class, EndFileNewlineSniff::class],
        [BracesFixer::class, ScopeIndentSniff::class],
        [BracesFixer::class, ScopeClosingBraceSniff::class],
        [ClassDefinitionFixer::class, ClassDeclarationSniff::class],
        [NoClosingTagFixer::class, ClosingTagSniff::class],
        [SingleClassElementPerStatementFixer::class, PropertyDeclarationSniff::class],
    ];

    public function process(ContainerBuilder $containerBuilder): void
    {
        $checkersToRemove = $this->resolveCheckersToRemove($containerBuilder->getServiceIds());

        $definitions = $containerBuilder->getDefinitions();
        foreach ($definitions as $id => $definition) {
            if (in_array($definition->getClass(), $checkersToRemove, true)) {
                $containerBuilder->removeDefinition($id);
            }
        }
    }

    /**
     * @param string[] $checkers
     * @return string[]
     */
    private function resolveCheckersToRemove(array $checkers): array
    {
        $checkers = array_flip($checkers);

        $checkersToRemove = [];
        foreach (self::DUPLICATED_CHECKER_GROUPS as $matchingCheckerGroup) {
            if (! $this->isMatch($checkers, $matchingCheckerGroup)) {
                continue;
            }

            array_shift($matchingCheckerGroup);

            $checkersToRemove = [...$checkersToRemove, ...$matchingCheckerGroup];
        }

        return $checkersToRemove;
    }

    /**
     * @param string[] $checkers
     * @param string[] $matchingCheckerGroup
     */
    private function isMatch(array $checkers, array $matchingCheckerGroup): bool
    {
        $matchingCheckerGroupKeys = array_flip($matchingCheckerGroup);
        $matchingCheckers = array_intersect_key($matchingCheckerGroupKeys, $checkers);

        return count($matchingCheckers) === count($matchingCheckerGroup);
    }
}
