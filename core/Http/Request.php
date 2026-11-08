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

namespace Stuck1A\StuckMVC\Core\Http;

/**
 * Immutable representation of the current HTTP request.
 *
 * The route path is already normalized: it's relative to the application base path, url-decoded, without leading or
 * trailing slashes and without duplicate slashes. The start page therefore has the path '' (empty string).
 */
final class Request
{
    /**
     * @param array<string, mixed>  $query   Query string parameters ($_GET)
     * @param array<string, mixed>  $post    Parsed request body ($_POST)
     * @param array<string, mixed>  $server  Server parameters ($_SERVER)
     * @param array<string, string> $cookies Cookies ($_COOKIE)
     * @param array<string, mixed>  $files   Uploaded files ($_FILES)
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $post = [],
        public readonly array $server = [],
        public readonly array $cookies = [],
        public readonly array $files = [],
        /** Raw request body (only read for POST requests). */
        public readonly string $body = '',
    ) {
    }

    public static function fromGlobals(string $basePath): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            self::normalizePath($uri, $basePath),
            $_GET,
            $_POST,
            $_SERVER,
            $_COOKIE,
            $_FILES,
            ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' ? (string) file_get_contents('php://input') : '',
        );
    }

    /**
     * Creates a request for tests or internal calls.
     *
     * @param array<string, mixed> $post
     * @param array<string, mixed> $server
     */
    public static function create(string $method, string $uri, array $post = [], array $server = [], string $basePath = '', array $files = [], string $body = ''): self
    {
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
        return new self(strtoupper($method), self::normalizePath($uri, $basePath), $query, $post, $server + [
            'REQUEST_METHOD' => strtoupper($method),
            'REQUEST_URI'    => $uri,
            'REMOTE_ADDR'    => '127.0.0.1',
        ], [], $files, $body);
    }

    /**
     * Extracts the route path from a request URI.
     */
    public static function normalizePath(string $uri, string $basePath = ''): string
    {
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $uri)) {
            // absolute URI (proxy requests)
            $uri = (string) parse_url($uri, PHP_URL_PATH);
        }
        // strip query string and fragment manually: parse_url() would mistake '//foo' for a host name
        $path = rawurldecode((string) preg_split('/[?#]/', $uri, 2)[0]);
        $path = (string) preg_replace('#/{2,}#', '/', '/' . $path);
        $basePath = rtrim($basePath, '/');
        if ($basePath !== '' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
            $path = substr($path, strlen($basePath));
        }
        return trim($path, '/');
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /**
     * Returns a scalar query parameter as string or the default, if missing or not scalar.
     */
    public function query(string $name, ?string $default = null): ?string
    {
        $value = $this->query[$name] ?? null;
        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * Returns a scalar POST field as string or the default, if missing or not scalar.
     */
    public function input(string $name, ?string $default = null): ?string
    {
        $value = $this->post[$name] ?? null;
        return is_scalar($value) ? (string) $value : $default;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $this->server[$key] ?? null;
        return is_string($value) ? $value : null;
    }

    public function isSecure(): bool
    {
        $https = strtolower((string) ($this->server['HTTPS'] ?? ''));
        return ($https !== '' && $https !== 'off') || (string) ($this->server['SERVER_PORT'] ?? '') === '443';
    }

    public function clientIp(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function hasCookie(string $name): bool
    {
        return isset($this->cookies[$name]);
    }
}
