<?php

namespace mPhpMaster\MigrationSnapshot\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class MigrateDumpWithDataCommand extends Command
{
    protected $signature = 'migrate:ddump
        {--database= : The database connection to use}
        ';

    protected $description = 'Dump current database schema/structure/data as plain-text SQL file.';

    public function handle()
    {
        return \Artisan::call('migrate:dump', $this->option(), $this->output);
        
        return $this->call('migrate:dump', [
            '--include-data' => true,
            '' => $this->option('database')
        ]);
    }

}
