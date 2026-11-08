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

use Psr\Container\ContainerInterface;
use Smarty\Smarty;
use Smarty\Template;
use Stuck1A\StuckMVC\Core\Config\Config;
use Stuck1A\StuckMVC\Core\Http\Csrf;
use Stuck1A\StuckMVC\Core\Routing\UrlGenerator;
use Stuck1A\StuckMVC\Core\Widget\WidgetRenderer;

/**
 * Registers the framework's template functions and filters.
 *
 * Functions:
 *   {getRootLink}              URL of the start page (with trailing slash), e.g. {getRootLink}music
 *   {url path='music'}         URL of a route
 *   {getImagesPath}, {getStylesPath}, {getScriptsPath}, {getDownloadsPath}
 *                              URLs of the asset directories (with trailing slash)
 *   {getThemeName}             Name of the active theme
 *   {csrf_field}               Hidden input field with the CSRF token (mandatory in forms which trigger an action)
 *   {include_widget name=x}    Renders a widget at runtime ({widget name=x} is an alias)
 *   {emend old='..' new='..'}  Strikes old text and appends the emended version
 * Output filter:
 *   protect_email              Replaces the @ of email addresses by (at) as simple protection against spam bots
 */
final class TemplatePlugins
{
    public function __construct(
        private readonly UrlGenerator $url,
        private readonly Config $config,
        private readonly ContainerInterface $container,
    ) {
    }

    public function register(Smarty $smarty): void
    {
        $functions = [
            'getRootLink'      => fn (): string => $this->url->to(),
            'url'              => fn (array $params): string => $this->url->to((string) ($params['path'] ?? '')),
            'getImagesPath'    => fn (): string => $this->url->dir('img'),
            'getStylesPath'    => fn (): string => $this->url->dir('css'),
            'getScriptsPath'   => fn (): string => $this->url->dir('js'),
            'getDownloadsPath' => fn (): string => $this->url->dir('downloads'),
            'getThemeName'     => fn (): string => htmlspecialchars((string) $this->config->get('application.theme')),
            'csrf_field'       => fn (): string => $this->csrfField(),
            'include_widget'   => fn (array $params, Template $template): string => $this->widget($params),
            'widget'           => fn (array $params, Template $template): string => $this->widget($params),
            'emend'            => self::emend(...),
        ];
        foreach ($functions as $name => $callback) {
            $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, $name, $callback);
        }
        $smarty->registerFilter('output', self::protectEmail(...));
    }

    /**
     * Strikes old text and appends the emended version with some fancy styling.
     * The texts are template literals written by the site author, so they may contain HTML.
     *
     * @param array<string, mixed> $params
     */
    public static function emend(array $params): string
    {
        if (!isset($params['old'])) {
            return '';
        }
        $html = "<span style='text-decoration:line-through;opacity:0.5;'>{$params['old']}</span>";
        if (isset($params['new'])) {
            $html .= "<span style='font-style:italic;opacity:0.9;'>{$params['new']}</span>";
        }
        return $html;
    }

    /**
     * Converts the @ sign in email addresses to (at) as a simple protection against spam bots.
     * Content of script and style elements is kept untouched.
     */
    public static function protectEmail(string $output): string
    {
        $parts = preg_split('#(<(?:script|style)\b.*?</(?:script|style)>)#is', $output, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $output;
        }
        foreach ($parts as $i => $part) {
            if ($i % 2 === 0) {
                $parts[$i] = (string) preg_replace('!([\w.+-]+)@([a-zA-Z0-9.\-]+\.(?:[a-zA-Z]{2,}|[0-9]{1,3}))!', '$1(at)$2', $part);
            }
        }
        return implode('', $parts);
    }

    private function csrfField(): string
    {
        $token = $this->container->get(Csrf::class)->token();
        return '<input type="hidden" name="' . Csrf::FIELD . '" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * @param array<string, mixed> $params
     */
    private function widget(array $params): string
    {
        $name = (string) ($params['name'] ?? '');
        unset($params['name']);
        return $this->container->get(WidgetRenderer::class)->render($name, $params);
    }
}
