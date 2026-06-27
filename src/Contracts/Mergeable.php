<?php

namespace PtPlugins\FilamentModelMergerFree\Contracts;

use Illuminate\Database\Eloquent\Model;

interface Mergeable
{
    /**
     * Called before the merge operation.
     * Return false to abort the merge.
     */
    public function beforeMerge(Model $target): bool;

    /**
     * Called after the merge operation completes.
     */
    public function afterMerge(Model $target): void;
}
