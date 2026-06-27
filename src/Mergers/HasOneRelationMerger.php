<?php

namespace PtPlugins\FilamentModelMergerFree\Mergers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use PtPlugins\FilamentModelMergerFree\Contracts\RelationMerger;
use PtPlugins\FilamentModelMergerFree\Support\RelationMergeResult;

/**
 * Transfers a HasOne relation when the primary record does not already own one.
 *
 * If the primary already has its own, the relation is reported as a conflict
 * and left untouched — interactive conflict resolution (choosing the winning
 * record) is a Pro feature.
 */
class HasOneRelationMerger implements RelationMerger
{
    public function supports(Relation $relation): bool
    {
        return $relation instanceof HasOne;
    }

    public function transfer(Model $primary, Model $target, string $relationName): RelationMergeResult
    {
        $targetRecord = $target->{$relationName}()->first();

        if (! $targetRecord) {
            return new RelationMergeResult(0);
        }

        if ($primary->{$relationName}()->exists()) {
            return new RelationMergeResult(0, conflict: true);
        }

        $foreignKey = $target->{$relationName}()->getForeignKeyName();

        $targetRecord->update([$foreignKey => $primary->getKey()]);

        return new RelationMergeResult(1);
    }
}
