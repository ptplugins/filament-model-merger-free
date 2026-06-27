# Filament Model Merger — Free

> Free FilamentPHP tool to merge duplicate records. A row action opens a modal — pick the duplicate from a dropdown, confirm, and its **HasMany** and **HasOne** relations transfer onto the record you keep, then the duplicate is deleted.

<p align="center" class="filament-hidden">
  <a href="https://ptplugins.com/buy-us-a-beer"><img src="https://img.shields.io/badge/%F0%9F%8D%BA-Buy%20us%20a%20beer-yellow" alt="Buy us a beer"></a>
</p>

This is the **free edition**. It handles the everyday case — a couple of duplicate rows with simple parent/child relations. When you need polymorphic & many-to-many relations, a full audit trail, conflict resolution and bulk merging, reach for **[Model Merger Pro](https://ptplugins.com/filament-model-merger)**.

## Free vs Pro

| | Free | [Pro](https://ptplugins.com/filament-model-merger) |
|---|:---:|:---:|
| Merge row action (modal + dropdown) | ✅ | ✅ |
| `HasMany` / `HasOne` relations | ✅ | ✅ |
| Polymorphic relations (`MorphOne` / `MorphMany`) | 🛠️ custom | ✅ |
| Many-to-many (`BelongsToMany` / `MorphToMany`) | 🛠️ custom | ✅ |
| Side-by-side three-way merge page (pick each value) | — | ✅ |
| Conflict resolution (choose winning values) | — | ✅ |
| Merge audit log + snapshots | — | ✅ |
| Configurable delete strategy | — | ✅ |
| Bulk merge | — | ✅ |

**✅ built-in · 🛠️ custom** = supported in Free by writing a small `RelationMerger` yourself ([see below](#extending-free-with-custom-relation-mergers)) · **—** = Pro only.

Skipped relations are reported back after each merge, so you always know what Free left untouched.

## Filament compatibility

This package ships a separate release series per Filament generation. With the package added, `composer require ptplugins/filament-model-merger-free` resolves to the series that matches the Filament version already installed in your app — or pin it explicitly:

| Filament | Release series | Explicit install |
|----------|----------------|------------------|
| v3 | `3.x` | `composer require ptplugins/filament-model-merger-free:"^3.0"` |
| v4 / v5 | `4.x` | `composer require ptplugins/filament-model-merger-free:"^4.0"` |

## Installation

```bash
composer require ptplugins/filament-model-merger-free
```

No migrations, no config, no pages — the package auto-registers its translations and the merge action carries its own modal.

## Usage

Add merging to any Filament Resource in two steps.

### 1. Add the trait and declare which relations to transfer

```php
use PtPlugins\FilamentModelMergerFree\Concerns\HasMergeableFields;

class CustomerResource extends Resource
{
    use HasMergeableFields;

    /** Relations transferred from the duplicate into the record you keep. */
    public static function getMergeableRelations(): array
    {
        return ['orders', 'profile']; // HasMany / HasOne only in Free
    }
}
```

### 2. Add the merge action to your table

```php
use PtPlugins\FilamentModelMergerFree\Actions\MergeAction;

public static function table(Table $table): Table
{
    return $table->actions([
        MergeAction::make(),
        // ...
    ]);
}
```

That's it — each row gets a **Merge** action. It opens a small modal with a dropdown to pick the duplicate; on confirm, the duplicate's `HasMany` / `HasOne` relations are transferred onto the row and the duplicate is deleted. Skipped relations are reported back.

> Want a side-by-side, field-by-field **three-way merge** on a dedicated page (pick each value from either record)? That's [Model Merger Pro](https://ptplugins.com/filament-model-merger).

### Optional merge hooks

Implement `Mergeable` on your model to run logic before/after a merge:

```php
use PtPlugins\FilamentModelMergerFree\Contracts\Mergeable;

class Customer extends Model implements Mergeable
{
    public function beforeMerge(Model $target): bool
    {
        return true; // return false to abort
    }

    public function afterMerge(Model $target): void
    {
        // recalculate totals, fire events, ...
    }
}
```

## Extending Free with custom relation mergers

Free transfers `HasMany` and `HasOne` out of the box. Many-to-many and polymorphic relations are marked **🛠️ custom** above because you can teach Free to transfer them yourself — the engine resolves each relation through a chain of `RelationMerger` strategies, and you can register your own:

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use PtPlugins\FilamentModelMergerFree\Contracts\RelationMerger;
use PtPlugins\FilamentModelMergerFree\Support\RelationMergeResult;

class BelongsToManyRelationMerger implements RelationMerger
{
    public function supports(Relation $relation): bool
    {
        return $relation instanceof BelongsToMany;
    }

    public function transfer(Model $primary, Model $target, string $relationName): RelationMergeResult
    {
        $ids = $target->{$relationName}()->allRelatedIds()->all();

        $primary->{$relationName}()->syncWithoutDetaching($ids);

        return new RelationMergeResult(count($ids));
    }
}
```

Register it once — typically from a service provider's `boot()`:

```php
use PtPlugins\FilamentModelMergerFree\ModelMergerFree;

ModelMergerFree::extend(new BelongsToManyRelationMerger());
```

Custom mergers run **before** the built-ins, so you can also override how a built-in relation type is transferred. More recipes (deduplicating pivot rows, polymorphic transfers) are on the [PtPlugins blog](https://ptplugins.com/blog).

> Prefer batteries-included? **[Model Merger Pro](https://ptplugins.com/filament-model-merger)** ships polymorphic & many-to-many transfers, conflict resolution, an audit trail and bulk merge with zero code.

## Upgrade to Pro

Need polymorphic & many-to-many transfers, an audit trail, conflict resolution or bulk merge? **[Model Merger Pro →](https://ptplugins.com/filament-model-merger)**

## Credits

Built by [PtPlugins](https://ptplugins.com). Licensed under MIT.
