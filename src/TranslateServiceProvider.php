<?php

namespace Khalyomede\LaravelTranslate;

use Illuminate\Support\ServiceProvider;
use Khalyomede\LaravelTranslate\Commands\Translate;

final class TranslateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Translate::class,
            ]);
        }

        $this->mergeConfigFrom(__DIR__ . "/config/translate.php", "translate");

        $this->publishes([
            __DIR__ . "/config/translate.php" => config_path("translate.php"),
        ], "translate");
    }
}
