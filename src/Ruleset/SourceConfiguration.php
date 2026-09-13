<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Ruleset;

use Stolt\CodingStandardMigrator\Standard\Standard;

/**
 * Everything a reader was able to extract from a source configuration file.
 *
 * Next to the {@see Ruleset} this carries the handful of settings which live
 * outside of the rules themselves, but still influence the migrated output.
 */
final readonly class SourceConfiguration
{
    /**
     * @param string|null $indent      the literal indent, e.g. "    " or "\t"
     * @param string|null $lineEnding  the literal line ending, e.g. "\n"
     * @param string|null $phpVersion  the PHP version the standard was configured for
     * @param list<string> $paths      the directories the standard is applied to
     */
    public function __construct(
        public Standard $standard,
        public string $file,
        public Ruleset $ruleset,
        public ?string $indent = null,
        public ?string $lineEnding = null,
        public bool $riskyAllowed = false,
        public ?string $phpVersion = null,
        public array $paths = [],
    ) {}

    /**
     * @param list<string> $paths
     */
    public function withPaths(array $paths): self
    {
        return new self(
            standard: $this->standard,
            file: $this->file,
            ruleset: $this->ruleset,
            indent: $this->indent,
            lineEnding: $this->lineEnding,
            riskyAllowed: $this->riskyAllowed,
            phpVersion: $this->phpVersion,
            paths: $paths,
        );
    }

    public function usesTabs(): bool
    {
        return $this->indent !== null && \str_contains($this->indent, "\t");
    }

    public function indentWidth(): ?int
    {
        if ($this->indent === null || $this->usesTabs()) {
            return null;
        }

        $width = \strlen($this->indent);

        return $width > 0 ? $width : null;
    }
}
