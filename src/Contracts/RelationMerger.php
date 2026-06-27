<?php

namespace PtPlugins\FilamentModelMergerFree\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use PtPlugins\FilamentModelMergerFree\Support\RelationMergeResult;

/**
 * Strategy for transferring one relation type from a target record onto the
 * primary record during a merge.
 *
 * The Free edition ships two built-in mergers (HasMany and HasOne). Register
 * your own to teach Free how to transfer many-to-many or polymorphic relations:
 *
 *     ModelMergerFree::extend(new BelongsToManyRelationMerger());
 *
 * A relation with no supporting merger is reported back as "skipped" after the
 * merge, so nothing is ever transferred silently.
 */
interface RelationMerger
{
    /**
     * Whether this merger handles the given relation instance.
     */
    public function supports(Relation $relation): bool;

    /**
     * Transfer the relation's records from $target onto $primary.
     */
    public function transfer(Model $primary, Model $target, string $relationName): RelationMergeResult;
}
