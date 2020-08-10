<?php

namespace mPhpMaster\MigrationSnapshot;

use mPhpMaster\MigrationSnapshot\Commands\LoadCommand;
use mPhpMaster\MigrationSnapshot\Commands\MigrateDumpCommand;
use mPhpMaster\MigrationSnapshot\Commands\DumpDataCommand;
use mPhpMaster\MigrationSnapshot\Commands\MigrateLoadCommand;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/migration-snapshot.php' => config_path('migration-snapshot.php'),
            ], 'config');

            $this->commands([
                LoadCommand::class,
                DumpDataCommand::class,
                MigrateDumpCommand::class,
                MigrateLoadCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/migration-snapshot.php', 'migration-snapshot');

        $this->app->register(EventServiceProvider::class);
    }
}
