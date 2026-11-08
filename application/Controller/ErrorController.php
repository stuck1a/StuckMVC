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

namespace Stuck1A\StuckMVC\Application\Controller;

use Stuck1A\StuckMVC\Core\Controller\Attribute\Mapping;
use Stuck1A\StuckMVC\Core\Controller\PageController;
use Stuck1A\StuckMVC\Core\Http\Request;
use Stuck1A\StuckMVC\Core\Http\Response;
use Stuck1A\StuckMVC\Core\Routing\Route;

/**
 * Error pages. When requested directly, they respond with the matching HTTP status, so search engines don't index
 * them as regular pages.
 */
#[Mapping('error')]
final class ErrorController extends PageController
{
    public function handle(Request $request, Route $route): Response
    {
        $status = $route->path === 'error/404' ? 404 : 500;
        return $this->render($route->template, ['status' => $status], $status);
    }
}
