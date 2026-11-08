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
use Stuck1A\StuckMVC\Core\Database\Model;

/**
 * User account (table 'user').
 *
 * @property int|string  $id
 * @property string      $username
 * @property string|null $email
 * @property string      $passhash
 * @property string      $role
 * @property string      $created_at
 * @property string|null $last_login_at
 */
final class User extends Model implements Authenticatable
{
    protected const TABLE = 'user';

    protected const FILLABLE = ['username', 'email'];

    public const USERNAME_PATTERN = '/^[A-Za-z0-9_.-]{3,32}$/';

    public const MIN_PASSWORD_LENGTH = 8;

    /** Shown wherever a new password is entered. */
    public const PASSWORD_RULE = 'mindestens ' . self::MIN_PASSWORD_LENGTH . ' Zeichen, davon mindestens eine Ziffer oder ein Sonderzeichen';

    /** Between regular users and administrators: may use reserved services such as Share. */
    public const ROLE_PRIVILEGED = 'privileged';

    /** All roles with their labels, from the fewest to the most rights. */
    public const ROLES = [self::ROLE_USER => 'Benutzer', self::ROLE_PRIVILEGED => 'Privilegiert', self::ROLE_ADMIN => 'Administrator'];

    /**
     * Creates a new account (not saved yet).
     */
    public static function register(string $username, string $password, ?string $email = null, string $role = self::ROLE_USER): self
    {
        $user = new self(['username' => $username, 'email' => $email === '' ? null : $email]);
        $user->role = $role;
        $user->setPassword($password);
        $user->created_at = date('Y-m-d H:i:s');
        return $user;
    }

    /**
     * Validates registration data and returns a list of error messages (empty if valid).
     *
     * @return list<string>
     */
    public static function validate(string $username, string $password, string $passwordRepeat, ?string $email = null): array
    {
        $errors = [];
        if (!preg_match(self::USERNAME_PATTERN, $username)) {
            $errors[] = 'Der Benutzername muss 3 bis 32 Zeichen lang sein und darf nur Buchstaben, Ziffern, "_", "-" und "." enthalten.';
        }
        if (!self::isStrongPassword($password)) {
            $errors[] = 'Das Passwort braucht ' . self::PASSWORD_RULE . '.';
        }
        if ($password !== $passwordRepeat) {
            $errors[] = 'Die Passwörter stimmen nicht überein.';
        }
        if ($email !== null && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Die E-Mail-Adresse ist ungültig.';
        }
        return $errors;
    }

    /** At least MIN_PASSWORD_LENGTH characters, one of them no letter (digit or special character). */
    public static function isStrongPassword(string $password): bool
    {
        return mb_strlen($password) >= self::MIN_PASSWORD_LENGTH && preg_match('/[^\p{L}]/u', $password) === 1;
    }

    public static function usernameExists(string $username): bool
    {
        return self::query()->where('username', $username)->exists();
    }

    public static function emailExists(string $email): bool
    {
        return self::query()->where('email', $email)->exists();
    }

    /**
     * Free username derived from an e-mail address (accounts created from requests log in with their e-mail).
     */
    public static function usernameFromEmail(string $email): string
    {
        $base = (string) preg_replace('/[^A-Za-z0-9_.-]+/', '-', strstr($email, '@', true) ?: $email);
        // 25 characters at most (older live table); room for a numeric suffix
        $base = substr(trim($base, '.-'), 0, 20);
        $base = strlen($base) < 3 ? 'user-' . $base : $base;
        $username = $base;
        for ($i = 2; self::usernameExists($username); $i++) {
            $username = $base . '-' . $i;
        }
        return $username;
    }

    /**
     * Validates a new password and returns a list of error messages (empty if valid).
     *
     * @return list<string>
     */
    public static function passwordErrors(string $password, string $passwordRepeat): array
    {
        $errors = [];
        if (!self::isStrongPassword($password)) {
            $errors[] = 'Das Passwort braucht ' . self::PASSWORD_RULE . '.';
        } elseif (strlen($password) > 200) {
            $errors[] = 'Das Passwort darf höchstens 200 Zeichen lang sein.';
        }
        if ($password !== $passwordRepeat) {
            $errors[] = 'Die Passwörter stimmen nicht überein.';
        }
        return $errors;
    }

    /**
     * Removes the account and everything stored about it in this database (requests, reset links).
     */
    public function deleteWithData(): void
    {
        $pdo = self::connection()->pdo();
        $pdo->beginTransaction();
        try {
            PasswordReset::forget($this->getAuthId());
            MediaFavorite::forget($this->getAuthId());
            Playlist::forget($this->getAuthId());
            if ($this->email !== null && $this->email !== '') {
                RegistrationRequest::query()->where('email', (string) $this->email)->delete();
            }
            $this->delete();
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function adminCount(): int
    {
        return self::query()->where('role', self::ROLE_ADMIN)->count();
    }

    public static function adminExists(): bool
    {
        return self::query()->where('role', self::ROLE_ADMIN)->exists();
    }

    public function setPassword(string $password): void
    {
        $this->passhash = password_hash($password, PASSWORD_DEFAULT);
    }

    public function getAuthId(): int|string
    {
        return $this->getKey() ?? throw new \LogicException('User is not saved yet.');
    }

    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function getPasswordHash(): string
    {
        return (string) $this->passhash;
    }

    public function getRole(): string
    {
        return (string) ($this->role ?: self::ROLE_USER);
    }

    public function isAdmin(): bool
    {
        return $this->getRole() === self::ROLE_ADMIN;
    }

    /** Whether the account has at least the given role (higher roles include the rights of lower ones). */
    public function hasRole(string $role): bool
    {
        $ranks = array_flip(array_keys(self::ROLES));
        return isset($ranks[$role], $ranks[$this->getRole()]) && $ranks[$this->getRole()] >= $ranks[$role];
    }
}
