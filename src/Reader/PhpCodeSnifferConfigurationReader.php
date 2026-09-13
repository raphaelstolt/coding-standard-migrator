<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Reader;

use SimpleXMLElement;
use Stolt\CodingStandardMigrator\Exception\InvalidSourceConfiguration;
use Stolt\CodingStandardMigrator\Exception\SourceConfigurationNotFound;
use Stolt\CodingStandardMigrator\Ruleset\Ruleset;
use Stolt\CodingStandardMigrator\Ruleset\SourceConfiguration;
use Stolt\CodingStandardMigrator\Standard\Standard;

/**
 * Reads a `phpcs.xml` style ruleset.
 *
 * Referenced standards such as `<rule ref="PSR12"/>` are normalised into rule sets,
 * sniffs keep their `Standard.Category.Sniff` name. Sniffs silenced through a
 * `<severity>0</severity>` or an `<exclude name="…"/>` become disabled rules, so that
 * the migration can carry the opt out over.
 *
 * @see https://github.com/PHPCSStandards/PHP_CodeSniffer/wiki/Annotated-Ruleset
 */
final readonly class PhpCodeSnifferConfigurationReader implements ConfigurationReader
{
    use GuessesSourcePaths;

    public function standard(): Standard
    {
        return Standard::PhpCodeSniffer;
    }

    public function configurationFileNames(): array
    {
        return ['phpcs.xml', 'phpcs.xml.dist', '.phpcs.xml', '.phpcs.xml.dist', 'ruleset.xml'];
    }

    public function read(string $file): SourceConfiguration
    {
        if (!\is_file($file)) {
            throw SourceConfigurationNotFound::atPath($file);
        }

        $ruleset = $this->parse($file);
        $paths = $this->paths($ruleset);

        return new SourceConfiguration(
            standard: $this->standard(),
            file: $file,
            ruleset: Ruleset::fromArray($this->rules($ruleset)),
            indent: $this->indent($ruleset),
            phpVersion: $this->phpVersion($ruleset),
            paths: $paths !== [] ? $paths : $this->guessPaths($file),
        );
    }

    /**
     * @throws InvalidSourceConfiguration
     */
    private function parse(string $file): SimpleXMLElement
    {
        $contents = \file_get_contents($file);

        if ($contents === false) {
            throw InvalidSourceConfiguration::unparsable($file, 'the file could not be read');
        }

        $useInternalErrors = \libxml_use_internal_errors(true);
        $ruleset = \simplexml_load_string($contents);
        $errors = \libxml_get_errors();
        \libxml_clear_errors();
        \libxml_use_internal_errors($useInternalErrors);

        if ($ruleset === false) {
            throw InvalidSourceConfiguration::unparsable(
                $file,
                \trim($errors[0]->message ?? 'the file does not hold valid XML'),
            );
        }

        return $ruleset;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function rules(SimpleXMLElement $ruleset): array
    {
        $rules = [];

        foreach ($ruleset->rule as $rule) {
            $reference = $this->attribute($rule, 'ref');

            if ($reference === null) {
                continue;
            }

            $name = $this->normalizeReference($reference);
            $properties = $this->properties($rule);

            $rules[$name] = match (true) {
                $this->isSilenced($rule) => false,
                $properties === [] => true,
                default => $properties,
            };

            foreach ($rule->exclude as $exclude) {
                $excluded = $this->attribute($exclude, 'name');

                if ($excluded !== null) {
                    $rules[$this->normalizeReference($excluded)] = false;
                }
            }
        }

        return $rules;
    }

    /**
     * A reference without a dot addresses a whole standard, which is the PHP_CodeSniffer
     * equivalent of a rule set.
     */
    private function normalizeReference(string $reference): string
    {
        $isStandard =
            !\str_contains($reference, '.') && !\str_contains($reference, '/') && !\str_contains($reference, '\\');

        return $isStandard ? '@' . $reference : $reference;
    }

    private function isSilenced(SimpleXMLElement $rule): bool
    {
        return isset($rule->severity) && \trim((string) $rule->severity) === '0';
    }

    /**
     * @return array<string, mixed>
     */
    private function properties(SimpleXMLElement $rule): array
    {
        $properties = [];

        if (!isset($rule->properties)) {
            return $properties;
        }

        foreach ($rule->properties->property as $property) {
            $name = $this->attribute($property, 'name');

            if ($name === null) {
                continue;
            }

            $properties[$name] = isset($property->element)
                ? $this->elements($property)
                : $this->normalizeValue($this->attribute($property, 'value') ?? '');
        }

        return $properties;
    }

    /**
     * @return list<bool|int|string>
     */
    private function elements(SimpleXMLElement $property): array
    {
        $elements = [];

        foreach ($property->element as $element) {
            $value = $this->attribute($element, 'value');

            if ($value !== null) {
                $elements[] = $this->normalizeValue($value);
            }
        }

        return $elements;
    }

    /**
     * Ruleset values are always strings, including the escape sequences of line endings.
     */
    private function normalizeValue(string $value): bool|int|string
    {
        return match (true) {
            $value === 'true' => true,
            $value === 'false' => false,
            \preg_match('/^-?\d+$/', $value) === 1 => (int) $value,
            default => \str_replace(['\r', '\n', '\t'], ["\r", "\n", "\t"], $value),
        };
    }

    /**
     * @return list<string>
     */
    private function paths(SimpleXMLElement $ruleset): array
    {
        $paths = [];

        foreach ($ruleset->file as $file) {
            $path = \trim((string) $file);

            if ($path !== '') {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * The tab width doubles as the indent, the indent style itself is decided by the
     * referenced sniffs, whose mappings take precedence over this one.
     */
    private function indent(SimpleXMLElement $ruleset): ?string
    {
        $tabWidth = $this->argument($ruleset, 'tab-width');

        if ($tabWidth === null || \preg_match('/^\d+$/', $tabWidth) !== 1) {
            return null;
        }

        $width = (int) $tabWidth;

        return $width > 0 ? \str_repeat(' ', $width) : null;
    }

    /**
     * Turns the `php_version` of a ruleset, e.g. 80300, into a version, e.g. 8.3.
     */
    private function phpVersion(SimpleXMLElement $ruleset): ?string
    {
        foreach ($ruleset->config as $configuration) {
            if ($this->attribute($configuration, 'name') !== 'php_version') {
                continue;
            }

            $value = $this->attribute($configuration, 'value');

            if ($value === null || \preg_match('/^\d{5,6}$/', $value) !== 1) {
                continue;
            }

            $version = (int) $value;

            return \sprintf('%d.%d', \intdiv($version, 10_000), \intdiv($version % 10_000, 100));
        }

        return null;
    }

    private function argument(SimpleXMLElement $ruleset, string $name): ?string
    {
        foreach ($ruleset->arg as $argument) {
            if ($this->attribute($argument, 'name') === $name) {
                return $this->attribute($argument, 'value');
            }
        }

        return null;
    }

    private function attribute(SimpleXMLElement $element, string $name): ?string
    {
        $value = $element[$name];

        return $value !== null ? (string) $value : null;
    }
}
