<?php
/*
 *  This file is part of StuckMVC <https://stuck1a.de/coding/stuckmvc>,
 *  Copyright (c) 2021-2026.
 *  The StuckMVC framework is free software: you can redistribute it and/or modify it
 *  under the terms of the GNU General Public License version 3 as published by the
 *  Free Software Foundation. See the GNU General Public License for more details.
 *
 *  FOR A SOMEWHAT FREER WORLD.
 */

declare(strict_types=1);

namespace Stuck1A\StuckMVC\Core\Controller;

use Stuck1A\StuckMVC\Core\Http\Request;
use Stuck1A\StuckMVC\Core\Http\Response;
use Stuck1A\StuckMVC\Core\Routing\Route;

/**
 * Endpoint for machine-to-machine POST requests (webhooks of external services). POST requests to its routes are
 * passed to receive() instead of the ActionDispatcher, so there is no CSRF check: implementations must authenticate
 * every request themselves (e.g. with a shared secret header) and must not rely on sessions. GET requests answer 404.
 */
abstract class WebhookController extends Controller
{
    abstract public function receive(Request $request, Route $route): Response;

    public function handle(Request $request, Route $route): Response
    {
        return (new Response('', 404))->setHeader('Cache-Control', 'no-store');
    }
}
