<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Reader;

use Stolt\CodingStandardMigrator\Exception\InvalidSourceConfiguration;
use Stolt\CodingStandardMigrator\Exception\SourceConfigurationNotFound;
use Stolt\CodingStandardMigrator\Ruleset\Ruleset;
use Stolt\CodingStandardMigrator\Ruleset\SourceConfiguration;
use Stolt\CodingStandardMigrator\Standard\Standard;
use Throwable;

/**
 * Reads a `.php-cs-fixer.php` style configuration file.
 *
 * The configuration is read by including the file and inspecting its return value,
 * which is either a `PhpCsFixer\Config` instance or, as a convenience, a plain array
 * of rules. Only the accessors of the returned object are used, so this package does
 * not depend on friendsofphp/php-cs-fixer itself.
 */
final readonly class PhpCsFixerConfigurationReader implements ConfigurationReader
{
    use GuessesSourcePaths;

    public function standard(): Standard
    {
        return Standard::PhpCsFixer;
    }

    public function configurationFileNames(): array
    {
        return ['.php-cs-fixer.php', '.php-cs-fixer.dist.php', '.php_cs', '.php_cs.dist'];
    }

    public function read(string $file): SourceConfiguration
    {
        if (!\is_file($file)) {
            throw SourceConfigurationNotFound::atPath($file);
        }

        $configuration = $this->include($file);

        if (\is_array($configuration)) {
            return new SourceConfiguration(
                standard: $this->standard(),
                file: $file,
                ruleset: Ruleset::fromArray($configuration),
                paths: $this->guessPaths($file),
            );
        }

        if (!\is_object($configuration) || !\method_exists($configuration, 'getRules')) {
            throw InvalidSourceConfiguration::unexpectedReturnValue($file, \get_debug_type($configuration));
        }

        $rules = $configuration->getRules();

        if (!\is_array($rules)) {
            throw InvalidSourceConfiguration::unexpectedRulesType($file, \get_debug_type($rules));
        }

        return new SourceConfiguration(
            standard: $this->standard(),
            file: $file,
            ruleset: Ruleset::fromArray($rules),
            indent: $this->stringAccessor($configuration, 'getIndent'),
            lineEnding: $this->stringAccessor($configuration, 'getLineEnding'),
            riskyAllowed: $this->boolAccessor($configuration, 'getRiskyAllowed'),
            paths: $this->guessPaths($file),
        );
    }

    /**
     * @throws InvalidSourceConfiguration
     */
    private function include(string $file): mixed
    {
        try {
            return (static fn(string $configurationFile): mixed => require $configurationFile)($file);
        } catch (Throwable $failure) {
            throw new InvalidSourceConfiguration(
                \sprintf(
                    'Failed to read %s: %s. Configuration files referencing PHP-CS-Fixer classes '
                    . 'require friendsofphp/php-cs-fixer to be installed.',
                    $file,
                    $failure->getMessage(),
                ),
                previous: $failure,
            );
        }
    }

    private function stringAccessor(object $configuration, string $accessor): ?string
    {
        if (!\method_exists($configuration, $accessor)) {
            return null;
        }

        $value = $configuration->{$accessor}();

        return \is_string($value) && $value !== '' ? $value : null;
    }

    private function boolAccessor(object $configuration, string $accessor): bool
    {
        return \method_exists($configuration, $accessor) && $configuration->{$accessor}() === true;
    }
}
