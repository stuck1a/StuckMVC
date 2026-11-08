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
 * Base class for models based on the active record pattern: each object represents one record of the model's table.
 *
 * Column values are accessed as properties ($user->username). Changes are written to the database by save(),
 * which inserts new records and updates existing ones (only the changed columns). Only columns listed in
 * FILLABLE can be mass-assigned with fill(), which protects columns like 'role' against manipulated form data.
 *
 * @example $user = User::findBy(['username' => 'stuck1a']);
 * @example $user = new User(['username' => 'foo']); $user->save();
 */
abstract class Model
{
    /** Name of the table the model represents. Must be set by every model. */
    protected const TABLE = '';

    /** Name of the primary key column. */
    protected const PRIMARY_KEY = 'id';

    /** @var list<string> Columns which may be mass-assigned by fill() */
    protected const FILLABLE = [];

    private static ?Connection $connection = null;

    /** @var array<string, mixed> */
    private array $attributes = [];

    /** @var array<string, mixed> Values as last loaded from / written to the database */
    private array $original = [];

    private bool $exists = false;

    /**
     * @param array<string, mixed> $attributes Values for FILLABLE columns
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Sets the connection all models use. Called once by the kernel.
     */
    public static function setConnection(?Connection $connection): void
    {
        self::$connection = $connection;
    }

    public static function connection(): Connection
    {
        return self::$connection ?? throw new \LogicException('No database connection configured for models.');
    }

    public static function query(): QueryBuilder
    {
        return self::connection()->table(static::TABLE);
    }

    public static function find(int|string $id): ?static
    {
        return static::findBy([static::PRIMARY_KEY => $id]);
    }

    /**
     * Returns the first record matching all given column values.
     *
     * @param array<string, mixed> $criteria
     */
    public static function findBy(array $criteria): ?static
    {
        $query = static::query();
        foreach ($criteria as $column => $value) {
            $query->where($column, $value);
        }
        $row = $query->first();
        return $row === null ? null : static::fromRow($row);
    }

    /**
     * Returns all records matching the given column values.
     *
     * @param array<string, mixed> $criteria
     *
     * @return list<static>
     */
    public static function findAll(array $criteria = [], ?string $orderBy = null): array
    {
        $query = static::query();
        foreach ($criteria as $column => $value) {
            $query->where($column, $value);
        }
        if ($orderBy !== null) {
            $query->orderBy($orderBy);
        }
        return array_map(static::fromRow(...), $query->get());
    }

    /**
     * Creates a model object from a database row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): static
    {
        $model = new static();
        $model->attributes = $row;
        $model->original = $row;
        $model->exists = true;
        return $model;
    }

    /**
     * Mass-assigns values. Columns which are not FILLABLE are ignored silently.
     *
     * @param array<string, mixed> $attributes
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $column => $value) {
            if (in_array($column, static::FILLABLE, true)) {
                $this->attributes[$column] = $value;
            }
        }
        return $this;
    }

    public function __get(string $column): mixed
    {
        return $this->attributes[$column] ?? null;
    }

    public function __set(string $column, mixed $value): void
    {
        $this->attributes[$column] = $value;
    }

    public function __isset(string $column): bool
    {
        return isset($this->attributes[$column]);
    }

    public function getKey(): int|string|null
    {
        return $this->attributes[static::PRIMARY_KEY] ?? null;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function isDirty(): bool
    {
        return $this->dirty() !== [];
    }

    /**
     * Inserts the record, if it doesn't exist yet, or updates its changed columns.
     */
    public function save(): bool
    {
        if (!$this->exists) {
            $id = static::query()->insert($this->attributes);
            if (!isset($this->attributes[static::PRIMARY_KEY]) && $id !== '' && $id !== '0') {
                $this->attributes[static::PRIMARY_KEY] = ctype_digit($id) ? (int) $id : $id;
            }
            $this->exists = true;
            $this->original = $this->attributes;
            return true;
        }
        $changes = $this->dirty();
        if ($changes !== []) {
            static::query()->where(static::PRIMARY_KEY, $this->getKey())->update($changes);
            $this->original = $this->attributes;
        }
        return true;
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        $deleted = static::query()->where(static::PRIMARY_KEY, $this->getKey())->delete() > 0;
        $this->exists = false;
        return $deleted;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<string, mixed>
     */
    private function dirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $column => $value) {
            if (!array_key_exists($column, $this->original) || $this->original[$column] !== $value) {
                $dirty[$column] = $value;
            }
        }
        unset($dirty[static::PRIMARY_KEY]);
        return $dirty;
    }
}
