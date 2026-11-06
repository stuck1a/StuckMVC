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
 * Synchronizer token protection against cross-site request forgery.
 *
 * Every form which triggers an action must contain the token as hidden field (use <code>{csrf_field}</code> in
 * templates). The action dispatcher rejects POST requests without a valid token.
 */
final class Csrf
{
    public const FIELD = '_token';

    private const SESSION_KEY = '_csrf_token';

    public function __construct(private readonly Session $session)
    {
    }

    /**
     * Returns the token of the current session. Starts the session and creates the token, if necessary.
     */
    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);
        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::SESSION_KEY, $token);
        }
        return $token;
    }

    public function isValid(?string $token): bool
    {
        if ($token === null || $token === '' || !$this->session->exists()) {
            return false;
        }
        $expected = $this->session->get(self::SESSION_KEY);
        return is_string($expected) && hash_equals($expected, $token);
    }

    /**
     * Issues a new token, e.g. after login to prevent token fixation.
     */
    public function rotate(): void
    {
        $this->session->remove(self::SESSION_KEY);
    }
}
