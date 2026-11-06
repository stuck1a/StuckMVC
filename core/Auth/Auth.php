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

namespace Stuck1A\StuckMVC\Core\Auth;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Stuck1A\StuckMVC\Core\Config\Config;
use Stuck1A\StuckMVC\Core\Http\Csrf;
use Stuck1A\StuckMVC\Core\Http\Session;

/**
 * Session based authentication.
 *
 * Security measures:
 *   - passwords are verified with password_verify() and transparently rehashed if the hashing options changed
 *   - unknown users take as long as wrong passwords (no user enumeration by timing)
 *   - failed attempts are throttled per username/IP and per IP
 *   - the session ID and CSRF token are renewed on login and logout (no session fixation)
 *   - sessions expire after a period of inactivity and are invalidated when the password changes
 */
final class Auth
{
    private const SESSION_KEY = '_auth';

    private ?Authenticatable $user = null;

    private bool $resolved = false;

    public function __construct(
        private readonly Session $session,
        private readonly Csrf $csrf,
        private readonly UserProvider $users,
        private readonly CacheInterface $cache,
        private readonly Config $config,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function attempt(string $login, string $password, string $ip): AuthResult
    {
        $login = trim($login);
        $keys = $this->throttleKeys($login, $ip);
        if ($this->isLockedOut($keys)) {
            $this->logger->warning('Login for {login} from {ip} blocked due to too many failed attempts.', ['login' => $login, 'ip' => $ip]);
            return AuthResult::LockedOut;
        }
        try {
            $user = $login === '' ? null : $this->users->findByLogin($login);
        } catch (\Throwable $e) {
            $this->logger->error('User lookup failed: {message}', ['message' => $e->getMessage(), 'exception' => $e]);
            return AuthResult::Unavailable;
        }
        // always verify a hash, so unknown users can't be detected by the response time
        $hash = $user?->getPasswordHash() ?? self::dummyHash();
        if (!password_verify($password, $hash) || $user === null) {
            $this->registerFailure($keys);
            $this->logger->notice('Failed login for {login} from {ip}.', ['login' => $login, 'ip' => $ip]);
            return AuthResult::InvalidCredentials;
        }
        $this->cache->deleteMultiple([$keys['login']]);
        $newHash = password_needs_rehash($hash, PASSWORD_DEFAULT) ? password_hash($password, PASSWORD_DEFAULT) : null;
        try {
            $this->users->onLogin($user, $newHash);
        } catch (\Throwable $e) {
            $this->logger->error('Could not update login data: {message}', ['message' => $e->getMessage(), 'exception' => $e]);
        }
        if ($newHash !== null) {
            $user = $this->users->findById($user->getAuthId()) ?? $user;
        }
        $this->login($user);
        $this->logger->info('User {user} logged in from {ip}.', ['user' => $user->getUsername(), 'ip' => $ip]);
        return AuthResult::Success;
    }

    /**
     * Logs a user in without password check (e.g. directly after registration).
     */
    public function login(Authenticatable $user): void
    {
        $this->session->regenerate();
        $this->csrf->rotate();
        $this->session->set(self::SESSION_KEY, [
            'id'          => $user->getAuthId(),
            'fingerprint' => self::fingerprint($user),
            'activity'    => time(),
        ]);
        $this->user = $user;
        $this->resolved = true;
    }

    public function logout(): void
    {
        $this->session->destroy();
        $this->user = null;
        $this->resolved = true;
    }

    /**
     * The currently logged in user or null. Doesn't start a session for visitors without session cookie.
     */
    public function user(): ?Authenticatable
    {
        if ($this->resolved) {
            return $this->user;
        }
        $this->resolved = true;
        $data = $this->session->get(self::SESSION_KEY);
        if (!is_array($data) || !isset($data['id'], $data['fingerprint'], $data['activity'])) {
            return null;
        }
        $idleTimeout = (int) $this->config->get('auth.idle_timeout', 7200);
        if ($idleTimeout > 0 && time() - (int) $data['activity'] > $idleTimeout) {
            $this->session->remove(self::SESSION_KEY);
            return null;
        }
        try {
            $user = $this->users->findById($data['id']);
        } catch (\Throwable $e) {
            $this->logger->error('User lookup failed: {message}', ['message' => $e->getMessage(), 'exception' => $e]);
            return null;
        }
        if ($user === null || !hash_equals((string) $data['fingerprint'], self::fingerprint($user))) {
            // user deleted or password changed in the meantime
            $this->session->remove(self::SESSION_KEY);
            return null;
        }
        $data['activity'] = time();
        $this->session->set(self::SESSION_KEY, $data);
        return $this->user = $user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function isAdmin(): bool
    {
        return $this->user()?->getRole() === Authenticatable::ROLE_ADMIN;
    }

    /**
     * @return array{login: string, ip: string}
     */
    private function throttleKeys(string $login, string $ip): array
    {
        return [
            'login' => 'auth.throttle.' . hash('sha256', mb_strtolower($login) . '|' . $ip),
            'ip'    => 'auth.throttle.ip.' . hash('sha256', $ip),
        ];
    }

    /**
     * @param array{login: string, ip: string} $keys
     */
    private function isLockedOut(array $keys): bool
    {
        $maxAttempts = (int) $this->config->get('auth.max_attempts', 5);
        return (int) $this->cache->get($keys['login'], 0) >= $maxAttempts
            || (int) $this->cache->get($keys['ip'], 0) >= $maxAttempts * 4;
    }

    /**
     * @param array{login: string, ip: string} $keys
     */
    private function registerFailure(array $keys): void
    {
        $lockout = (int) $this->config->get('auth.lockout_seconds', 900);
        foreach ($keys as $key) {
            $this->cache->set($key, (int) $this->cache->get($key, 0) + 1, $lockout);
        }
    }

    private static function fingerprint(Authenticatable $user): string
    {
        return hash('sha256', $user->getAuthId() . '|' . $user->getPasswordHash());
    }

    private static function dummyHash(): string
    {
        static $hash = null;
        return $hash ??= password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    }
}
