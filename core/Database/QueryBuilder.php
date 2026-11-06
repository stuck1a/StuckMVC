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

/**
 * Fluent builder for SELECT, INSERT, UPDATE and DELETE statements.
 *
 * All values are passed as bound parameters and all identifiers are validated and quoted, so queries built with
 * this class are safe against SQL injection even with user input.
 *
 * @example $db->table('user')->where('username', $name)->first();
 * @example $db->table('user')->select('id', 'username')->where('role', '=', 'admin')->orderBy('id', 'DESC')->get();
 * @example $db->table('user')->insert(['username' => 'foo', 'passhash' => $hash]);
 * @example $db->table('user')->where('id', 5)->update(['role' => 'admin']);
 */
final class QueryBuilder
{
    private const OPERATORS = ['=', '!=', '<>', '<', '<=', '>', '>=', 'LIKE', 'NOT LIKE'];

    /** @var list<string> */
    private array $columns = ['*'];

    /** @var list<array{string, string, list<mixed>}> [boolean, sql, params] */
    private array $wheres = [];

    /** @var list<string> */
    private array $orders = [];

    private ?int $limit = null;

    private ?int $offset = null;

    public function __construct(private readonly Connection $connection, private readonly string $table)
    {
    }

    public function select(string ...$columns): self
    {
        $this->columns = $columns === [] ? ['*'] : array_values($columns);
        return $this;
    }

    /**
     * Adds a condition. Call with two arguments for equality: where('id', 5).
     */
    public function where(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        return func_num_args() === 2
            ? $this->addWhere('AND', $column, '=', $operatorOrValue)
            : $this->addWhere('AND', $column, $operatorOrValue, $value);
    }

    public function orWhere(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        return func_num_args() === 2
            ? $this->addWhere('OR', $column, '=', $operatorOrValue)
            : $this->addWhere('OR', $column, $operatorOrValue, $value);
    }

    /**
     * @param list<mixed> $values
     */
    public function whereIn(string $column, array $values): self
    {
        if ($values === []) {
            $this->wheres[] = ['AND', '1 = 0', []];
            return $this;
        }
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = ['AND', $this->quote($column) . " IN ({$placeholders})", array_values($values)];
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->wheres[] = ['AND', $this->quote($column) . ' IS NULL', []];
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = $this->quote($column) . ' ' . $direction;
        return $this;
    }

    public function limit(int $limit, ?int $offset = null): self
    {
        $this->limit = max(0, $limit);
        $this->offset = $offset === null ? null : max(0, $offset);
        return $this;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function get(): array
    {
        [$sql, $params] = $this->toSelectSql();
        return $this->connection->fetchAll($sql, $params);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $previous = [$this->limit, $this->offset];
        $this->limit(1, $this->offset);
        try {
            return $this->get()[0] ?? null;
        } finally {
            [$this->limit, $this->offset] = $previous;
        }
    }

    public function count(): int
    {
        $columns = $this->columns;
        $this->columns = ['COUNT(*)'];
        try {
            [$sql, $params] = $this->toSelectSql();
        } finally {
            $this->columns = $columns;
        }
        return (int) ($this->connection->run($sql, $params)->fetchColumn());
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Inserts a record and returns its generated ID.
     *
     * @param array<string, mixed> $values Column => value
     */
    public function insert(array $values): string
    {
        if ($values === []) {
            throw new \InvalidArgumentException('Cannot insert an empty record.');
        }
        $columns = implode(', ', array_map($this->quote(...), array_keys($values)));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->connection->execute("INSERT INTO {$this->quote($this->table)} ({$columns}) VALUES ({$placeholders})", array_values($values));
        return $this->connection->lastInsertId();
    }

    /**
     * Updates all matching records and returns the number of affected rows.
     *
     * @param array<string, mixed> $values Column => value
     */
    public function update(array $values): int
    {
        if ($values === []) {
            return 0;
        }
        $this->guardUnconditional('update');
        $set = implode(', ', array_map(fn (string $column): string => $this->quote($column) . ' = ?', array_keys($values)));
        [$where, $params] = $this->compileWheres();
        return $this->connection->execute("UPDATE {$this->quote($this->table)} SET {$set}{$where}", [...array_values($values), ...$params]);
    }

    /**
     * Deletes all matching records and returns the number of affected rows.
     */
    public function delete(): int
    {
        $this->guardUnconditional('delete');
        [$where, $params] = $this->compileWheres();
        return $this->connection->execute("DELETE FROM {$this->quote($this->table)}{$where}", $params);
    }

    /**
     * Returns the SELECT statement and its parameters (useful for debugging and tests).
     *
     * @return array{string, list<mixed>}
     */
    public function toSelectSql(): array
    {
        $columns = implode(', ', array_map(
            fn (string $column): string => $column === '*' || $column === 'COUNT(*)' ? $column : $this->quote($column),
            $this->columns,
        ));
        [$where, $params] = $this->compileWheres();
        $sql = "SELECT {$columns} FROM {$this->quote($this->table)}{$where}";
        if ($this->orders !== []) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
            if ($this->offset !== null) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }
        return [$sql, $params];
    }

    private function addWhere(string $boolean, string $column, mixed $operator, mixed $value): self
    {
        $operator = strtoupper((string) $operator);
        if (!in_array($operator, self::OPERATORS, true)) {
            throw new \InvalidArgumentException("Invalid SQL operator '{$operator}'.");
        }
        if ($value === null) {
            $sql = $this->quote($column) . (in_array($operator, ['!=', '<>'], true) ? ' IS NOT NULL' : ' IS NULL');
            $this->wheres[] = [$boolean, $sql, []];
            return $this;
        }
        $this->wheres[] = [$boolean, $this->quote($column) . " {$operator} ?", [$value]];
        return $this;
    }

    /**
     * @return array{string, list<mixed>}
     */
    private function compileWheres(): array
    {
        if ($this->wheres === []) {
            return ['', []];
        }
        $sql = '';
        $params = [];
        foreach ($this->wheres as $i => [$boolean, $condition, $values]) {
            $sql .= ($i === 0 ? ' WHERE ' : " {$boolean} ") . $condition;
            array_push($params, ...$values);
        }
        return [$sql, $params];
    }

    private function guardUnconditional(string $operation): void
    {
        if ($this->wheres === []) {
            throw new \LogicException("Refusing to {$operation} all records of '{$this->table}' without condition.");
        }
    }

    private function quote(string $identifier): string
    {
        return $this->connection->quoteIdentifier($identifier);
    }
}
