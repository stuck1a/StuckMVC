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

namespace Stuck1A\StuckMVC\Core\Controller\Attribute;

use Attribute;

/**
 * Marks a public controller method as action, which can be triggered by a POST request.
 *
 * This replaces the former 'cl'/'fnc' request fields, which allowed to call arbitrary methods of arbitrary classes.
 * Now only explicitly marked methods are callable, each by a unique name submitted as '_action' form field, and
 * every submission must carry a valid CSRF token.
 *
 * The method receives the current request and route and may return a Response. If it returns null, the client is
 * redirected back to the page the form was submitted on (Post/Redirect/Get).
 *
 * @example #[Action('auth.login')] public function login(Request $request, Route $route): ?Response
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Action
{
    public function __construct(public readonly string $name)
    {
        if (!preg_match('/^[a-z][a-z0-9_.-]*$/', $name)) {
            throw new \InvalidArgumentException("Invalid action name '{$name}'.");
        }
    }
}
