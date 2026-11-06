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

/*************************************************************************************/
/*  Default configuration of the application.                                        */
/*                                                                                   */
/*  NEVER put credentials or other secrets in here - this file is under version      */
/*  control. Installation-specific values (database credentials, debug mode, ...)    */
/*  belong into 'configs.local.php', which overrides any value of this file.         */
/*  Copy 'configs.local.example.php' to 'configs.local.php' to get started.          */
/*                                                                                   */
/*  All paths are relative to the document root of the application.                  */
/*************************************************************************************/

return [
    'application' => [
        // Shows error details in the browser and turns PHP warnings into exceptions. NEVER enable on live systems!
        'debug'     => false,
        // Identifier of the active theme (letters, digits, '_' and '-'). Replaces the tag <themename> in paths.
        'theme'     => 'classic',
        // URL path of the application if it doesn't run in the document root of the domain, e.g. '/myapp'.
        // null = detect automatically.
        'base_path' => null,
        // Public address used for links in e-mails (never taken from the request, which could be forged).
        'url'       => 'http://localhost:8000',
        // Root namespace of the application classes (controllers, models, widgets).
        'namespace' => 'Stuck1A\\StuckMVC\\Application',
    ],

    'features' => [
        // Feature flags of the application. Routes can be tied to them (see 'routing.feature_routes').
        // Administrators may override them in the backend (stored in 'paths.settings').
    ],

    'cache' => [
        // Caches the routing table and similar data. Invalidates itself when files change.
        'enabled' => true,
    ],

    'logging' => [
        // Log file (null = PHP's native error log). Located in tmp/, which is not accessible from the web.
        'file'        => 'tmp/logs/app.log',
        // Minimum level of logged messages: debug, info, notice, warning, error, critical, alert, emergency
        'level'       => 'notice',
        'date_format' => 'd.m.Y H:i:s',
    ],

    'database' => [
        // PDO driver ('mysql' or 'sqlite')
        'driver'  => 'mysql',
        'host'    => '127.0.0.1',
        'port'    => 3306,
        // Optional UNIX socket path instead of host/port
        'socket'  => null,
        'name'    => '',
        'user'    => '',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    'session' => [
        'name' => 'stuckmvc_sid',
    ],

    'auth' => [
        // Class which loads user accounts for the login
        'provider'             => 'Stuck1A\\StuckMVC\\Application\\Model\\UserProvider',
        // Failed login attempts per username and IP before the login is blocked temporarily
        'max_attempts'         => 5,
        // Duration of the login block in seconds
        'lockout_seconds'      => 900,
        // Logged in users are logged out after this many seconds of inactivity (0 = never)
        'idle_timeout'         => 7200,
    ],

    'templates' => [
        // Name of default templates: routes pointing to a directory render this template of the directory.
        'default_name'  => 'index.tpl',
        // Recompile templates on every request. Only useful while developing Smarty plugins.
        'force_compile' => false,
    ],

    'routing' => [
        // Route prefixes controlled by a flag in 'features' (including their child routes), e.g. 'blog' => 'blog_enabled'.
        'feature_routes' => [],
        // Predefined routes which overwrite generated routes with the same path ('' is the start page).
        'static_routes' => [
            ''      => 'application/views/pages/home/index.tpl',
            'index' => 'application/views/pages/home/index.tpl',
        ],
        // Optional SEO data per route. By default the title is taken from the {block 'title'} of the template.
        'pages' => [
            '' => ['description' => 'Welcome to StuckMVC.'],
        ],
    ],

    'paths' => [
        'pages'       => 'application/views/pages',             // content pages (become routes automatically)
        'admin'       => 'application/views/admin',             // backend pages (routes below 'admin/')
        'views'       => 'application/views',                   // template base directory
        'theme'       => 'application/views/<themename>',       // templates of the active theme
        'controllers' => 'application/Controller',              // page and backend controllers
        'widgets'     => 'application/Widget',                  // widgets
        'downloads'   => 'out/downloads',                       // downloadable files
        'img'         => 'out/img',                             // images
        'css'         => 'out/src/<themename>/css',             // stylesheets
        'js'          => 'out/src/<themename>/js',              // scripts
        'cache'       => 'tmp/cache',                           // file cache
        'compiled'    => 'tmp/compiled',                        // compiled Smarty templates
        'rendered'    => 'tmp/rendered',                        // Smarty output cache
        'settings'    => 'tmp/settings/site.json',              // persistent admin settings (never clear with cache)
    ],
];
