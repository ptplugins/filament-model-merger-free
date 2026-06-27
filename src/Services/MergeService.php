<?php

namespace PtPlugins\FilamentModelMergerFree\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;
use PtPlugins\FilamentModelMergerFree\Contracts\Mergeable;
use PtPlugins\FilamentModelMergerFree\ModelMergerFree;

/**
 * Free merge engine.
 *
 * Transfers relations from the target record to the primary record (keep-primary
 * strategy), then deletes the target. Which relation types are transferred is
 * delegated to the registered RelationMerger strategies: Free ships HasMany and
 * HasOne mergers, while many-to-many and polymorphic relations have no built-in
 * merger and are reported back as "skipped" — unless the host app teaches Free
 * to handle them via ModelMergerFree::extend(). There is no audit log, no
 * conflict resolution and no configurable delete strategy here; those live in
 * Model Merger Pro (https://ptplugins.com/filament-model-merger).
 */
class MergeService
{
    /** @var array<string, int> */
    protected array $transferLog = [];

    /** @var array<int, string> */
    protected array $skipped = [];

    /** @var array<int, string> */
    protected array $conflicts = [];

    protected bool $targetWasSoftDeleted = false;

    /**
     * Merge target record into primary record.
     *
     * @param  Model  $primary  The record that will be kept
     * @param  Model  $target  The record that will be merged and deleted
     * @param  array<string>  $relations  Relation method names to transfer
     * @return array{transferred: array<string, int>, skipped: array<int, string>, conflicts: array<int, string>, target_soft_deleted: bool}
     *
     * @throws InvalidArgumentException
     */
    public function merge(Model $primary, Model $target, array $relations): array
    {
        $this->validate($primary, $target);
        $this->transferLog = [];
        $this->skipped = [];
        $this->conflicts = [];
        $this->targetWasSoftDeleted = false;

        if ($primary instanceof Mergeable && ! $primary->beforeMerge($target)) {
            throw new InvalidArgumentException('Merge aborted by beforeMerge hook.');
        }

        $primary->getConnection()->transaction(function () use ($primary, $target, $relations) {
            foreach ($relations as $relationName) {
                $this->transferRelation($primary, $target, $relationName);
            }

            $this->deleteTarget($target);
        });

        if ($primary instanceof Mergeable) {
            $primary->afterMerge($target);
        }

        return [
            'transferred' => $this->transferLog,
            'skipped' => $this->skipped,
            'conflicts' => $this->conflicts,
            'target_soft_deleted' => $this->targetWasSoftDeleted,
        ];
    }

    protected function validate(Model $primary, Model $target): void
    {
        if ($primary->getTable() !== $target->getTable()) {
            throw new InvalidArgumentException('Primary and target must be the same model type.');
        }

        if ($primary->getKey() === $target->getKey()) {
            throw new InvalidArgumentException('Cannot merge a record with itself.');
        }

        if (! $primary->exists || ! $target->exists) {
            throw new InvalidArgumentException('Both primary and target records must exist.');
        }
    }

    protected function transferRelation(Model $primary, Model $target, string $relationName): void
    {
        if (! method_exists($target, $relationName)) {
            throw new InvalidArgumentException("Relation [{$relationName}] does not exist on the model.");
        }

        $relation = $target->{$relationName}();

        foreach (ModelMergerFree::mergers() as $merger) {
            if (! $merger->supports($relation)) {
                continue;
            }

            $result = $merger->transfer($primary, $target, $relationName);

            $this->transferLog[$relationName] = $result->transferred;

            if ($result->conflict) {
                $this->conflicts[] = $relationName;
            }

            return;
        }

        // No registered merger handles this relation type (e.g. BelongsToMany or
        // polymorphic relations in Free). Report it as skipped so the UI can
        // hint at Pro — or teach Free via ModelMergerFree::extend(new ...).
        $this->skipped[] = $relationName;
    }

    protected function deleteTarget(Model $target): void
    {
        if ($this->modelUsesSoftDeletes($target)) {
            $target->delete();
            $this->targetWasSoftDeleted = true;

            return;
        }

        $target->delete();
        $this->targetWasSoftDeleted = false;
    }

    protected function modelUsesSoftDeletes(Model $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model));
    }
}
