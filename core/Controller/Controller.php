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

use Stuck1A\StuckMVC\Core\Http\HttpException;
use Stuck1A\StuckMVC\Core\Http\RedirectResponse;
use Stuck1A\StuckMVC\Core\Http\Request;
use Stuck1A\StuckMVC\Core\Http\Response;
use Stuck1A\StuckMVC\Core\Routing\Route;

/**
 * Base class of all controllers.
 *
 * A controller is responsible for one or more routes (see {@see Attribute\Mapping}). For GET requests the kernel
 * calls handle(), which renders the route's template by default. Controllers may additionally provide actions for
 * form submissions (see {@see Attribute\Action}).
 */
abstract class Controller
{
    public function __construct(protected readonly ControllerServices $services)
    {
    }

    public function handle(Request $request, Route $route): Response
    {
        return $this->render($route->template, $this->viewData($request, $route));
    }

    /**
     * Whether the current user may access this controller. Checked before pages are rendered and before actions
     * are executed. Unauthorized page requests are passed to denied(), unauthorized actions are rejected with 403.
     */
    public function authorize(Request $request): bool
    {
        return true;
    }

    /**
     * Response for unauthorized page requests.
     */
    public function denied(Request $request): Response
    {
        throw HttpException::forbidden();
    }

    /**
     * Template variables for the route's template. Override to provide page-specific data.
     *
     * @return array<string, mixed>
     */
    protected function viewData(Request $request, Route $route): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $variables
     */
    protected function render(string $template, array $variables = [], int $status = 200): Response
    {
        return new Response($this->services->view->render($template, $variables), $status);
    }

    /**
     * Redirects to a route path.
     *
     * @param array<string, scalar> $query
     */
    protected function redirect(string $path, array $query = []): RedirectResponse
    {
        return new RedirectResponse($this->services->url->to($path, $query));
    }

    /**
     * Stores a message which is shown on the next rendered page.
     */
    protected function flash(string $type, string $message): void
    {
        $this->services->session->flash($type, $message);
    }
}
