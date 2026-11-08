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

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

/**
 * Finds classes within a PSR-4 directory without parsing any source code: the class name is derived from the file
 * path, the class itself is loaded by the regular autoloader.
 */
final class ClassDiscovery
{
    /**
     * @template T of object
     *
     * @param string               $dir       PSR-4 base directory of the namespace prefix
     * @param string               $namespace Namespace prefix belonging to $dir
     * @param class-string<T>|null $parent    If set, only non-abstract subclasses of this class are returned
     *
     * @return list<class-string<T>> Sorted list of class names
     */
    public static function find(string $dir, string $namespace, ?string $parent = null): array
    {
        if (!is_dir($dir)) {
            return [];
        }
        $dir = rtrim((string) realpath($dir), DIRECTORY_SEPARATOR);
        $namespace = trim($namespace, '\\');
        $classes = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            /** @var \SplFileInfo $file */
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($dir) + 1, -4);
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:[\\/\\\\][A-Za-z_][A-Za-z0-9_]*)*$/', $relative)) {
                // not a PSR-4 class file (e.g. located in a 'views' folder or containing dashes)
                continue;
            }
            $class = $namespace . '\\' . str_replace(['/', '\\'], '\\', $relative);
            if (!class_exists($class)) {
                continue;
            }
            if ($parent !== null) {
                $reflection = new ReflectionClass($class);
                if ($reflection->isAbstract() || !$reflection->isSubclassOf($parent)) {
                    continue;
                }
            }
            $classes[] = $class;
        }
        sort($classes);
        return $classes;
    }
}
