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

namespace Stuck1A\StuckMVC\Core\Support;

/**
 * Path helpers.
 */
final class Path
{
    /**
     * Resolves a configured path: absolute paths are kept, relative paths are relative to the root directory.
     */
    public static function resolve(string $rootDir, string $path): string
    {
        if ($path === '' || self::isAbsolute($path)) {
            return $path;
        }
        return rtrim($rootDir, '/\\') . DIRECTORY_SEPARATOR . $path;
    }

    public static function isAbsolute(string $path): bool
    {
        return $path !== '' && ($path[0] === '/' || $path[0] === '\\' || preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1);
    }
}
