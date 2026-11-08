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

namespace Stuck1A\StuckMVC\Core\Widget;

use ReflectionClass;
use Stuck1A\StuckMVC\Core\Http\Request;
use Stuck1A\StuckMVC\Core\Routing\Route;

/**
 * Base class for widgets: self-contained components with their own controller logic and template, which can be
 * included in any template with <code>{include_widget name=breadcrumb}</code>.
 *
 * Widgets are rendered at runtime whenever the including template is rendered, so they always reflect the current
 * request (the former compiler plugin rendered them once while compiling, which froze their output).
 *
 * The widget name is derived from the class name ('BreadcrumbWidget' => 'breadcrumb'). Dependencies can be declared
 * as constructor parameters and are injected automatically.
 */
abstract class Widget
{
    /**
     * Path of the widget's template, relative to the directory of the widget class.
     */
    protected string $template = 'views/widget.tpl';

    /**
     * Prepares the template variables. Additional attributes of the include_widget call are passed as $params.
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public function viewData(array $params, ?Request $request, ?Route $route): array
    {
        return [];
    }

    /**
     * Absolute path of the widget template.
     */
    public function templateFile(): string
    {
        $dir = dirname((string) (new ReflectionClass($this))->getFileName());
        return $dir . DIRECTORY_SEPARATOR . $this->template;
    }
}
