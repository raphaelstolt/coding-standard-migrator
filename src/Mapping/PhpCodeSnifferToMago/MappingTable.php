<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Mapping\PhpCodeSnifferToMago;

use Stolt\CodingStandardMigrator\Mapping\Mago\RuleTable;
use Stolt\CodingStandardMigrator\Mapping\Mapping;
use Stolt\CodingStandardMigrator\Ruleset\Rule;

/**
 * The knowledge of which PHP_CodeSniffer sniff corresponds to which Mago setting.
 *
 * Sniffs are looked up by their `Standard.Category.Sniff` name, the error code of a
 * `Standard.Category.Sniff.ErrorCode` reference is stripped beforehand. As in the
 * PHP-CS-Fixer table, only verified mappings are listed, everything else stays unknown.
 *
 * @see https://mago.carthage.software/main/en/tools/formatter/configuration-reference/
 * @see https://mago.carthage.software/main/en/tools/linter/rules/
 */
final class MappingTable implements RuleTable
{
    /**
     * Referenced standards and the Mago formatter preset which comes closest.
     *
     * @var array<string, array{0: string|null, 1: string|null}>
     */
    private const STANDARDS = [
        '@PSR1' => ['psr-12', 'Mago has no PSR-1 preset, the PSR-12 preset is a superset of it.'],
        '@PSR2' => ['psr-12', 'Mago has no PSR-2 preset, the PSR-12 preset is a superset of it.'],
        '@PSR12' => ['psr-12', null],
        '@PER' => [null, 'The Mago formatter follows PER-CS by default, no preset needed.'],
        '@PER-CS' => [null, 'The Mago formatter follows PER-CS by default, no preset needed.'],
        '@Drupal' => ['drupal', null],
    ];

    /**
     * Standards which need to be migrated sniff by sniff.
     *
     * @var array<string, string>
     */
    private const STANDARDS_WITHOUT_PRESET = [
        '@Generic' => 'Mago has no counterpart preset, reference the sniffs you rely on individually.',
        '@MySource' => 'Mago has no counterpart preset, reference the sniffs you rely on individually.',
        '@PEAR' => 'Mago has no counterpart preset, reference the sniffs you rely on individually.',
        '@Squiz' => 'Mago has no counterpart preset, reference the sniffs you rely on individually.',
        '@Zend' => 'Mago has no counterpart preset, reference the sniffs you rely on individually.',
        '@PHPCompatibility' => 'Set the `php-version` key in mago.toml and let the Mago analyser report outdated constructs.',
    ];

    /**
     * Sniffs the Mago formatter applies unconditionally, no Mago setting needed.
     *
     * @var array<string, string|null>
     */
    private const COVERED_BY_FORMATTER = [
        'Generic.Files.EndFileNewline' => null,
        'Generic.Formatting.DisallowMultipleStatements' => null,
        'Generic.Functions.FunctionCallArgumentSpacing' => null,
        'Generic.WhiteSpace.ArbitraryParenthesesSpacing' => null,
        'Generic.WhiteSpace.IncrementDecrementSpacing' => null,
        'Generic.WhiteSpace.LanguageConstructSpacing' => null,
        'PEAR.Functions.FunctionCallSignature' => null,
        'PEAR.WhiteSpace.ScopeClosingBrace' => null,
        'PSR2.Classes.ClassDeclaration' => null,
        'PSR2.Classes.PropertyDeclaration' => null,
        'PSR2.ControlStructures.ElseIfDeclaration' => null,
        'PSR2.ControlStructures.SwitchDeclaration' => null,
        'PSR2.Methods.FunctionCallSignature' => null,
        'PSR2.Methods.FunctionClosingBrace' => null,
        'PSR2.Namespaces.UseDeclaration' => 'See the `empty-line-after-use` formatter option.',
        'PSR12.Files.FileHeader' => 'Mago normalises the spacing of the file header, not the order of its blocks.',
        'PSR12.Operators.OperatorSpacing' => null,
        'PSR12.Traits.UseDeclaration' => null,
        'Squiz.ControlStructures.ControlSignature' => null,
        'Squiz.ControlStructures.ForEachLoopDeclaration' => null,
        'Squiz.ControlStructures.ForLoopDeclaration' => null,
        'Squiz.ControlStructures.SwitchDeclaration' => null,
        'Squiz.Functions.FunctionDeclaration' => null,
        'Squiz.Functions.FunctionDeclarationArgumentSpacing' => null,
        'Squiz.Functions.MultiLineFunctionDeclaration' => null,
        'Squiz.WhiteSpace.CastSpacing' => null,
        'Squiz.WhiteSpace.ControlStructureSpacing' => null,
        'Squiz.WhiteSpace.LanguageConstructSpacing' => null,
        'Squiz.WhiteSpace.OperatorSpacing' => null,
        'Squiz.WhiteSpace.ScopeClosingBrace' => null,
        'Squiz.WhiteSpace.SemicolonSpacing' => null,
        'Squiz.WhiteSpace.SuperfluousWhitespace' => null,
    ];

