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

namespace Stuck1A\StuckMVC\Application\Setup;

use Stuck1A\StuckMVC\Core\Database\Connection;

/**
 * Creates the database tables of the application (idempotent, existing tables are kept).
 */
final class SchemaInstaller
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function install(): void
    {
        $file = dirname(__DIR__) . '/database/schema.' . $this->connection->driver() . '.sql';
        if (!is_file($file)) {
            throw new \RuntimeException("No database schema available for driver '{$this->connection->driver()}'.");
        }
        $sql = (string) preg_replace('/^\s*--.*$/m', '', (string) file_get_contents($file));
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            $this->connection->pdo()->exec($statement);
        }
    }
}
