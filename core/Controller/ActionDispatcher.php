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

use Stuck1A\StuckMVC\Core\Container;
use Stuck1A\StuckMVC\Core\Http\Csrf;
use Stuck1A\StuckMVC\Core\Http\HttpException;
use Stuck1A\StuckMVC\Core\Http\RedirectResponse;
use Stuck1A\StuckMVC\Core\Http\Request;
use Stuck1A\StuckMVC\Core\Http\Response;
use Stuck1A\StuckMVC\Core\Http\Session;
use Stuck1A\StuckMVC\Core\Routing\ApplicationMap;
use Stuck1A\StuckMVC\Core\Routing\Route;
use Stuck1A\StuckMVC\Core\Routing\UrlGenerator;

/**
 * Processes form submissions (POST requests).
 *
 * The form field '_action' names the action, '_token' must contain the CSRF token. Only controller methods which
 * are explicitly marked with {@see Attribute\Action} can be triggered. After processing, the client is redirected
 * (Post/Redirect/Get), so reloading the page doesn't submit the form again.
 */
final class ActionDispatcher
{
    public const FIELD = '_action';

    public function __construct(
        private readonly ApplicationMap $map,
        private readonly Container $container,
        private readonly Csrf $csrf,
        private readonly Session $session,
        private readonly UrlGenerator $url,
    ) {
    }

    public function dispatch(Request $request, Route $route): Response
    {
        $name = $request->input(self::FIELD);
        $action = $name === null ? null : $this->map->action($name);
        if ($action === null) {
            throw HttpException::badRequest('Unknown or missing action.');
        }
        if (!$this->csrf->isValid($request->input(Csrf::FIELD))) {
            $this->session->flash('error', 'Das Formular ist abgelaufen. Bitte erneut absenden.');
            return $this->back($request);
        }
        [$class, $method] = $action;
        /** @var Controller $controller */
        $controller = $this->container->make($class);
        if (!$controller->authorize($request)) {
            throw HttpException::forbidden('Action not allowed.');
        }
        $response = $controller->{$method}($request, $route);
        return $response instanceof Response ? $response : $this->back($request);
    }

    private function back(Request $request): RedirectResponse
    {
        return new RedirectResponse($this->url->to($request->path, array_filter($request->query, 'is_scalar')));
    }
}
