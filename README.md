# hlaCk Laravel Migration Snapshot helper By hlaCk
 
- Use `composer` to install this library: `composer require mphpmaster/migration-snapshot`.
- `db:dump-data`: Dump current database data as plain-text SQL file.
- `db:load-data`: Load current database data from plain-text SQL file.
- `migrate:dump`: Dump current database schema/structure as plain-text SQL file.
- `migrate:load`: Load current database schema/structure from plain-text SQL file.
- Schema file path: `migrations/sql/schema.sql`.
- Data file path: `migrations/sql/data.sql`.
- Register provider for old laravels: add `"mPhpMaster\\MigrationSnapshot\\ServiceProvider",` to `config/app.php`.



## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

The Laravel Migration Snapshot Helper is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