    /**
     * Sniffs with a static, property independent Mago formatter counterpart.
     *
     * @var array<string, array<string, scalar>>
     */
    private const FORMATTER_OPTIONS = [
        'Generic.Classes.OpeningBraceSameLine' => ['classlike-brace-style' => 'same-line'],
        'Generic.Formatting.NoSpaceAfterCast' => ['space-after-cast-unary-prefix-operators' => false],
        'Generic.Formatting.SpaceAfterCast' => ['space-after-cast-unary-prefix-operators' => true],
        'Generic.Functions.OpeningFunctionBraceBsdAllman' => [
            'function-brace-style' => 'next-line',
            'method-brace-style' => 'next-line',
        ],
        'Generic.Functions.OpeningFunctionBraceKernighanRitchie' => [
            'function-brace-style' => 'same-line',
            'method-brace-style' => 'same-line',
        ],
        'Generic.PHP.LowerCaseConstant' => ['uppercase-literal-keyword' => false],
        'Generic.PHP.UpperCaseConstant' => ['uppercase-literal-keyword' => true],
        'Generic.WhiteSpace.DisallowSpaceIndent' => ['use-tabs' => true],
        'Generic.WhiteSpace.DisallowTabIndent' => ['use-tabs' => false],
        'PEAR.Classes.ClassDeclaration' => ['classlike-brace-style' => 'next-line'],
        'PSR2.Namespaces.NamespaceDeclaration' => ['empty-line-after-namespace' => true],
        'PSR12.ControlStructures.BooleanOperatorPlacement' => ['line-before-binary-operator' => true],
        'PSR2.Files.ClosingTag' => ['remove-trailing-close-tag' => true],
        'Squiz.Strings.DoubleQuoteUsage' => ['single-quote' => true],
        'Squiz.WhiteSpace.FunctionSpacing' => ['empty-line-after-method' => true],
        'Zend.Files.ClosingTag' => ['remove-trailing-close-tag' => true],
    ];

