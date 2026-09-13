<?php

declare(strict_types=1);

/*
 * A stand-in for a PhpCsFixer\Config instance, so the test suite does not need
 * friendsofphp/php-cs-fixer to be installed. The reader only relies on these accessors.
 */
return new class () {
    /**
     * @return array<string, bool|array<string, mixed>>
     */
    public function getRules(): array
    {
        return [
            '@PSR12' => true,
            'array_syntax' => ['syntax' => 'short'],
            'concat_space' => ['spacing' => 'one'],
            'single_quote' => true,
            'yoda_style' => true,
            'no_trailing_whitespace' => true,
            'header_comment' => ['header' => 'Anything'],
            'phpdoc_align' => true,
            'native_function_invocation' => true,
            'php_unit_strict' => false,
        ];
    }

    public function getIndent(): string
    {
        return '    ';
    }

    public function getLineEnding(): string
    {
        return "\n";
    }

    public function getRiskyAllowed(): bool
    {
        return true;
    }
};
