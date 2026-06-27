<?php

namespace PtPlugins\FilamentModelMergerFree\Support;

/**
 * Outcome of transferring a single relation during a merge.
 *
 * @property-read int $transferred Number of related records moved onto the primary
 * @property-read bool $conflict Whether the relation could not be transferred because the primary already owned one
 */
final class RelationMergeResult
{
    public function __construct(
        public readonly int $transferred,
        public readonly bool $conflict = false,
    ) {}
}