    /**
     * Sniffs with a Mago linter counterpart.
     *
     * @var array<string, array{0: array<string, array<string, mixed>>, 1: string|null}>
     */
    private const LINTER_RULES = [
        'Generic.CodeAnalysis.EmptyStatement' => [['no-empty' => ['enabled' => true]], null],
        'Generic.Commenting.Fixme' => [['tagged-fixme' => ['enabled' => true]], null],
        'Generic.Commenting.Todo' => [['tagged-todo' => ['enabled' => true]], null],
        'Generic.ControlStructures.InlineControlStructure' => [['block-statement' => ['enabled' => true]], null],
        'Generic.Files.OneClassPerFile' => [['single-class-per-file' => ['enabled' => true]], null],
        'Generic.Files.OneObjectStructurePerFile' => [['single-class-per-file' => ['enabled' => true]], null],
        'Generic.NamingConventions.CamelCapsFunctionName' => [
            [
                'function-name' => ['enabled' => true],
                'method-name' => ['enabled' => true],
            ],
            null,
        ],
        'Generic.NamingConventions.UpperCaseConstantName' => [['constant-name' => ['enabled' => true]], null],
        'Generic.PHP.ForbiddenFunctions' => [
            ['no-alias-function' => ['enabled' => true]],
            'Only covers the function aliases, not any additional forbidden functions of the sniff.',
        ],
        'Generic.PHP.LowerCaseKeyword' => [['lowercase-keyword' => ['enabled' => true]], null],
        'Generic.PHP.LowerCaseType' => [['lowercase-type-hint' => ['enabled' => true]], null],
        'PSR1.Classes.ClassDeclaration' => [['single-class-per-file' => ['enabled' => true]], null],
        'PSR1.Files.SideEffects' => [['no-side-effects-with-declarations' => ['enabled' => true]], null],
        'PSR1.Methods.CamelCapsMethodName' => [['method-name' => ['enabled' => true]], null],
        'Squiz.Classes.ClassFileName' => [['file-name' => ['enabled' => true]], null],
        'Squiz.Classes.ValidClassName' => [['class-name' => ['enabled' => true]], null],
        'Squiz.Commenting.ClassComment' => [
            ['missing-docs' => ['enabled' => true]],
            'Verify the scope of `missing-docs`, it is not limited to classes.',
        ],
        'Squiz.Commenting.FunctionComment' => [
            ['missing-docs' => ['enabled' => true]],
            'Verify the scope of `missing-docs`, it is not limited to functions.',
        ],
        'Squiz.Commenting.VariableComment' => [
            ['missing-docs' => ['enabled' => true]],
            'Verify the scope of `missing-docs`, it is not limited to properties.',
        ],
        'Squiz.NamingConventions.ValidFunctionName' => [
            [
                'function-name' => ['enabled' => true],
                'method-name' => ['enabled' => true],
            ],
            null,
        ],
        'Squiz.NamingConventions.ValidVariableName' => [
            [
                'variable-name' => ['enabled' => true],
                'property-name' => ['enabled' => true],
            ],
            null,
        ],
        'Squiz.PHP.DisallowMultipleAssignments' => [['no-multi-assignments' => ['enabled' => true]], null],
    ];

    /**
     * Sniffs Mago has no counterpart for.
     *
     * @var array<string, string>
     */
    private const UNSUPPORTED = [
        'Generic.Commenting.DocComment' => 'Mago does not reformat PHPDoc blocks, see the `valid-docblock` linter rule instead.',
        'Generic.ControlStructures.DisallowYodaConditions' => 'Mago only offers the inverse `yoda-conditions` linter rule.',
        'PEAR.Commenting.FileComment' => 'Mago neither inserts nor validates file header comments.',
        'Squiz.Commenting.FileComment' => 'Mago neither inserts nor validates file header comments.',
        'Squiz.Commenting.InlineComment' => 'Mago does not reformat comments, see the `no-hash-comment` linter rule for the comment style.',
    ];

    public function lookup(Rule $rule): Mapping
    {
        return (
            $this->lookupStandard($rule) ?? $this->lookupConfigurable($rule) ?? $this->lookupStatic(
                $rule,
            ) ?? Mapping::unknown(
                $rule->name,
                'No mapping known yet, check `mago lint --list-rules` and the formatter reference.',
            )
        );
    }

    private function lookupStandard(Rule $rule): ?Mapping
    {
        if (!$rule->isRuleSet()) {
            return null;
        }

        if (isset(self::STANDARDS[$rule->name])) {
            [$preset, $note] = self::STANDARDS[$rule->name];

            return (
                $preset === null
                    ? Mapping::coveredByFormatter($rule->name, $note)
                    : Mapping::formatterOptions($rule->name, ['preset' => $preset], $note)
            );
        }

        if (isset(self::STANDARDS_WITHOUT_PRESET[$rule->name])) {
            return Mapping::unsupported($rule->name, self::STANDARDS_WITHOUT_PRESET[$rule->name]);
        }

        return Mapping::unknown(
            $rule->name,
            'Third party standard without a Mago preset, migrate the sniffs you rely on individually.',
        );
    }

