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

namespace Stuck1A\StuckMVC\Core\Routing;

use IteratorAggregate;
use Traversable;

/**
 * The routing table of the application.
 *
 * @implements IteratorAggregate<string, Route>
 */
final class RouteCollection implements IteratorAggregate, \Countable
{
    /** @var array<string, Route> */
    private array $routes = [];

    /**
     * @param iterable<Route> $routes
     */
    public function __construct(iterable $routes = [])
    {
        foreach ($routes as $route) {
            $this->add($route);
        }
    }

    public function add(Route $route): void
    {
        $this->routes[$route->path] = $route;
    }

    public function get(string $path): ?Route
    {
        if (isset($this->routes[$path])) {
            return $this->routes[$path];
        }
        foreach ($this->routes as $pattern => $route) {
            if (!str_contains($pattern, '{')) {
                continue;
            }
            $segments = preg_split('/(\{[A-Za-z_][A-Za-z0-9_]*\})/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);
            if (!is_array($segments)) {
                continue;
            }
            $regex = '';
            foreach ($segments as $segment) {
                $regex .= preg_match('/^\{[A-Za-z_][A-Za-z0-9_]*\}$/', $segment)
                    ? '[^/]+'
                    : preg_quote($segment, '#');
            }
            if (preg_match('#^' . $regex . '$#D', $path)) {
                return new Route($path, $route->template, $route->title, $route->section, $route->controller, $route->description, $route->keywords);
            }
        }
        return null;
    }

    public function has(string $path): bool
    {
        return $this->get($path) !== null;
    }

    /**
     * @return array<string, Route>
     */
    public function all(): array
    {
        return $this->routes;
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->routes);
    }

    public function count(): int
    {
        return count($this->routes);
    }

    /**
     * Returns the hierarchic trail from the start page down to the given route (e.g. for breadcrumbs).
     *
     * Every existing route on the way is included ('music/byartist' => '', 'music', 'music/byartist').
     * Consecutive entries which render the same template (like the aliases 'home' and '' of the start page or
     * 'music/index' and 'music') are merged into one entry, keeping the shorter URL.
     *
     * @return list<Route>
     */
    public function trail(Route $route): array
    {
        $paths = [''];
        $segments = $route->path === '' ? [] : explode('/', $route->path);
        $current = '';
        foreach ($segments as $segment) {
            $current = ltrim($current . '/' . $segment, '/');
            $paths[] = $current;
        }
        $trail = [];
        foreach ($paths as $path) {
            $step = $path === $route->path ? $route : $this->get($path);
            if ($step === null) {
                continue;
            }
            $last = end($trail);
            if ($last instanceof Route && $last->template === $step->template) {
                continue;
            }
            $trail[] = $step;
        }
        return $trail;
    }

    /**
     * @return list<array<string, ?string>>
     */
    public function toArray(): array
    {
        return array_values(array_map(static fn (Route $route): array => $route->toArray(), $this->routes));
    }

    /**
     * @param list<array<string, ?string>> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(array_map(Route::fromArray(...), $data));
    }
}
