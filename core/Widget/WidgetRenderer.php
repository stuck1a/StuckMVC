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

use Psr\Log\LoggerInterface;
use Stuck1A\StuckMVC\Core\Config\Config;
use Stuck1A\StuckMVC\Core\Container;
use Stuck1A\StuckMVC\Core\Http\RequestContext;
use Stuck1A\StuckMVC\Core\Routing\ApplicationMap;
use Stuck1A\StuckMVC\Core\View\View;

/**
 * Renders widgets on behalf of the {include_widget} template function.
 *
 * A failing widget never breaks the whole page: the error is logged and the widget is omitted. In debug mode a
 * short error notice is rendered at the widget's position instead.
 */
final class WidgetRenderer
{
    public function __construct(
        private readonly Container $container,
        private readonly ApplicationMap $map,
        private readonly View $view,
        private readonly RequestContext $context,
        private readonly LoggerInterface $logger,
        private readonly Config $config,
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public function render(string $name, array $params = []): string
    {
        $class = $this->map->widget($name);
        if ($class === null) {
            $this->logger->error('Unknown widget {name} requested.', ['name' => $name]);
            return $this->errorNotice("Unknown widget '{$name}'");
        }
        try {
            /** @var Widget $widget */
            $widget = $this->container->make($class);
            $data = $widget->viewData($params, $this->context->request(), $this->context->route());
            return $this->view->render($widget->templateFile(), $data + ['widget' => $widget, 'params' => $params]);
        } catch (\Throwable $e) {
            $this->logger->error('Widget {name} failed: {message}', ['name' => $name, 'message' => $e->getMessage(), 'exception' => $e]);
            return $this->errorNotice("Widget '{$name}' failed: " . $e::class . ': ' . $e->getMessage());
        }
    }

    private function errorNotice(string $message): string
    {
        if (!$this->config->isDebug()) {
            return '';
        }
        return '<div class="alert alert-danger" role="alert">' . htmlspecialchars($message) . '</div>';
    }
}
