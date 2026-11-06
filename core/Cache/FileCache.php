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

namespace Stuck1A\StuckMVC\Core\Cache;

use DateInterval;
use DateTimeImmutable;
use Psr\SimpleCache\CacheInterface;

/**
 * PSR-16 compliant file cache.
 *
 * Each item is stored in its own file together with its expiration timestamp, so there is no shared key file which
 * could get corrupted by concurrent requests. Files are written atomically (temporary file + rename).
 * Only scalar values and arrays can be cached; objects are rejected on read to prevent object injection through
 * manipulated cache files.
 */
final class FileCache implements CacheInterface
{
    private const EXTENSION = '.cache';

    private readonly string $dir;

    public function __construct(string $dir)
    {
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException("Cache directory '{$dir}' cannot be created.");
        }
        $this->dir = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $item = $this->read($key);
        return $item === null ? $default : $item['value'];
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $expires = $this->expiration($ttl);
        if ($expires !== null && $expires <= time()) {
            return $this->delete($key);
        }
        $data = serialize(['expires' => $expires, 'value' => $value]);
        return $this->writeAtomic($this->file($key), $data);
    }

    public function delete(string $key): bool
    {
        $file = $this->file($key);
        return !is_file($file) || @unlink($file) || !is_file($file);
    }

    public function clear(): bool
    {
        $success = true;
        foreach (glob($this->dir . '*' . self::EXTENSION) ?: [] as $file) {
            $success = (@unlink($file) || !is_file($file)) && $success;
        }
        return $success;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            $success = $this->set((string) $key, $value, $ttl) && $success;
        }
        return $success;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            $success = $this->delete($key) && $success;
        }
        return $success;
    }

    public function has(string $key): bool
    {
        return $this->read($key) !== null;
    }

    /**
     * @return array{expires: ?int, value: mixed}|null
     */
    private function read(string $key): ?array
    {
        $file = $this->file($key);
        if (!is_file($file)) {
            return null;
        }
        $data = @file_get_contents($file);
        $item = $data === false ? false : @unserialize($data, ['allowed_classes' => false]);
        if (!is_array($item) || !array_key_exists('value', $item) || !array_key_exists('expires', $item)) {
            // corrupted item
            @unlink($file);
            return null;
        }
        if ($item['expires'] !== null && $item['expires'] <= time()) {
            @unlink($file);
            return null;
        }
        return $item;
    }

    private function file(string $key): string
    {
        if ($key === '' || strpbrk($key, '{}()/\\@:') !== false) {
            throw new InvalidArgumentException("Invalid cache key '{$key}'.");
        }
        return $this->dir . hash('sha256', $key) . self::EXTENSION;
    }

    private function expiration(null|int|DateInterval $ttl): ?int
    {
        if ($ttl instanceof DateInterval) {
            return (new DateTimeImmutable())->add($ttl)->getTimestamp();
        }
        return $ttl === null ? null : time() + $ttl;
    }

    private function writeAtomic(string $file, string $data): bool
    {
        $tmp = $file . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (@file_put_contents($tmp, $data, LOCK_EX) === false) {
            return false;
        }
        if (!@rename($tmp, $file)) {
            @unlink($tmp);
            return false;
        }
        return true;
    }
}
