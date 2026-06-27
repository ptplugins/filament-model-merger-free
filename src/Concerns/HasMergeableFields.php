<?php

namespace PtPlugins\FilamentModelMergerFree\Concerns;

trait HasMergeableFields
{
    /**
     * Relations transferred from the duplicate into the kept record during a merge.
     *
     * Free transfers HasMany and HasOne; other relation types are reported as
     * skipped (or teach Free to handle them with a custom RelationMerger). This is
     * the single source of truth read by MergeAction.
     *
     * @return array<string> Relation method names
     */
    public static function getMergeableRelations(): array
    {
        return [];
    }
}
