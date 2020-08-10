<?php

namespace mPhpMaster\MigrationSnapshot;

use mPhpMaster\MigrationSnapshot\Commands\MigrateDumpWithDataCommand;
use mPhpMaster\MigrationSnapshot\Commands\MigrateDumpCommand;
use mPhpMaster\MigrationSnapshot\Commands\MigrateLoadCommand;

class MigrationSnapshotServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/migration-snapshot.php' => config_path('migration-snapshot.php'),
            ], 'config');

            $this->commands([
                MigrateDumpWithDataCommand::class,
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
