<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Exception;

use InvalidArgumentException;

final class UnknownStandard extends InvalidArgumentException implements Exception
{
    /**
     * @param list<string> $known
     */
    public static function named(string $name, array $known): self
    {
        return new self(\sprintf(
            'Unknown coding standard "%s". Known standards are %s.',
            $name,
            \implode(', ', $known),
        ));
    }
}
