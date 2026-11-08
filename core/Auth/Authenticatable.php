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
 * A user account which can log in.
 */
interface Authenticatable
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';

    public function getAuthId(): int|string;

    public function getUsername(): string;

    public function getPasswordHash(): string;

    public function getRole(): string;
}
