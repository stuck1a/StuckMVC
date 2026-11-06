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
 * Aborts the current request with a specific HTTP status. The kernel renders the matching error page.
 */
class HttpException extends \RuntimeException
{
    public function __construct(private readonly int $status, string $message = '', ?\Throwable $previous = null)
    {
        parent::__construct($message !== '' ? $message : "HTTP {$status}", $status, $previous);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public static function notFound(string $message = 'Page not found'): self
    {
        return new self(404, $message);
    }

    public static function badRequest(string $message = 'Bad request'): self
    {
        return new self(400, $message);
    }

    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self(403, $message);
    }
}
