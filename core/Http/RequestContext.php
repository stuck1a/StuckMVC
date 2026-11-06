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

use Stuck1A\StuckMVC\Core\Routing\Route;

/**
 * Holds the request which is currently processed and the route it was matched to.
 * Used by services which are called from templates (widgets, plugins) and therefore can't receive them as argument.
 */
final class RequestContext
{
    private ?Request $request = null;

    private ?Route $route = null;

    public function set(Request $request, ?Route $route): void
    {
        $this->request = $request;
        $this->route = $route;
    }

    public function request(): ?Request
    {
        return $this->request;
    }

    public function route(): ?Route
    {
        return $this->route;
    }
}
