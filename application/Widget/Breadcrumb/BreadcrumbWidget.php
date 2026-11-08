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

namespace Stuck1A\StuckMVC\Application\Widget\Breadcrumb;

use Stuck1A\StuckMVC\Core\Http\Request;
use Stuck1A\StuckMVC\Core\Routing\Route;
use Stuck1A\StuckMVC\Core\Routing\RouteCollection;
use Stuck1A\StuckMVC\Core\Widget\Widget;

/**
 * Breadcrumb navigation from the start page down to the current page.
 *
 * Usage: {include_widget name=breadcrumb}
 * Optional parameters:
 *   home=false    Omit the start page as first entry
 *   always=true   Also render the breadcrumb on the start page (which only has a single entry)
 */
final class BreadcrumbWidget extends Widget
{
    protected string $template = 'views/breadcrumb.tpl';

    public function __construct(private readonly RouteCollection $routes)
    {
    }

    public function viewData(array $params, ?Request $request, ?Route $route): array
    {
        // error pages are no part of the page hierarchy
        $hidden = $route === null || (!$route->isPublicContent() && !$route->isAdmin());
        return ['items' => $hidden ? [] : $this->tree($route, $params)];
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return list<Route>
     */
    public function tree(Route $route, array $params = []): array
    {
        $trail = $this->routes->trail($route);
        $always = filter_var($params['always'] ?? false, FILTER_VALIDATE_BOOL);
        if (count($trail) < 2 && !$always) {
            return [];
        }
        if (!filter_var($params['home'] ?? true, FILTER_VALIDATE_BOOL) && count($trail) > 1) {
            array_shift($trail);
        }
        return $trail;
    }
}
