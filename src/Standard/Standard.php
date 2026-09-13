<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Standard;

use Stolt\CodingStandardMigrator\Exception\UnknownStandard;

/**
 * The coding standards this engine knows about.
 *
 * Being listed here does not imply support. A standard is only usable once a
 * reader, a writer, or a mapper is registered for it in the {@see StandardRegistry},
 * which is what the later iterations for PHP_CodeSniffer and Pint will add.
 */
enum Standard: string
{
    case PhpCsFixer = 'php-cs-fixer';
    case Mago = 'mago';
    case PhpCodeSniffer = 'phpcs';
    case Pint = 'pint';

    public static function fromName(string $name): self
    {
        $standard = self::tryFrom(\strtolower(\trim($name)));

        if ($standard === null) {
            throw UnknownStandard::named($name, self::names());
        }

        return $standard;
    }

    /**
     * @return non-empty-list<string>
     */
    public static function names(): array
    {
        return \array_map(static fn(self $standard): string => $standard->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::PhpCsFixer => 'PHP-CS-Fixer',
            self::Mago => 'Mago',
            self::PhpCodeSniffer => 'PHP_CodeSniffer',
            self::Pint => 'Laravel Pint',
        };
    }
}
