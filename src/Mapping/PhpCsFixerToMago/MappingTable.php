<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Mapping\PhpCsFixerToMago;

use Stolt\CodingStandardMigrator\Mapping\Mago\RuleTable;
use Stolt\CodingStandardMigrator\Mapping\Mapping;
use Stolt\CodingStandardMigrator\Ruleset\Rule;

/**
 * The knowledge of which PHP-CS-Fixer rule corresponds to which Mago setting.
 *
 * Only mappings which could be verified against the Mago documentation are listed
 * here. Everything else deliberately falls through to {@see Mapping::unknown()} so
 * a migration never invents a Mago option which does not exist.
 *
 * @see https://mago.carthage.software/main/en/tools/formatter/configuration-reference/
 * @see https://mago.carthage.software/main/en/tools/linter/rules/
 */
final class MappingTable implements RuleTable
{
    /**
     * PHP-CS-Fixer rule sets and the Mago formatter preset which comes closest.
     *
     * @var array<string, array{0: string|null, 1: string|null}>
     */
    private const RULE_SETS = [
        '@PSR1' => ['psr-12', 'Mago has no PSR-1 preset, the PSR-12 preset is a superset of it.'],
        '@PSR2' => ['psr-12', 'Mago has no PSR-2 preset, the PSR-12 preset is a superset of it.'],
        '@PSR12' => ['psr-12', null],
        '@PER' => [null, 'The Mago formatter follows PER-CS by default, no preset needed.'],
        '@PER-CS' => [null, 'The Mago formatter follows PER-CS by default, no preset needed.'],
        '@PER-CS1.0' => [null, 'The Mago formatter follows the latest PER-CS by default.'],
        '@PER-CS2.0' => [null, 'The Mago formatter follows the latest PER-CS by default.'],
        '@PER-CS3.0' => [null, 'The Mago formatter follows the latest PER-CS by default.'],
        '@Laravel' => ['laravel', null],
    ];

    /**
     * Rules the Mago formatter applies unconditionally, no Mago setting needed.
     *
     * @var array<string, string|null>
     */
    private const COVERED_BY_FORMATTER = [
        'array_indentation' => null,
        'binary_operator_spaces' => null,
        'blank_line_between_import_groups' => 'See the `separate-use-types` formatter option to opt out.',
        'braces' => 'Superseded by `braces_position`, see the `*-brace-style` formatter options.',
        'compact_nullable_type_declaration' => null,
        'compact_nullable_typehint' => null,
        'elseif' => null,
        'encoding' => null,
        'full_opening_tag' => null,
        'function_declaration' => null,
        'indentation_type' => 'Driven by the `use-tabs` and `tab-width` formatter options.',
        'line_ending' => 'Driven by the `end-of-line` formatter option.',
        'method_argument_space' => null,
        'no_closing_tag' => 'Driven by the `remove-trailing-close-tag` formatter option.',
        'no_multiline_whitespace_around_double_arrow' => null,
        'no_singleline_whitespace_before_semicolons' => null,
        'no_spaces_after_function_name' => null,
        'no_trailing_whitespace' => null,
        'no_trailing_whitespace_in_comment' => null,
        'no_whitespace_before_comma_in_array' => null,
        'no_whitespace_in_blank_line' => null,
        'normalize_index_brace' => null,
        'return_type_declaration' => null,
        'single_blank_line_at_eof' => null,
        'single_line_after_imports' => 'See the `empty-line-after-use` formatter option.',
        'spaces_inside_parentheses' => null,
        'statement_indentation' => null,
        'switch_case_semicolon_to_colon' => null,
        'switch_case_space' => null,
        'ternary_operator_spaces' => null,
        'type_declaration_spaces' => null,
        'unary_operator_spaces' => null,
        'whitespace_after_comma_in_array' => null,
    ];

    /**
     * Rules with a static, option independent Mago formatter counterpart.
     *
     * @var array<string, array<string, scalar>>
     */
    private const FORMATTER_OPTIONS = [
        'blank_line_after_namespace' => ['empty-line-after-namespace' => true],
        'blank_line_after_opening_tag' => ['empty-line-after-opening-tag' => true],
        'blank_line_before_return' => ['empty-line-before-return' => true],
        'class_attributes_separation' => ['separate-class-like-members' => true],
        'heredoc_indentation' => ['indent-heredoc' => true],
        'linebreak_after_opening_tag' => ['opening-tag-on-own-line' => true],
        'new_with_braces' => ['parentheses-in-new-expression' => true],
        'new_with_parentheses' => ['parentheses-in-new-expression' => true],
        'no_unneeded_braces' => ['parentheses-in-exit-and-die' => true],
        'not_operator_with_space' => ['space-after-logical-not-unary-prefix-operator' => true],
        'single_import_per_statement' => ['expand-use-groups' => true],
        'single_quote' => ['single-quote' => true],
        'trailing_comma_in_multiline' => ['trailing-comma' => true],
    ];

