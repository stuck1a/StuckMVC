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
 * Hardened wrapper around the native PHP session.
 *
 * Sessions are started lazily, so anonymous visitors don't receive a session cookie unless a feature really needs
 * one (e.g. a login form with CSRF protection). The session cookie is HttpOnly, SameSite=Lax and marked as secure
 * on HTTPS connections. Strict mode prevents session fixation with attacker-chosen session IDs.
 *
 * For tests and CLI usage the session can run in memory mode (no native session, no cookies).
 */
final class Session
{
    private const FLASH_KEY = '_flash';

    /** @var array<string, mixed> Storage in memory mode */
    private array $memory = [];

    private bool $started = false;

    public function __construct(
        private readonly string $name = 'stuckmvc_sid',
        private readonly bool $secure = false,
        private readonly bool $native = true,
        private readonly bool $cookieSent = false,
    ) {
    }

    /**
     * Whether a session exists for the current client (either already started or resumable by the received cookie).
     */
    public function exists(): bool
    {
        return $this->started || $this->cookieSent;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }
        if ($this->native && session_status() !== PHP_SESSION_ACTIVE) {
            if (headers_sent($file, $line)) {
                throw new \RuntimeException("Cannot start session, output already started at {$file}:{$line}.");
            }
            session_name($this->name);
            session_start([
                'cookie_httponly'  => true,
                'cookie_secure'    => $this->secure,
                'cookie_samesite'  => 'Lax',
                'cookie_path'      => '/',
                'use_strict_mode'  => true,
                'use_only_cookies' => true,
                'use_trans_sid'    => false,
            ]);
        }
        $this->started = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->exists()) {
            return $default;
        }
        $this->start();
        return $this->storage()[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        if ($this->native) {
            $_SESSION[$key] = $value;
        } else {
            $this->memory[$key] = $value;
        }
    }

    public function remove(string $key): void
    {
        if (!$this->exists()) {
            return;
        }
        $this->start();
        if ($this->native) {
            unset($_SESSION[$key]);
        } else {
            unset($this->memory[$key]);
        }
    }

    /**
     * Issues a new session ID while keeping the data. Must be called whenever the privilege level changes (login).
     */
    public function regenerate(): void
    {
        $this->start();
        if ($this->native && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * Wipes all session data and invalidates the session cookie.
     */
    public function destroy(): void
    {
        if (!$this->exists()) {
            return;
        }
        $this->start();
        if ($this->native && session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            $params = session_get_cookie_params();
            if (!headers_sent()) {
                setcookie($this->name, '', [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?: 'Lax',
                ]);
            }
            session_destroy();
        }
        $this->memory = [];
        $this->started = false;
    }

    /**
     * Stores a message which is shown once on the next rendered page.
     *
     * @param string $type One of 'success', 'info', 'warning', 'error'
     */
    public function flash(string $type, string $message): void
    {
        $messages = $this->get(self::FLASH_KEY, []);
        $messages[] = ['type' => $type, 'message' => $message];
        $this->set(self::FLASH_KEY, $messages);
    }

    /**
     * Returns and removes all flash messages.
     *
     * @return list<array{type: string, message: string}>
     */
    public function consumeFlashes(): array
    {
        $messages = $this->get(self::FLASH_KEY, []);
        if ($messages !== []) {
            $this->remove(self::FLASH_KEY);
        }
        return is_array($messages) ? array_values($messages) : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function storage(): array
    {
        return $this->native ? ($_SESSION ?? []) : $this->memory;
    }
}
