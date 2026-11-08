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

/**
 * Storage of user accounts used by {@see Auth}.
 */
interface UserProvider
{
    public function findById(int|string $id): ?Authenticatable;

    /**
     * Finds a user by username or email address (case-insensitive).
     */
    public function findByLogin(string $login): ?Authenticatable;

    /**
     * Called after a successful login. Receives a new hash, if the stored one uses outdated hashing options.
     */
    public function onLogin(Authenticatable $user, ?string $newPasswordHash): void;
}
