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

use FilesystemIterator;
use Psr\SimpleCache\CacheInterface;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use SplFileInfo;
use Stuck1A\StuckMVC\Core\Config\Config;
use Stuck1A\StuckMVC\Core\Controller\Attribute\Action;
use Stuck1A\StuckMVC\Core\Controller\Attribute\Mapping;
use Stuck1A\StuckMVC\Core\Controller\Controller;
use Stuck1A\StuckMVC\Core\Support\ClassDiscovery;
use Stuck1A\StuckMVC\Core\Support\Path;
use Stuck1A\StuckMVC\Core\Widget\Widget;

/**
 * Generates the {@see ApplicationMap} by scanning the application:
 *
 *   - Content pages: every template within the pages directory becomes a route which reflects its location, e.g.
 *     'pages/music/byartist.tpl' => 'music/byartist'. Directories get a route to their default template
 *     ('pages/music/index.tpl' => 'music'). Directories named 'inc' and templates named '*.inc.tpl' are skipped.
 *   - Backend pages: same for the admin directory, prefixed with 'admin'.
 *   - Static routes from the configuration, which overwrite generated routes.
 *   - Controllers with their {@see Mapping} and {@see Action} attributes.
 *   - Widgets (subclasses of {@see Widget}).
 *
 * The result is cached. The cache is invalidated automatically as soon as any file within the scanned directories
 * is added, removed or changed, so there's no need to flush the cache after deployments.
 */
final class MapBuilder
{
    private const CACHE_KEY = 'stuckmvc.application_map';

    public function __construct(
        private readonly Config $config,
        private readonly string $rootDir,
        private readonly CacheInterface $cache,
    ) {
    }

    public function load(): ApplicationMap
    {
        $fingerprint = $this->fingerprint();
        $cached = $this->cache->get(self::CACHE_KEY);
        if (is_array($cached) && ($cached['fingerprint'] ?? null) === $fingerprint && is_array($cached['map'] ?? null)) {
            return ApplicationMap::fromArray($cached['map']);
        }
        $map = $this->build();
        $this->cache->set(self::CACHE_KEY, ['fingerprint' => $fingerprint, 'map' => $map->toArray()]);
        return $map;
    }

    public function build(): ApplicationMap
    {
        [$mappings, $actions] = $this->scanControllers();
        $routes = new RouteCollection();
        $templates = $this->scanTemplates($this->path('paths.pages'), '') + $this->scanTemplates($this->path('paths.admin'), 'admin');
        foreach ((array) $this->config->get('routing.static_routes', []) as $path => $template) {
            $templates[trim((string) $path, '/')] = $this->absolute((string) $template);
        }
        $pages = (array) $this->config->get('routing.pages', []);
        foreach ($templates as $path => $template) {
            if (!is_file($template) || !$this->featureEnabled($path)) {
                continue;
            }
            $section = ($path === 'admin' || str_starts_with($path, 'admin/')) ? Route::SECTION_ADMIN : Route::SECTION_PAGE;
            $seo = (array) ($pages[$path] ?? []);
            $routes->add(new Route(
                $path,
                $template,
                (string) ($seo['title'] ?? $this->extractTitle($template, $path)),
                $section,
                $this->resolveController($path, $mappings),
                (string) ($seo['description'] ?? ''),
                (string) ($seo['keywords'] ?? ''),
            ));
        }
        return new ApplicationMap($routes, $actions, $this->scanWidgets());
    }

