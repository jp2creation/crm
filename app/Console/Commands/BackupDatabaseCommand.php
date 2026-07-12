<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDO;
use Throwable;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:run
        {--connection= : Connexion base de donnees a sauvegarder}
        {--disk= : Disque Laravel de destination}
        {--path= : Dossier de destination sur le disque}
        {--keep= : Nombre de sauvegardes a conserver}';

    protected $description = 'Create a compressed SQL backup of the CRM database.';

    public function handle(): int
    {
        $connectionName = (string) ($this->option('connection') ?: config('database.default'));
        $disk = (string) ($this->option('disk') ?: config('crm.backup.disk', 'local'));
        $path = trim((string) ($this->option('path') ?: config('crm.backup.path', 'backups/database')), '/');
        $keep = max(1, (int) ($this->option('keep') ?: config('crm.backup.keep', 14)));

        try {
            $sql = $this->dumpConnection($connectionName);
            $file = $path.'/'.$connectionName.'-'.now()->format('Ymd-His').'.sql.gz';
            $compressed = gzencode($sql, 6);

            if ($compressed === false) {
                $this->error('Compression gzip impossible.');

                return self::FAILURE;
            }

            Storage::disk($disk)->put($file, $compressed);
            $this->pruneOldBackups($disk, $path, $keep);
        } catch (Throwable $exception) {
            $this->error('Sauvegarde impossible : '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Sauvegarde creee : '.$disk.':'.$file);

        return self::SUCCESS;
    }

    private function dumpConnection(string $connectionName): string
    {
        $connection = DB::connection($connectionName);
        $driver = $connection->getDriverName();
        $pdo = $connection->getPdo();

        return match ($driver) {
            'mysql', 'mariadb' => $this->dumpMysql($pdo, $connection->getDatabaseName()),
            'sqlite' => $this->dumpSqlite($pdo, $connection->getDatabaseName()),
            default => throw new \RuntimeException("Driver non supporte : {$driver}"),
        };
    }

    private function dumpMysql(PDO $pdo, string $database): string
    {
        $lines = $this->header($database, 'mysql');
        $lines[] = 'SET FOREIGN_KEY_CHECKS=0;';

        $tables = collect($pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM))
            ->filter(fn (array $row): bool => ($row[1] ?? '') === 'BASE TABLE')
            ->map(fn (array $row): string => (string) $row[0])
            ->values();

        foreach ($tables as $table) {
            $identifier = $this->mysqlIdentifier($table);
            $create = $pdo->query('SHOW CREATE TABLE '.$identifier)->fetch(PDO::FETCH_ASSOC);
            $createSql = (string) ($create['Create Table'] ?? array_values($create ?: [])[1] ?? '');

            $lines[] = '';
            $lines[] = 'DROP TABLE IF EXISTS '.$identifier.';';
            $lines[] = $createSql.';';
            $this->appendInserts($pdo, $lines, $table, fn (string $value): string => $this->mysqlIdentifier($value));
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';

        return implode("\n", $lines)."\n";
    }

    private function dumpSqlite(PDO $pdo, string $database): string
    {
        $lines = $this->header($database, 'sqlite');
        $lines[] = 'PRAGMA foreign_keys=OFF;';

        $tables = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")
            ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tables as $table) {
            $name = (string) $table['name'];
            $identifier = $this->sqliteIdentifier($name);

            $lines[] = '';
            $lines[] = 'DROP TABLE IF EXISTS '.$identifier.';';
            $lines[] = rtrim((string) $table['sql'], ';').';';
            $this->appendInserts($pdo, $lines, $name, fn (string $value): string => $this->sqliteIdentifier($value));
        }

        $lines[] = 'PRAGMA foreign_keys=ON;';

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function appendInserts(PDO $pdo, array &$lines, string $table, callable $identifier): void
    {
        $tableIdentifier = $identifier($table);
        $statement = $pdo->query('SELECT * FROM '.$tableIdentifier);

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $columns = array_keys($row);
            $values = array_map(fn (mixed $value): string => $this->sqlValue($pdo, $value), array_values($row));

            $lines[] = 'INSERT INTO '.$tableIdentifier.' ('
                .implode(', ', array_map($identifier, $columns))
                .') VALUES ('.implode(', ', $values).');';
        }
    }

    private function sqlValue(PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $pdo->quote((string) $value) ?: "''";
    }

    /**
     * @return array<int, string>
     */
    private function header(string $database, string $driver): array
    {
        return [
            '-- Martin Sols CRM database backup',
            '-- Driver: '.$driver,
            '-- Database: '.$database,
            '-- Created at: '.now()->toDateTimeString(),
            '',
        ];
    }

    private function mysqlIdentifier(string $value): string
    {
        return '`'.str_replace('`', '``', $value).'`';
    }

    private function sqliteIdentifier(string $value): string
    {
        return '"'.str_replace('"', '""', $value).'"';
    }

    private function pruneOldBackups(string $disk, string $path, int $keep): void
    {
        $files = collect(Storage::disk($disk)->files($path))
            ->filter(fn (string $file): bool => str_ends_with($file, '.sql.gz'))
            ->sortDesc()
            ->values();

        foreach ($files->slice($keep) as $file) {
            Storage::disk($disk)->delete($file);
        }
    }
}