    /**
     * Rules with a Mago linter counterpart.
     *
     * @var array<string, array{0: array<string, array<string, mixed>>, 1: string|null}>
     */
    private const LINTER_RULES = [
        'explicit_string_variable' => [['braced-string-interpolation' => ['enabled' => true]], null],
        'lowercase_cast' => [['lowercase-type-hint' => ['enabled' => true]], null],
        'lowercase_keywords' => [['lowercase-keyword' => ['enabled' => true]], null],
        'lowercase_static_reference' => [['lowercase-keyword' => ['enabled' => true]], null],
        'lowercase_type_declaration' => [['lowercase-type-hint' => ['enabled' => true]], null],
        'modernize_strpos' => [
            [
                'str-contains' => ['enabled' => true],
                'str-starts-with' => ['enabled' => true],
            ],
            null,
        ],
        'no_alias_functions' => [['no-alias-function' => ['enabled' => true]], null],
        'no_alternative_syntax' => [['no-alternative-syntax' => ['enabled' => true]], null],
        'no_useless_else' => [
            ['prefer-early-return' => ['enabled' => true]],
            'Not an exact match, `prefer-early-return` is broader.',
        ],
        'octal_notation' => [['explicit-octal' => ['enabled' => true]], null],
        'php_unit_attributes' => [['prefer-test-attribute' => ['enabled' => true]], null],
        'php_unit_dedicate_assert' => [['use-specific-assertions' => ['enabled' => true]], null],
        'php_unit_strict' => [['strict-assertions' => ['enabled' => true]], null],
        'single_class_element_per_statement' => [
            ['no-multi-assignments' => ['enabled' => true]],
            'Not an exact match, verify against your code base.',
        ],
        'static_lambda' => [['prefer-static-closure' => ['enabled' => true]], null],
        'use_arrow_functions' => [['prefer-arrow-function' => ['enabled' => true]], null],
    ];

    /**
     * Rules Mago has no counterpart for.
     *
     * @var array<string, string>
     */
    private const UNSUPPORTED = [
        'header_comment' => 'Mago neither inserts nor validates file header comments.',
        'no_php4_constructor' => 'Not covered by the Mago linter, handle it with a one-off refactoring.',
        'phpdoc_align' => 'Mago does not reformat PHPDoc blocks, see the `valid-docblock` linter rule instead.',
        'phpdoc_indent' => 'Mago does not reformat PHPDoc blocks, see the `valid-docblock` linter rule instead.',
        'phpdoc_order' => 'Mago does not reformat PHPDoc blocks, see the `valid-docblock` linter rule instead.',
        'phpdoc_separation' => 'Mago does not reformat PHPDoc blocks, see the `valid-docblock` linter rule instead.',
        'phpdoc_trim' => 'Mago does not reformat PHPDoc blocks, see the `valid-docblock` linter rule instead.',
    ];

    public function lookup(Rule $rule): Mapping
    {
        return (
            $this->lookupRuleSet($rule) ?? $this->lookupConfigurable($rule) ?? $this->lookupStatic(
                $rule,
            ) ?? Mapping::unknown(
                $rule->name,
                'No mapping known yet, check `mago lint --list-rules` and the formatter reference.',
            )
        );
    }

    private function lookupRuleSet(Rule $rule): ?Mapping
    {
        if (!$rule->isRuleSet()) {
            return null;
        }

        if (isset(self::RULE_SETS[$rule->name])) {
            [$preset, $note] = self::RULE_SETS[$rule->name];

            return (
                $preset === null
                    ? Mapping::coveredByFormatter($rule->name, $note)
                    : Mapping::formatterOptions($rule->name, ['preset' => $preset], $note)
            );
        }

        if (\preg_match('/^@PHP\d+Migration/', $rule->name) === 1) {
            return Mapping::unsupported(
                $rule->name,
                'Set the `php-version` key in mago.toml and let the Mago analyser report outdated constructs.',
            );
        }

        return Mapping::unknown(
            $rule->name,
            'Rule set without a Mago preset, expand it via `php-cs-fixer describe` and migrate its rules.',
        );
    }

