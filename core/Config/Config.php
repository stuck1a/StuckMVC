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

namespace Stuck1A\StuckMVC\Core\Config;

/**
 * Read-only access to the application configuration.
 *
 * The configuration consists of the tracked defaults in <code>configs.php</code>, which are recursively overwritten
 * by the untracked, installation-specific <code>configs.local.php</code> (credentials, environment, secrets).
 * Values are accessed via dot notation, e.g. <code>$config->get('database.host')</code>.
 */
final class Config
{
    /**
     * Placeholder tags which are replaced within all string values of the 'paths' section.
     */
    private const THEME_TAG = '<themename>';

    /** @var array<string, mixed> Values with resolved placeholder tags */
    private readonly array $data;

    /**
     * @param array<string, mixed> $raw Configuration values (placeholder tags are resolved automatically)
     */
    public function __construct(private readonly array $raw)
    {
        $this->data = self::resolveTags($raw);
    }

    /**
     * Loads the default configuration and merges the local configuration file on top of it, if one exists.
     */
    public static function load(string $rootDir): self
    {
        $data = self::requireArray($rootDir . '/configs.php');
        $localFile = $rootDir . '/configs.local.php';
        if (is_file($localFile)) {
            $data = self::merge($data, self::requireArray($localFile));
        }
        return new self($data);
    }

    /**
     * Returns a config value by its dot-separated key or the given default, if the key does not exist.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->data;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    public function isDebug(): bool
    {
        return (bool) $this->get('application.debug', false);
    }

    /**
     * Returns a copy with some values replaced. Mainly useful for tests.
     *
     * @param array<string, mixed> $overrides
     */
    public function with(array $overrides): self
    {
        return new self(self::merge($this->raw, $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private static function requireArray(string $file): array
    {
        if (!is_file($file)) {
            throw new \RuntimeException("Configuration file '{$file}' is missing.");
        }
        $data = require $file;
        if (!is_array($data)) {
            throw new \RuntimeException("Configuration file '{$file}' must return an array.");
        }
        return $data;
    }

    /**
     * Recursive merge where associative arrays are merged and every other value (including lists) is replaced.
     *
     * @param array<array-key, mixed> $base
     * @param array<array-key, mixed> $override
     *
     * @return array<array-key, mixed>
     */
    private static function merge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key]) && !array_is_list($value)) {
                $base[$key] = self::merge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private static function resolveTags(array $data): array
    {
        $theme = (string) ($data['application']['theme'] ?? 'classic');
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $theme)) {
            throw new \RuntimeException('The configured theme name may only contain letters, digits, "_" and "-".');
        }
        array_walk_recursive($data, static function (mixed &$value) use ($theme): void {
            if (is_string($value)) {
                $value = str_replace(self::THEME_TAG, $theme, $value);
            }
        });
        return $data;
    }
}
