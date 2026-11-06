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

namespace Stuck1A\StuckMVC\Core\Controller;

use Stuck1A\StuckMVC\Core\Http\Request;
use Stuck1A\StuckMVC\Core\Http\Response;
use Stuck1A\StuckMVC\Core\Routing\Route;

/**
 * Parent of all backend controllers. Backend pages are only rendered for logged in administrators, everybody else
 * is redirected to the login page. Backend pages without their own controller are handled by this class directly.
 */
class BackendController extends Controller
{
    public const LOGIN_PATH = 'admin/login';

    public function handle(Request $request, Route $route): Response
    {
        return parent::handle($request, $route)
            ->setHeader('X-Robots-Tag', 'noindex, nofollow')
            ->setHeader('Cache-Control', 'no-store');
    }

    public function authorize(Request $request): bool
    {
        return !$this->requiresAdmin() || $this->services->auth->isAdmin();
    }

    /**
     * Visitors without permission are sent to the login page, which returns them afterwards.
     */
    public function denied(Request $request): Response
    {
        $query = $request->path === 'admin' ? [] : ['redirect' => $this->services->url->to($request->path)];
        return $this->redirect(self::LOGIN_PATH, $query);
    }

    /**
     * Whether the controller's pages are restricted to administrators. Override for public backend pages (login).
     */
    protected function requiresAdmin(): bool
    {
        return true;
    }
}
