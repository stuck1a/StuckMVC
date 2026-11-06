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

namespace Stuck1A\StuckMVC\Core\View;

use Smarty\Smarty;
use Stuck1A\StuckMVC\Core\Config\Config;
use Stuck1A\StuckMVC\Core\Support\Path;

/**
 * Facade for the Smarty template engine.
 *
 * Variables are HTML-escaped automatically (<code>{$var}</code>). Use <code>{$var nofilter}</code> only for trusted
 * HTML. Template directories are the views directory and the directory of the active theme.
 */
final class View
{
    private readonly Smarty $smarty;

    public function __construct(Config $config, string $rootDir, TemplatePlugins $plugins)
    {
        $path = static fn (string $key): string => Path::resolve($rootDir, (string) $config->get($key));
        $smarty = new Smarty();
        $smarty->setTemplateDir($path('paths.views'));
        $smarty->addTemplateDir($path('paths.theme'));
        $smarty->setConfigDir($path('paths.views'));
        $smarty->setCompileDir(self::ensureDir($path('paths.compiled')));
        $smarty->setCacheDir(self::ensureDir($path('paths.rendered')));
        $smarty->setEscapeHtml(true);
        $smarty->setForceCompile((bool) $config->get('templates.force_compile', false));
        $smarty->setCompileCheck(Smarty::COMPILECHECK_ON);
        $smarty->setCaching(Smarty::CACHING_OFF);
        $smarty->muteUndefinedOrNullWarnings();
        $plugins->register($smarty);
        // global template config (title, meta data, ...) - general section and theme-specific section
        $smarty->configLoad('facts.conf', 'general');
        $smarty->configLoad('facts.conf', $config->get('application.theme') . 'Theme');
        $this->smarty = $smarty;
    }

    public function smarty(): Smarty
    {
        return $this->smarty;
    }

    /**
     * Assigns a variable which is available in all templates rendered afterwards.
     */
    public function assign(string $name, mixed $value): void
    {
        $this->smarty->assign($name, $value);
    }

    /**
     * Renders a template (name relative to the template directories or absolute path).
     *
     * @param array<string, mixed> $variables Variables which are only available within this template.
     */
    public function render(string $template, array $variables = []): string
    {
        $tpl = $this->smarty->createTemplate($template, null, null, $this->smarty);
        foreach ($variables as $name => $value) {
            $tpl->assign($name, $value);
        }
        return $tpl->fetch();
    }

    private static function ensureDir(string $dir): string
    {
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException("Directory '{$dir}' cannot be created.");
        }
        return $dir;
    }
}