    /**
     * Rules whose Mago counterpart depends on the options of the source rule.
     */
    private function lookupConfigurable(Rule $rule): ?Mapping
    {
        return match ($rule->name) {
            'array_syntax' => Mapping::linterRules(
                $rule->name,
                ['array-style' => ['enabled' => true]],
                \sprintf(
                    'Configured as "%s" in PHP-CS-Fixer, verify the style option of the `array-style` rule.',
                    $rule->optionAmong('syntax', ['short', 'long'], 'short') ?? 'short',
                ),
            ),
            'braces_position' => Mapping::formatterOptions($rule->name, $this->bracePlacement($rule)),
            'cast_spaces' => Mapping::formatterOptions($rule->name, [
                'space-after-cast-unary-prefix-operators' =>
                    $rule->optionAmong('space', ['single', 'none'], 'single') === 'single',
            ]),
            'concat_space' => Mapping::formatterOptions($rule->name, [
                'space-around-concatenation-binary-operator' =>
                    $rule->optionAmong('spacing', ['one', 'none'], 'none') === 'one',
            ]),
            'constant_case' => $rule->optionAmong('case', ['lower', 'upper'], 'lower') === 'upper'
                ? Mapping::formatterOptions($rule->name, ['uppercase-literal-keyword' => true])
                : Mapping::formatterOptions($rule->name, ['uppercase-literal-keyword' => false]),
            'declare_equal_normalize' => Mapping::formatterOptions($rule->name, [
                'space-around-assignment-in-declare' =>
                    $rule->optionAmong('space', ['single', 'none'], 'none') === 'single',
            ]),
            'increment_style' => $rule->optionAmong('style', ['pre', 'post'], 'pre') === 'pre'
                ? Mapping::linterRules($rule->name, ['prefer-pre-increment' => ['enabled' => true]])
                : Mapping::unsupported($rule->name, 'Mago only offers the inverse `prefer-pre-increment` linter rule.'),
            'nullable_type_declaration' => Mapping::formatterOptions($rule->name, [
                'null-type-hint' => $rule->optionAmong('syntax', ['question_mark', 'union'], 'question_mark')
                === 'union'
                    ? 'null_pipe'
                    : 'question',
            ]),
            'ordered_class_elements' => Mapping::formatterOptions(
                $rule->name,
                ['sort-class-methods' => true],
                'Mago only sorts methods, not properties, constants, or cases.',
            ),
            'ordered_imports' => Mapping::formatterOptions($rule->name, [
                'sort-uses' => match ($rule->optionAmong('sort_algorithm', ['alpha', 'length', 'none'], 'alpha')) {
                    'length' => 'length-ascending',
                    'none' => 'preserve',
                    default => 'alphanumeric-ascending',
                },
            ]),
            'single_line_empty_body' => Mapping::formatterOptions($rule->name, [
                'inline-empty-function-braces' => true,
                'inline-empty-method-braces' => true,
                'inline-empty-classlike-braces' => true,
            ]),
            'yoda_style' => $this->yodaStyle($rule),
            default => null,
        };
    }

    private function lookupStatic(Rule $rule): ?Mapping
    {
        if (isset(self::FORMATTER_OPTIONS[$rule->name])) {
            return Mapping::formatterOptions($rule->name, self::FORMATTER_OPTIONS[$rule->name]);
        }

        if (isset(self::LINTER_RULES[$rule->name])) {
            [$rules, $note] = self::LINTER_RULES[$rule->name];

            return Mapping::linterRules($rule->name, $rules, $note);
        }

        if (\array_key_exists($rule->name, self::COVERED_BY_FORMATTER)) {
            return Mapping::coveredByFormatter($rule->name, self::COVERED_BY_FORMATTER[$rule->name]);
        }

        if (isset(self::UNSUPPORTED[$rule->name])) {
            return Mapping::unsupported($rule->name, self::UNSUPPORTED[$rule->name]);
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function bracePlacement(Rule $rule): array
    {
        $placements = [
            'control_structures_opening_brace' => 'control-brace-style',
            'anonymous_functions_opening_brace' => 'closure-brace-style',
            'functions_opening_brace' => 'function-brace-style',
            'classes_opening_brace' => 'classlike-brace-style',
        ];

        $options = [];

        foreach ($placements as $sourceOption => $targetOption) {
            $placement = $rule->optionAmong($sourceOption, ['same_line', 'next_line_unless_newline_at_signature_end']);

            if ($placement !== null) {
                $options[$targetOption] = $placement === 'same_line' ? 'same-line' : 'next-line';
            }
        }

        return $options;
    }

    private function yodaStyle(Rule $rule): Mapping
    {
        $equal = $rule->options['equal'] ?? true;
        $identical = $rule->options['identical'] ?? true;

        if ($equal === false && $identical === false) {
            return Mapping::unsupported(
                $rule->name,
                'Mago only offers the `yoda-conditions` linter rule, it cannot enforce non-Yoda conditions.',
            );
        }

        return Mapping::linterRules($rule->name, ['yoda-conditions' => ['enabled' => true]]);
    }
}
