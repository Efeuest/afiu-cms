<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class Database
{
    private ?PDO $pdo = null;

    public function __construct(private readonly Config $config) {}

    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $database = (string) $this->config->get('database.database', '');
        if ($database === '') {
            throw new RuntimeException('Database is not configured.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $this->config->get('database.host', '127.0.0.1'),
            (int) $this->config->get('database.port', 3306),
            $database
        );

        try {
            $this->pdo = new PDO(
                $dsn,
                (string) $this->config->get('database.username'),
                (string) $this->config->get('database.password'),
                self::pdoOptions()
            );
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Database connection failed: ' . $e->getMessage(),
                0,
                $e
            );
        }

        return $this->pdo;
    }

    public function one(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function all(string $sql, array $params = []): array
    {
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function insertId(): int
    {
        return (int) $this->pdo()->lastInsertId();
    }

    public function transaction(callable $callback): mixed
    {
        $pdo = $this->pdo();
        $ownsTransaction = !$pdo->inTransaction();

        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $result = $callback($this);

            // Only the code that opened the transaction may close it.
            // MySQL can implicitly end transactions around DDL; therefore
            // inTransaction() must be checked immediately before commit.
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->commit();
            }

            return $result;
        } catch (Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    public static function testAndCreate(array $data): void
    {
        self::assertMysqlDriver();

        $database = (string) ($data['database'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) {
            throw new RuntimeException(
                'Database name may contain only letters, numbers and underscores.'
            );
        }

        $host = (string) ($data['host'] ?? '127.0.0.1');
        $port = (int) ($data['port'] ?? 3306);
        $username = (string) ($data['username'] ?? '');
        $password = (string) ($data['password'] ?? '');

        // Prefer connecting directly to an existing database. This means a
        // normal application user does not need global CREATE DATABASE rights.
        try {
            new PDO(
                sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                    $host,
                    $port,
                    $database
                ),
                $username,
                $password,
                self::pdoOptions()
            );
            return;
        } catch (PDOException $e) {
            $mysqlCode = (int) ($e->errorInfo[1] ?? 0);

            // 1049 = Unknown database. Only in this case do we attempt to
            // create it automatically.
            if ($mysqlCode !== 1049) {
                throw new RuntimeException(
                    'Database connection failed: ' . $e->getMessage(),
                    0,
                    $e
                );
            }
        }

        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port),
                $username,
                $password,
                self::pdoOptions()
            );

            $pdo->exec(
                'CREATE DATABASE `' . $database . '` '
                . 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
        } catch (PDOException $e) {
            throw new RuntimeException(
                'The database does not exist and AfiuCMS could not create it. '
                . 'Create the database first or grant CREATE permission. '
                . 'MySQL said: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public static function assertMysqlDriver(): void
    {
        if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
            throw new RuntimeException(
                'PDO MySQL is not available. Install/enable the pdo_mysql PHP extension.'
            );
        }
    }

    private static function pdoOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ];
    }
}
