<?php

namespace PtPlugins\FilamentModelMergerFree;

use Filament\Contracts\Plugin;
use Filament\Panel;

class ModelMergerFreePlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }

    public function getId(): string
    {
        return 'model-merger-free';
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
