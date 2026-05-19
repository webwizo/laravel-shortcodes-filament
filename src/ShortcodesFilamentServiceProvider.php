<?php

namespace Webwizo\ShortcodesFilament;

use Illuminate\Support\ServiceProvider;
use Webwizo\ShortcodesFilament\Console\Commands\AddTenantColumnCommand;
use Webwizo\ShortcodesFilament\Models\Shortcode;
use Webwizo\ShortcodesFilament\Shortcodes\DynamicShortcode;
use Webwizo\Shortcodes\Facades\Shortcode as ShortcodeFacade;

class ShortcodesFilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/shortcodes-filament.php',
            'shortcodes-filament'
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                AddTenantColumnCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../config/shortcodes-filament.php' => config_path('shortcodes-filament.php'),
        ], 'shortcodes-filament-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'shortcodes-filament-migrations');

        if (! $this->app->runningInConsole()) {
            $this->registerDynamicShortcodes();
        }

        Shortcode::saved(fn () => $this->registerDynamicShortcodes());
        Shortcode::deleted(fn () => $this->registerDynamicShortcodes());
    }

    protected function registerDynamicShortcodes(): void
    {
        try {
            $model = config('shortcodes-filament.model');

            $model::where('is_active', true)
                ->get()
                ->each(function ($shortcode) {
                    ShortcodeFacade::register(
                        $shortcode->tag,
                        new DynamicShortcode($shortcode)
                    );
                });
        } catch (\Throwable $e) {
            logger()->error('[ShortcodesFilament] Failed to register dynamic shortcodes: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}