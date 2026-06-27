<?php

namespace PtPlugins\FilamentModelMergerFree\Actions;

use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use PtPlugins\FilamentModelMergerFree\Services\MergeService;

/**
 * Per-row "Merge" action. Opens a lightweight modal with a dropdown to pick the
 * duplicate record, then transfers its HasMany / HasOne relations onto this
 * record (keep-primary) and deletes the duplicate. The whole flow lives in the
 * modal — no separate page to register.
 *
 * The side-by-side, field-by-field three-way merge is the Pro experience
 * (https://ptplugins.com/filament-model-merger).
 */
class MergeAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->name('merge');
        $this->label(fn (): string => __('model-merger-free::messages.merge'));
        $this->icon('heroicon-o-arrows-right-left');
        $this->color('warning');

        $this->modalHeading(fn (): string => __('model-merger-free::messages.merge_record'));
        $this->modalDescription(fn (): string => __('model-merger-free::messages.merge_confirm_description'));
        $this->modalSubmitActionLabel(fn (): string => __('model-merger-free::messages.merge'));

        $this->form(fn (Model $record, $livewire): array => [
            Select::make('target')
                ->label(__('model-merger-free::messages.target_record'))
                ->placeholder(__('model-merger-free::messages.select_target'))
                ->options(self::targetOptions($record, $livewire))
                ->searchable()
                ->required(),
        ]);

        $this->action(function (Model $record, array $data, $livewire): void {
            $target = $record->newQuery()->whereKey($data['target'])->first();

            if (! $target || $target->getKey() === $record->getKey()) {
                Notification::make()
                    ->title(__('model-merger-free::messages.no_target_selected'))
                    ->danger()
                    ->send();

                return;
            }

            $result = app(MergeService::class)->merge($record, $target, self::mergeableRelations($livewire));

            Notification::make()
                ->title(__('model-merger-free::messages.merge_success'))
                ->body(self::summary($result))
                ->success()
                ->send();
        });
    }

    /**
     * Every other record of the same model, labelled by the resource's title attribute.
     *
     * @return array<int|string, string>
     */
    protected static function targetOptions(Model $record, $livewire): array
    {
        return $record->newQuery()
            ->whereKeyNot($record->getKey())
            ->pluck(self::titleAttribute($livewire, $record), $record->getKeyName())
            ->all();
    }

    /**
     * @return array<string>
     */
    protected static function mergeableRelations($livewire): array
    {
        $resource = self::resource($livewire);

        return ($resource && method_exists($resource, 'getMergeableRelations'))
            ? $resource::getMergeableRelations()
            : [];
    }

    protected static function titleAttribute($livewire, Model $record): string
    {
        $resource = self::resource($livewire);

        if ($resource && method_exists($resource, 'getRecordTitleAttribute') && $resource::getRecordTitleAttribute()) {
            return $resource::getRecordTitleAttribute();
        }

        return $record->getKeyName();
    }

    protected static function resource($livewire): ?string
    {
        $class = is_object($livewire) ? $livewire::class : $livewire;

        return method_exists($class, 'getResource') ? $class::getResource() : null;
    }

    /**
     * @param  array{transferred: array<string, int>, skipped: array<int, string>, conflicts: array<int, string>, target_soft_deleted: bool}  $result
     */
    protected static function summary(array $result): string
    {
        $lines = [];

        foreach ($result['transferred'] as $relation => $count) {
            $lines[] = __('model-merger-free::messages.relations_transferred', ['count' => $count, 'relation' => $relation]);
        }

        if (! empty($result['skipped'])) {
            $lines[] = __('model-merger-free::messages.relations_skipped_pro', ['count' => count($result['skipped'])]);
        }

        $lines[] = $result['target_soft_deleted']
            ? __('model-merger-free::messages.target_soft_deleted')
            : __('model-merger-free::messages.target_deleted');

        return implode("\n", $lines);
    }

    public static function getDefaultName(): ?string
    {
        return 'merge';
    }
}
