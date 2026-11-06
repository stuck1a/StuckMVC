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
 * Outcome of a login attempt.
 */
enum AuthResult
{
    case Success;
    /** Unknown user or wrong password - intentionally not distinguished (no user enumeration). */
    case InvalidCredentials;
    /** Too many failed attempts. */
    case LockedOut;
    /** The user storage (database) is not reachable or not configured. */
    case Unavailable;

    public function message(): string
    {
        return match ($this) {
            self::Success            => 'Anmeldung erfolgreich!',
            self::InvalidCredentials => 'Benutzername oder Passwort falsch.',
            self::LockedOut          => 'Zu viele fehlgeschlagene Anmeldeversuche. Bitte später erneut versuchen.',
            self::Unavailable        => 'Die Anmeldung ist zurzeit nicht verfügbar.',
        };
    }
}
