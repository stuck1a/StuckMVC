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

use Psr\Log\LoggerInterface;
use Stuck1A\StuckMVC\Core\Auth\Auth;
use Stuck1A\StuckMVC\Core\Config\Config;
use Stuck1A\StuckMVC\Core\Http\Csrf;
use Stuck1A\StuckMVC\Core\Http\Session;
use Stuck1A\StuckMVC\Core\Routing\RouteCollection;
use Stuck1A\StuckMVC\Core\Routing\UrlGenerator;
use Stuck1A\StuckMVC\Core\View\View;

/**
 * Bundle of the services every controller needs, so controllers only have to declare one constructor parameter.
 */
final class ControllerServices
{
    public function __construct(
        public readonly View $view,
        public readonly UrlGenerator $url,
        public readonly Session $session,
        public readonly Csrf $csrf,
        public readonly Auth $auth,
        public readonly RouteCollection $routes,
        public readonly Config $config,
        public readonly LoggerInterface $logger,
    ) {
    }
}
