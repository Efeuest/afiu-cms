<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

use RuntimeException;

final class MigrationRunner
{
    public function __construct(
        private readonly Database $db,
        private readonly Config $config
    ) {}

    public function run(): array
    {
        /*
         * MySQL implicitly commits transactions around many DDL statements
         * such as CREATE TABLE and ALTER TABLE. Therefore migrations must not
         * be wrapped in Database::transaction() at the runner level.
         *
         * Each migration is expected to be safe to run once, and it is only
         * recorded after its callable completes successfully.
         */
        $this->db->pdo()->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(190) NOT NULL UNIQUE,
                ran_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $ran = array_column(
            $this->db->all('SELECT migration FROM migrations'),
            'migration'
        );

        $executed = [];
        $path = rtrim((string) $this->config->get('paths.migrations'), '/');
        $files = glob($path . '/*.php') ?: [];
        sort($files, SORT_STRING);

        foreach ($files as $file) {
            $name = basename($file, '.php');

            if (in_array($name, $ran, true)) {
                continue;
            }

            $migration = require $file;
            if (!is_callable($migration)) {
                throw new RuntimeException("Migration {$name} must return a callable.");
            }

            $migration($this->db);
            $this->db->execute(
                'INSERT INTO migrations (migration) VALUES (?)',
                [$name]
            );

            $executed[] = $name;
        }

        return $executed;
    }
}
