<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Reader;

use JsonException;
use Stolt\CodingStandardMigrator\Exception\InvalidSourceConfiguration;
use Stolt\CodingStandardMigrator\Exception\SourceConfigurationNotFound;
use Stolt\CodingStandardMigrator\Ruleset\Ruleset;
use Stolt\CodingStandardMigrator\Ruleset\SourceConfiguration;
use Stolt\CodingStandardMigrator\Standard\Standard;

/**
 * Reads a `pint.json` configuration file.
 *
 * Pint configures PHP-CS-Fixer rules, so its `rules` are taken over as they are. Its
 * `preset` is normalised into the rule set the same set of rules is known as in
 * PHP-CS-Fixer, which keeps both migrations on one mapping table.
 *
 * @see https://laravel.com/docs/pint
 */
final readonly class PintConfigurationReader implements ConfigurationReader
{
    use GuessesSourcePaths;

    /**
     * The Pint presets and their PHP-CS-Fixer rule set counterpart, `empty` has none.
     *
     * @var array<string, string|null>
     */
    private const PRESETS = [
        'laravel' => '@Laravel',
        'per' => '@PER-CS',
        'psr12' => '@PSR12',
        'symfony' => '@Symfony',
        'empty' => null,
    ];

    public function standard(): Standard
    {
        return Standard::Pint;
    }

    public function configurationFileNames(): array
    {
        return ['pint.json', '.pint.json'];
    }

    public function read(string $file): SourceConfiguration
    {
        if (!\is_file($file)) {
            throw SourceConfigurationNotFound::atPath($file);
        }

        $contents = \file_get_contents($file);

        if ($contents === false) {
            throw InvalidSourceConfiguration::unparsable($file, 'the file could not be read');
        }

        try {
            $decoded = \json_decode($contents, true, flags: \JSON_THROW_ON_ERROR);
        } catch (JsonException $failure) {
            throw InvalidSourceConfiguration::unparsable($file, $failure->getMessage());
        }

        if (!\is_array($decoded)) {
            throw InvalidSourceConfiguration::unexpectedReturnValue($file, \get_debug_type($decoded));
        }

        return new SourceConfiguration(
            standard: $this->standard(),
            file: $file,
            ruleset: Ruleset::fromArray($this->rules($file, $decoded)),
            paths: $this->guessPaths($file),
        );
    }

    /**
     * @param array<array-key, mixed> $configuration
     *
     * @return array<array-key, mixed>
     */
    private function rules(string $file, array $configuration): array
    {
        $rules = [];
        $preset = $configuration['preset'] ?? null;

        if (\is_string($preset) && $preset !== '') {
            // An unknown preset is kept as a rule set, so that it shows up in the
            // migration report instead of being dropped silently.
            $ruleSet = \array_key_exists(\strtolower($preset), self::PRESETS)
                ? self::PRESETS[\strtolower($preset)]
                : '@' . $preset;

            if ($ruleSet !== null) {
                $rules[$ruleSet] = true;
            }
        }

        $configured = $configuration['rules'] ?? [];

        if (!\is_array($configured)) {
            throw InvalidSourceConfiguration::unexpectedRulesType($file, \get_debug_type($configured));
        }

        foreach ($configured as $rule => $options) {
            $rules[$rule] = $options;
        }

        return $rules;
    }
}
