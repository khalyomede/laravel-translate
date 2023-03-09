<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Khalyomede\LaravelTranslate\TranslateServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    use CanTestJson;
    use LazilyRefreshDatabase;

    /**
     * @param Application $app
     */
    protected function getPackageProviders($app): array
    {
        return [
            TranslateServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . "/misc/database/migrations");
    }
}
