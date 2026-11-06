<?php
/*
 *  This file is part of StuckMVC <https://stuck1a.de/coding/stuckmvc>,
 *  Copyright (c) 2021-2026.
 *  The StuckMVC framework is free software: you can redistribute it and/or modify it
 *  under the terms of the GNU General Public License version 3 as published by
 *  the Free Software Foundation. See the GNU General Public License for more details.
 *
 *  FOR A SOMEWHAT FREER WORLD.
 */

declare(strict_types=1);

namespace Stuck1A\StuckMVC\Core\Database;

use PDO;
use PDOStatement;

/**
 * Lazy PDO database connection.
 *
 * The connection is established on first use, so requests which don't need the database never connect to it.
 * PDO runs in exception mode with native prepared statements; all values must be passed as bound parameters.
 */
final class Connection
{
    private ?PDO $pdo = null;

    /**
     * @param array<string, mixed> $settings 'driver', 'host', 'port', 'socket', 'name', 'user', 'pass', 'charset'
     *                                       (for sqlite: 'driver' => 'sqlite', 'name' => path or ':memory:')
     */
    public function __construct(private readonly array $settings)
    {
    }

    public static function fromPdo(PDO $pdo): self
    {
        $connection = new self(['driver' => (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)]);
        $connection->pdo = self::configure($pdo);
        return $connection;
    }

    public function pdo(): PDO
    {
        return $this->pdo ??= $this->connect();
    }

    public function driver(): string
    {
        return (string) ($this->settings['driver'] ?? 'mysql');
    }

    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }

    /**
     * Executes a statement with bound parameters.
     *
     * @param array<int|string, mixed> $params
     */
    public function run(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(is_int($key) ? $key + 1 : $key, $value, match (true) {
                is_int($value)  => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                $value === null => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            });
        }
        $statement->execute();
        return $statement;
    }

    /**
     * @param array<int|string, mixed> $params
     *
     * @return list<array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<int|string, mixed> $params
     *
     * @return array<string, mixed>|null
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Executes a writing statement and returns the number of affected rows.
     *
     * @param array<int|string, mixed> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    public function lastInsertId(): string
    {
        return (string) $this->pdo()->lastInsertId();
    }

    /**
     * Runs the callback within a transaction, which is rolled back if the callback throws.
     *
     * @template T
     *
     * @param callable(self): T $callback
     *
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback($this);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Quotes a table or column name (optionally qualified like 'user.id') after validating it.
     * Identifiers can't be bound as parameters, so they must never contain user input without this check.
     */
    public function quoteIdentifier(string $identifier): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', $identifier)) {
            throw new \InvalidArgumentException("Invalid SQL identifier '{$identifier}'.");
        }
        $quote = $this->driver() === 'mysql' ? '`' : '"';
        return implode('.', array_map(static fn (string $part): string => $quote . $part . $quote, explode('.', $identifier)));
    }

    private function connect(): PDO
    {
        $s = $this->settings;
        $driver = $this->driver();
        if ($driver === 'sqlite') {
            $pdo = new PDO('sqlite:' . ($s['name'] ?? ':memory:'));
            $pdo->exec('PRAGMA foreign_keys = ON');
            return self::configure($pdo);
        }
        $charset = (string) ($s['charset'] ?? 'utf8mb4');
        $host = !empty($s['socket'])
            ? 'unix_socket=' . $s['socket']
            : 'host=' . ($s['host'] ?? '127.0.0.1') . ';port=' . (int) ($s['port'] ?? 3306);
        $dsn = "{$driver}:{$host};dbname={$s['name']};charset={$charset}";
        return self::configure(new PDO($dsn, (string) ($s['user'] ?? ''), (string) ($s['pass'] ?? ''), [
            PDO::ATTR_TIMEOUT => 5,
        ]));
    }

    private static function configure(PDO $pdo): PDO
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }
        return $pdo;
    }
}
