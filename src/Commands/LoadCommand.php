<?php

namespace mPhpMaster\MigrationSnapshot\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;

final class LoadCommand extends Command
{
    protected $signature = 'db:load-data
        {--database= : The database connection to use}
        {--t|tables : Do re-migrate tables before loading data}
        {--force : Force the operation to run when in production}';

    protected $description = 'Load current database data from plain-text SQL file.';

    protected function configure()
    {
        $this->setAliases([
            'dld'
        ]);
    }

    public function handle()
    {
        $exit_code = null;

        if (
            ! $this->option('force')
            && app()->environment('production')
            && ! $this->confirm('Are you sure you want to load the DB data from a file?')
        ) {
            return;
        }

        $database = $this->option('database') ?: DB::getDefaultConnection();
        DB::setDefaultConnection($database);
        $db_config = DB::getConfig();

        if (! in_array($db_config['driver'], MigrateDumpCommand::SUPPORTED_DB_DRIVERS, true)) {
            throw new InvalidArgumentException(
                'Unsupported DB driver ' . var_export($db_config['driver'], 1)
            );
        }

        // Delegate to driver-specific restore/load CLI command.
        // ASSUMES: Restore utilities for DBMS installed and in path.
        // CONSIDER: Accepting options for underlying restore utilities from CLI.
        // CONSIDER: Option to restore to console Stdout instead.
        $method = $db_config['driver'] . 'Load';

        // CONSIDER: Moving option to `migrate:dump` instead.
        $is_dropping = $this->option('tables');

        if ($is_dropping) {

            $path = database_path() . MigrateDumpCommand::SCHEMA_SQL_PATH_SUFFIX;
            if (! file_exists($path)) {
                throw new InvalidArgumentException(
                    'Schema-migrations path not found, run `migrate:dump` first.'
                );
            }

            \Schema::dropAllViews();
            \Schema::dropAllTables();
            // TODO: Drop others too: sequences, types, etc.
            $this->info('Dropped old tables and views');

            $exit_code = self::{$method}($path, $db_config, $this->getOutput()->getVerbosity());

            if (0 !== $exit_code) {
                exit($exit_code); // CONSIDER: Returning instead.
            }

            $this->info('Loaded schema');
        }

        $this->info('Loading default data...');
        $data_path = database_path() . MigrateDumpCommand::DATA_SQL_PATH_SUFFIX;
        if (!file_exists($data_path)) {
            $data_path = realpath($data_path);
            $this->error("Data file not found [{$data_path}] ...");
            return ;
        }

        $exit_code = self::{$method}($data_path, $db_config, $this->getOutput()->getVerbosity());

        if (0 !== $exit_code) {
            exit($exit_code); // CONSIDER: Returning instead.
        }

        $this->info('Loaded default data successfully!');
    }

    /**
     * @param string $path
     * @param array $db_config
     * @param int|null $verbosity
     *
     * @return int
     */
    private static function mysqlLoad(string $path, array $db_config, int $verbosity = null) : int
    {
        // CONSIDER: Supporting unix_socket.
        // CONSIDER: Directly sending queries via Eloquent (requires parsing SQL
        // or intermediate format).
        // CONSIDER: Capturing Stderr and outputting with `$this->error()`.

        // CONSIDER: Making input file an option which can override default.
        // CONSIDER: Avoiding shell specifics like `cat` and piping using
        // `file_get_contents` or similar.
        $command = 'cat ' . escapeshellarg($path)
            . ' | mysql --no-beep'
            . ' --host=' . escapeshellarg($db_config['host'])
            . ' --port=' . escapeshellarg($db_config['port'])
            . ' --user=' . escapeshellarg($db_config['username'])
            . ' --password=' . escapeshellarg($db_config['password'])
            . ' --database=' . escapeshellarg($db_config['database']);
        switch($verbosity) {
            case OutputInterface::VERBOSITY_QUIET:
                $command .= ' -q';
                break;
            case OutputInterface::VERBOSITY_NORMAL:
                // No op.
                break;
            case OutputInterface::VERBOSITY_VERBOSE:
                $command .= ' -v';
                break;
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $command .= ' -v -v';
                break;
            case OutputInterface::VERBOSITY_DEBUG:
                $command .= ' -v -v -v';
                break;
        }

        passthru($command, $exit_code);

        return $exit_code;
    }

    /**
     * @param string $path
     * @param array $db_config
     * @param int|null $verbosity
     *
     * @return int
     */
    private static function pgsqlLoad(string $path, array $db_config, int $verbosity = null) : int
    {
        // CONSIDER: Supporting unix_socket.
        // CONSIDER: Directly sending queries via Eloquent (requires parsing SQL
        // or intermediate format).
        // CONSIDER: Capturing Stderr and outputting with `$this->error()`.

        // CONSIDER: Making input file an option which can override default.
        $command = 'PGPASSWORD=' . escapeshellarg($db_config['password'])
            . ' psql --file=' . escapeshellarg($path)
            . ' --host=' . escapeshellarg($db_config['host'])
            . ' --port=' . escapeshellarg($db_config['port'])
            . ' --username=' . escapeshellarg($db_config['username'])
            . ' --dbname=' . escapeshellarg($db_config['database']);
        switch($verbosity) {
            case OutputInterface::VERBOSITY_QUIET:
                $command .= ' --quiet --output=/dev/null';
                break;
            case OutputInterface::VERBOSITY_NORMAL:
                // By default psql outputs command results like "SET".
                $command .= ' --output=/dev/null';
                break;
            case OutputInterface::VERBOSITY_VERBOSE:
                // No op.
                break;
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $command .= ' --echo-errors';
                break;
            case OutputInterface::VERBOSITY_DEBUG:
                $command .= ' --echo-all';
                break;
        }

        passthru($command, $exit_code);

        return $exit_code;
    }

    /**
     * @param string $path
     * @param array $db_config
     * @param int|null $verbosity
     *
     * @return int
     */
    private static function sqliteLoad(string $path, array $db_config, int $verbosity = null) : int
    {
        // CONSIDER: Directly sending queries via Eloquent (requires parsing SQL
        // or intermediate format).
        // CONSIDER: Capturing Stderr and outputting with `$this->error()`.

        $command = 'sqlite3 ' . escapeshellarg($db_config['database']) . ' ' . escapeshellarg(".read $path");

        passthru($command, $exit_code);

        return $exit_code;
    }
}
