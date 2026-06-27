<?php

namespace PtPlugins\FilamentModelMergerFree;

use PtPlugins\FilamentModelMergerFree\Contracts\RelationMerger;
use PtPlugins\FilamentModelMergerFree\Mergers\HasManyRelationMerger;
use PtPlugins\FilamentModelMergerFree\Mergers\HasOneRelationMerger;

/**
 * Static entry point for extending the Free merge engine.
 *
 * The Free edition transfers HasMany and HasOne relations out of the box.
 * Register custom relation mergers (e.g. for BelongsToMany or polymorphic
 * relations) from a service provider's boot() method:
 *
 *     ModelMergerFree::extend(new BelongsToManyRelationMerger());
 *
 * Custom mergers take precedence over the two built-in mergers, so you can also
 * override the default transfer behaviour for a built-in relation type.
 */
class ModelMergerFree
{
    /** @var array<int, RelationMerger> */
    protected static array $customMergers = [];

    public static function extend(RelationMerger $merger): void
    {
        static::$customMergers[] = $merger;
    }

    /**
     * The ordered merger chain used by the merge engine: custom mergers first
     * (so they can override defaults), then the two built-in Free mergers.
     *
     * @return array<int, RelationMerger>
     */
    public static function mergers(): array
    {
        return [
            ...static::$customMergers,
            new HasOneRelationMerger,
            new HasManyRelationMerger,
        ];
    }

    /**
     * Remove all registered custom mergers. Primarily useful in tests.
     */
    public static function flushMergers(): void
    {
        static::$customMergers = [];
    }
}
