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
 * Redirects the client to another URL. Uses "303 See Other" by default, which is the correct status after
 * processing a form submission (Post/Redirect/Get pattern).
 */
final class RedirectResponse extends Response
{
    public function __construct(private readonly string $url, int $status = 303)
    {
        parent::__construct('', $status, ['Location' => $url]);
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
