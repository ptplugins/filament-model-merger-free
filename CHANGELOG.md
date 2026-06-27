# Changelog

All notable changes to `ptplugins/filament-model-merger-free` will be documented in this file.

## 1.0.0

- Initial release.
- Filament merge page + table action to merge two duplicate records side-by-side.
- Transfers `HasMany` and `HasOne` relations (keep-primary strategy).
- Auto delete strategy: soft-delete the target when the model uses `SoftDeletes`, hard-delete otherwise.
- Polymorphic (`MorphOne`/`MorphMany`) and many-to-many (`BelongsToMany`/`MorphToMany`) relations are reported as skipped — those require [Model Merger Pro](https://ptplugins.com/filament-model-merger).
