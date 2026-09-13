<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Mapping;

/**
 * How faithfully a translated rule survived the migration.
 *
 * Only mappings which end up in the target configuration carry a fidelity, the other
 * {@see MappingOutcome} cases have nothing to be faithful to.
 */
enum MappingFidelity: string
{
    /** The target setting enforces the same thing as the source rule. */
    case Equivalent = 'equivalent';

    /** The target setting comes close, but its scope or its options need a review. */
    case Partial = 'partial';

    /**
     * A translation which needed a caveat is a partial one.
     *
     * Mapping tables state their caveats as notes, which makes the note the default
     * signal for a partial mapping. Pass a fidelity explicitly to a named constructor
     * of {@see Mapping} when a note is purely informational.
     */
    public static function ofNote(?string $note): self
    {
        return $note === null ? self::Equivalent : self::Partial;
    }

    public function label(): string
    {
        return $this->value;
    }
}
