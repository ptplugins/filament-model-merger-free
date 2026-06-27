<?php

namespace PtPlugins\FilamentModelMergerFree;

use Illuminate\Support\ServiceProvider;

class ModelMergerFreeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'model-merger-free');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/lang' => $this->app->langPath('vendor/model-merger-free'),
            ], 'model-merger-free-translations');
        }
    }
}
