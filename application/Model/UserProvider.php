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

namespace Stuck1A\StuckMVC\Application\Model;

use Stuck1A\StuckMVC\Core\Auth\Authenticatable;
use Stuck1A\StuckMVC\Core\Auth\UserProvider as UserProviderInterface;

/**
 * Loads user accounts for the authentication from the 'user' table.
 */
final class UserProvider implements UserProviderInterface
{
    public function findById(int|string $id): ?Authenticatable
    {
        return User::find($id);
    }

    public function findByLogin(string $login): ?Authenticatable
    {
        $row = User::query()->where('username', $login)->orWhere('email', $login)->first();
        return $row === null ? null : User::fromRow($row);
    }

    public function onLogin(Authenticatable $user, ?string $newPasswordHash): void
    {
        if (!$user instanceof User) {
            return;
        }
        if ($newPasswordHash !== null) {
            $user->passhash = $newPasswordHash;
        }
        $user->last_login_at = date('Y-m-d H:i:s');
        $user->save();
    }
}