    /**
     * Sniffs whose Mago counterpart depends on the properties of the sniff.
     */
    private function lookupConfigurable(Rule $rule): ?Mapping
    {
        return match ($this->sniff($rule)) {
            'Generic.Arrays.DisallowLongArraySyntax' => Mapping::linterRules(
                $rule->name,
                ['array-style' => ['enabled' => true]],
                'Verify that the style option of the `array-style` rule asks for short arrays.',
            ),
            'Generic.Arrays.DisallowShortArraySyntax' => Mapping::linterRules(
                $rule->name,
                ['array-style' => ['enabled' => true]],
                'Verify that the style option of the `array-style` rule asks for long arrays.',
            ),
            'Generic.Files.LineEndings' => Mapping::formatterOptions($rule->name, [
                'end-of-line' => ($rule->options['eolChar'] ?? "\n") === "\r\n" ? 'crlf' : 'lf',
            ]),
            'Generic.Files.LineLength' => Mapping::formatterOptions($rule->name, [
                'print-width' => $rule->intOption('lineLimit', 120) ?? 120,
            ]),
            'Generic.Metrics.CyclomaticComplexity' => Mapping::linterRules(
                $rule->name,
                ['cyclomatic-complexity' => ['enabled' => true]],
                \sprintf(
                    'The sniff allowed a complexity of %d, verify the threshold of the `cyclomatic-complexity` rule.',
                    $rule->intOption('complexity', 10) ?? 10,
                ),
            ),
            'Generic.Metrics.NestingLevel' => Mapping::linterRules(
                $rule->name,
                ['excessive-nesting' => ['enabled' => true]],
                \sprintf(
                    'The sniff allowed a nesting level of %d, verify the threshold of the `excessive-nesting` rule.',
                    $rule->intOption('nestingLevel', 5) ?? 5,
                ),
            ),
            'Generic.WhiteSpace.ScopeIndent' => Mapping::formatterOptions(
                $rule->name,
                \array_filter(
                    [
                        'use-tabs' => $rule->boolOption('tabIndent', false),
                        'tab-width' => $rule->intOption('indent'),
                    ],
                    static fn(int|bool|null $value): bool => $value !== null,
                ),
            ),
            'Squiz.Strings.ConcatenationSpacing' => Mapping::formatterOptions($rule->name, [
                'space-around-concatenation-binary-operator' => ($rule->intOption('spacing', 0) ?? 0) > 0,
            ]),
            default => null,
        };
    }

    private function lookupStatic(Rule $rule): ?Mapping
    {
        $sniff = $this->sniff($rule);

        if (isset(self::FORMATTER_OPTIONS[$sniff])) {
            return Mapping::formatterOptions($rule->name, self::FORMATTER_OPTIONS[$sniff]);
        }

        if (isset(self::LINTER_RULES[$sniff])) {
            [$rules, $note] = self::LINTER_RULES[$sniff];

            return Mapping::linterRules($rule->name, $rules, $note);
        }

        if (\array_key_exists($sniff, self::COVERED_BY_FORMATTER)) {
            return Mapping::coveredByFormatter($rule->name, self::COVERED_BY_FORMATTER[$sniff]);
        }

        if (isset(self::UNSUPPORTED[$sniff])) {
            return Mapping::unsupported($rule->name, self::UNSUPPORTED[$sniff]);
        }

        return null;
    }

    /**
     * Reduces a `Standard.Category.Sniff.ErrorCode` reference to its sniff.
     */
    private function sniff(Rule $rule): string
    {
        $segments = \explode('.', $rule->name);

        return \count($segments) > 3 ? \implode('.', \array_slice($segments, 0, 3)) : $rule->name;
    }
}
