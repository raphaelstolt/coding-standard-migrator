<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Reader;

/**
 * Guesses the paths a coding standard is applied to.
 *
 * Neither PHP-CS-Fixer nor Pint expose the paths they run on, the former keeps them in
 * a `Symfony\Component\Finder\Finder` instance, the latter defaults to the whole
 * project. Readers of such configurations fall back to the directories which exist next
 * to the configuration file.
 */
trait GuessesSourcePaths
{
    /** @var non-empty-list<string> */
    private const COMMON_PATHS = ['src', 'lib', 'app', 'tests', 'test'];

    /**
     * @return list<string>
     */
    private function guessPaths(string $file): array
    {
        $directory = \dirname($file);

        return \array_values(\array_filter(self::COMMON_PATHS, static fn(string $path): bool => \is_dir(
            $directory . \DIRECTORY_SEPARATOR . $path,
        )));
    }
}