    /**
     * @return array<string, string> Route path => absolute template path
     */
    private function scanTemplates(string $baseDir, string $prefix): array
    {
        if (!is_dir($baseDir)) {
            return [];
        }
        $baseDir = rtrim((string) realpath($baseDir), DIRECTORY_SEPARATOR);
        $defaultName = (string) $this->config->get('templates.default_name', 'index.tpl');
        $filter = new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS),
            static fn (SplFileInfo $file): bool => $file->isDir()
                ? $file->getFilename() !== 'inc'
                : str_ends_with($file->getFilename(), '.tpl') && !str_ends_with($file->getFilename(), '.inc.tpl'),
        );
        $templates = [];
        foreach (new RecursiveIteratorIterator($filter) as $file) {
            /** @var SplFileInfo $file */
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($baseDir) + 1));
            $path = trim($prefix . '/' . substr($relative, 0, -4), '/');
            $templates[$path] = $file->getPathname();
            if ($file->getFilename() === $defaultName) {
                $dirPath = trim($prefix . '/' . dirname($relative), '/.');
                $templates[$dirPath] ??= $file->getPathname();
            }
        }
        ksort($templates);
        return $templates;
    }

    /**
     * @return array{array<string, class-string>, array<string, array{class-string, string}>}
     */
    private function scanControllers(): array
    {
        $mappings = [];
        $actions = [];
        $namespace = $this->config->get('application.namespace') . '\\Controller';
        foreach (ClassDiscovery::find($this->path('paths.controllers'), $namespace, Controller::class) as $class) {
            $reflection = new ReflectionClass($class);
            foreach ($reflection->getAttributes(Mapping::class) as $attribute) {
                foreach ($attribute->newInstance()->paths as $path) {
                    $mappings[$path] = $class;
                }
            }
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(Action::class) as $attribute) {
                    $name = $attribute->newInstance()->name;
                    if (isset($actions[$name])) {
                        throw new \LogicException("Action '{$name}' is defined twice ({$actions[$name][0]} and {$class}).");
                    }
                    $actions[$name] = [$class, $method->getName()];
                }
            }
        }
        return [$mappings, $actions];
    }

    /**
     * @return array<string, class-string>
     */
    private function scanWidgets(): array
    {
        $widgets = [];
        $namespace = $this->config->get('application.namespace') . '\\Widget';
        foreach (ClassDiscovery::find($this->path('paths.widgets'), $namespace, Widget::class) as $class) {
            $short = (new ReflectionClass($class))->getShortName();
            $widgets[strtolower((string) preg_replace('/Widget$/', '', $short))] = $class;
        }
        return $widgets;
    }

    /**
     * Finds the most specific controller mapping for a route path.
     *
     * @param array<string, class-string> $mappings
     */
    private function resolveController(string $path, array $mappings): ?string
    {
        if (isset($mappings[$path])) {
            return $mappings[$path];
        }
        $best = null;
        foreach ($mappings as $mapping => $class) {
            $mapping = (string) $mapping;
            if ($mapping !== '' && str_starts_with($path, $mapping . '/') && ($best === null || strlen($mapping) > strlen($best))) {
                $best = $mapping;
            }
        }
        return $best === null ? null : $mappings[$best];
    }

    /**
     * Reads the page title from the template's {block 'title'} or derives it from the route path.
     */
    private function extractTitle(string $template, string $path): string
    {
        $source = (string) @file_get_contents($template);
        if (preg_match('/\{block\s+[\'"]?title[\'"]?\s*}(.*?)\{\/block}/s', $source, $match)) {
            $title = trim(strip_tags((string) preg_replace('/\{[^}]*}/', '', $match[1])));
            if ($title !== '') {
                return html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }
        if ($path === '') {
            return 'Home';
        }
        return ucfirst(str_replace(['-', '_'], ' ', basename($path)));
    }

    private function featureEnabled(string $path): bool
    {
        foreach ((array) $this->config->get('routing.feature_routes', []) as $prefix => $feature) {
            $prefix = trim((string) $prefix, '/');
            if (($path === $prefix || str_starts_with($path, $prefix . '/'))
                && !$this->config->get('features.' . $feature, false)) {
                return false;
            }
        }
        return true;
    }

    /** Checksum over routing settings, feature flags and source file metadata. */
    private function fingerprint(): string
    {
        $parts = [serialize($this->config->get('routing')), serialize($this->config->get('features')), (string) $this->config->get('application.namespace')];
        foreach (['paths.pages', 'paths.admin', 'paths.controllers', 'paths.widgets'] as $key) {
            $dir = $this->path($key);
            if (!is_dir($dir)) {
                continue;
            }
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                /** @var SplFileInfo $file */
                $parts[] = $file->getPathname() . ':' . $file->getMTime() . ':' . $file->getSize();
            }
        }
        sort($parts);
        return hash('sha256', implode("\n", $parts));
    }

    private function path(string $key): string
    {
        return $this->absolute((string) $this->config->get($key, ''));
    }

    private function absolute(string $path): string
    {
        return Path::resolve($this->rootDir, $path);
    }
}
