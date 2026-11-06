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

namespace Stuck1A\StuckMVC\Core\Routing;

use Stuck1A\StuckMVC\Core\Config\Config;

/**
 * Generates root-relative URLs to routes and asset directories.
 *
 * URLs are intentionally not absolute: the former implementation assembled them from $_SERVER['SERVER_NAME'], which
 * can be controlled by the client (host header injection) and broke local development setups.
 */
final class UrlGenerator
{
    private readonly string $basePath;

    public function __construct(private readonly Config $config, string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Detects the base path of the application from the path of the front controller script.
     */
    public static function detectBasePath(Config $config, array $server): string
    {
        $configured = $config->get('application.base_path');
        if (is_string($configured)) {
            return rtrim('/' . trim($configured, '/'), '/');
        }
        $dir = str_replace('\\', '/', dirname((string) ($server['SCRIPT_NAME'] ?? '/index.php')));
        return rtrim($dir === '.' ? '' : $dir, '/');
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    /**
     * URL of a route path, e.g. to('music/byartist') => '/music/byartist'.
     *
     * @param array<string, scalar> $query
     */
    public function to(string $path = '', array $query = []): string
    {
        $segments = array_map('rawurlencode', explode('/', trim($path, '/')));
        $url = $this->basePath . '/' . implode('/', $segments);
        return $query === [] ? $url : $url . '?' . http_build_query($query);
    }

    /**
     * URL of a directory registered in the 'paths' config section, with trailing slash.
     */
    public function dir(string $key): string
    {
        $dir = trim((string) $this->config->get('paths.' . $key, ''), '/');
        return $this->basePath . '/' . ($dir === '' ? '' : $dir . '/');
    }

    /**
     * Validates a redirect target from user input: only local paths are allowed (no open redirects).
     */
    public function isLocal(string $url): bool
    {
        return $url !== ''
            && $url[0] === '/'
            && !str_starts_with($url, '//')
            && !str_contains($url, '\\')
            && !preg_match('/[\x00-\x1F]/', $url);
    }
}
