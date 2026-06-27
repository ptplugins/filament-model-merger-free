<?php

namespace PtPlugins\FilamentModelMergerFree\Mergers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use PtPlugins\FilamentModelMergerFree\Contracts\RelationMerger;
use PtPlugins\FilamentModelMergerFree\Support\RelationMergeResult;

/**
 * Re-points every child row of a HasMany relation from the target to the
 * primary record.
 *
 * MorphMany extends HasMany, so it is explicitly excluded here — transferring
 * polymorphic children is a Pro feature (and can be added in Free via a custom
 * RelationMerger).
 */
class HasManyRelationMerger implements RelationMerger
{
    public function supports(Relation $relation): bool
    {
        return $relation instanceof HasMany && ! $relation instanceof MorphOneOrMany;
    }

    public function transfer(Model $primary, Model $target, string $relationName): RelationMergeResult
    {
        $foreignKey = $target->{$relationName}()->getForeignKeyName();

        $count = $target->{$relationName}()->update([
            $foreignKey => $primary->getKey(),
        ]);

        return new RelationMergeResult($count);
    }
}
