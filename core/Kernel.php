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

namespace Stuck1A\StuckMVC\Core;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Stuck1A\StuckMVC\Core\Auth\Auth;
use Stuck1A\StuckMVC\Core\Auth\UserProvider;
use Stuck1A\StuckMVC\Core\Cache\FileCache;
use Stuck1A\StuckMVC\Core\Cache\NullCache;
use Stuck1A\StuckMVC\Core\Config\Config;
use Stuck1A\StuckMVC\Core\Config\FeatureSettings;
use Stuck1A\StuckMVC\Core\Controller\ActionDispatcher;
use Stuck1A\StuckMVC\Core\Controller\BackendController;
use Stuck1A\StuckMVC\Core\Controller\Controller;
use Stuck1A\StuckMVC\Core\Controller\PageController;
use Stuck1A\StuckMVC\Core\Controller\WebhookController;
use Stuck1A\StuckMVC\Core\Database\Connection;
use Stuck1A\StuckMVC\Core\Database\Model;
use Stuck1A\StuckMVC\Core\Error\ErrorHandler;
use Stuck1A\StuckMVC\Core\Http\HttpException;
use Stuck1A\StuckMVC\Core\Http\Request;
use Stuck1A\StuckMVC\Core\Http\RequestContext;
use Stuck1A\StuckMVC\Core\Http\Response;
use Stuck1A\StuckMVC\Core\Http\Session;
use Stuck1A\StuckMVC\Core\Log\FileLogger;
use Stuck1A\StuckMVC\Core\Routing\ApplicationMap;
use Stuck1A\StuckMVC\Core\Routing\MapBuilder;
use Stuck1A\StuckMVC\Core\Routing\Route;
use Stuck1A\StuckMVC\Core\Routing\RouteCollection;
use Stuck1A\StuckMVC\Core\Routing\UrlGenerator;
use Stuck1A\StuckMVC\Core\Support\Path;
use Stuck1A\StuckMVC\Core\View\TemplatePlugins;
use Stuck1A\StuckMVC\Core\View\View;

/**
 * The application kernel: sets up all services and turns a request into a response.
 *
 * Request lifecycle:
 *   1. The route is looked up in the routing table (404 if there's none).
 *   2. POST requests are passed to the {@see ActionDispatcher}, which executes the requested action, or to the
 *      receive() method of a {@see WebhookController} (no CSRF check; it authenticates the sender itself).
 *   3. GET/HEAD requests are passed to the route's controller, which renders the page.
 *   4. Errors are turned into error pages (404, 403, 500, ...). Details are only shown in debug mode.
 */
final class Kernel
{
    private readonly Container $container;

    private readonly Config $config;

    /**
     * @param array<string, mixed> $server Server parameters, used to detect the base path
     */
    public function __construct(private readonly string $rootDir, Config $config, array $server = [])
    {
        $settings = new FeatureSettings(Path::resolve($rootDir, (string) $config->get('paths.settings', 'tmp/settings/site.json')));
        $defaults = (array) $config->get('features', []);
        $overrides = array_intersect_key($settings->load(), $defaults);
        $this->config = $config->with(['features' => array_replace($defaults, $overrides)]);
        $this->container = new Container();
        $this->container->instance(FeatureSettings::class, $settings);
        $this->registerServices($server);
    }

    /**
     * Creates the kernel for the web front controller including the global error handler.
     */
    public static function boot(string $rootDir): self
    {
        $errorHandler = ErrorHandler::register(false, $rootDir . '/offline.html');
        $config = Config::load($rootDir);
        $errorHandler->setDebug($config->isDebug());
        $kernel = new self($rootDir, $config, $_SERVER);
        $errorHandler->setLogger($kernel->container->get(LoggerInterface::class));
        return $kernel;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function rootDir(): string
    {
        return $this->rootDir;
    }

    public function basePath(): string
    {
        return $this->container->get(UrlGenerator::class)->basePath();
    }

    public function handle(Request $request): Response
    {
        $this->container->instance(Request::class, $request);
        $this->container->get(RequestContext::class)->set($request, null);
        try {
            $route = $this->container->get(RouteCollection::class)->get($request->path);
            $this->container->get(RequestContext::class)->set($request, $route);
            if ($route === null) {
                throw HttpException::notFound();
            }
            if ($request->isPost() && $route->controller !== null && is_subclass_of($route->controller, WebhookController::class)) {
                $response = $this->container->make($route->controller)->receive($request, $route);
            } elseif ($request->isPost()) {
                $response = $this->container->get(ActionDispatcher::class)->dispatch($request, $route);
            } elseif (in_array($request->method, ['GET', 'HEAD'], true)) {
                $response = $this->runController($request, $route);
            } else {
                throw new HttpException(405, 'Method not allowed');
            }
        } catch (HttpException $e) {
            $response = $this->errorResponse($e->getStatus(), $request, $e);
        } catch (\Throwable $e) {
            $this->container->get(LoggerInterface::class)->error('Request to /{path} failed: {message}', [
                'path' => $request->path, 'message' => $e->getMessage(), 'exception' => $e,
            ]);
            $response = $this->errorResponse(500, $request, $e);
        }
        return $this->withSecurityHeaders($response);
    }

    private function runController(Request $request, Route $route): Response
    {
        $class = $route->controller ?? ($route->isAdmin() ? BackendController::class : PageController::class);
        /** @var Controller $controller */
        $controller = $this->container->make($class);
        if (!$controller->authorize($request)) {
            return $controller->denied($request);
        }
        $this->assignGlobals($request, $route);
        return $controller->handle($request, $route);
    }

    /**
     * Variables which are available in every template.
     */
    private function assignGlobals(Request $request, ?Route $route): void
    {
        $view = $this->container->get(View::class);
        $session = $this->container->get(Session::class);
        $auth = $this->container->get(Auth::class);
        $view->assign('currentRoute', $route);
        $view->assign('currentPath', $request->path);
        $view->assign('currentUser', $auth->user());
        $view->assign('isAdmin', $auth->isAdmin());
        $view->assign('flashes', $session->exists() ? $session->consumeFlashes() : []);
        $view->assign('basePath', $this->basePath());
        $view->assign('debug', $this->config->isDebug());
        $view->assign('features', (array) $this->config->get('features', []));
    }

    private function errorResponse(int $status, Request $request, \Throwable $e): Response
    {
        if ($status >= 500 && $this->config->isDebug()) {
            return new Response(ErrorHandler::debugPage($e), $status);
        }
        try {
            $routes = $this->container->get(RouteCollection::class);
            $route = $routes->get($status === 404 ? 'error/404' : 'error') ?? $routes->get('error');
            if ($route === null) {
                throw new \RuntimeException('No error page available.');
            }
            $this->container->get(RequestContext::class)->set($request, $route);
            $this->assignGlobals($request, $route);
            $html = $this->container->get(View::class)->render($route->template, ['status' => $status]);
        } catch (\Throwable $renderError) {
            $this->container->get(LoggerInterface::class)->error('Rendering the error page failed: {message}', [
                'message' => $renderError->getMessage(), 'exception' => $renderError,
            ]);
            $html = '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Fehler ' . $status
                . '</title></head><body><h1>Fehler ' . $status . '</h1></body></html>';
        }
        return (new Response($html, $status))->setHeader('Cache-Control', 'no-store');
    }

    private function withSecurityHeaders(Response $response): Response
    {
        $response
            ->setDefaultHeader('X-Content-Type-Options', 'nosniff')
            ->setDefaultHeader('X-Frame-Options', 'SAMEORIGIN')
            ->setDefaultHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->setDefaultHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        if ($this->container->get(Session::class)->isStarted()) {
            // personalized pages (logged in user, CSRF tokens) must never be stored by shared caches
            $response->setDefaultHeader('Cache-Control', 'private, no-store');
        }
        return $response;
    }

    /**
     * @param array<string, mixed> $server
     */
    private function registerServices(array $server): void
    {
        $c = $this->container;
        $config = $this->config;
        $path = fn (string $key): string => Path::resolve($this->rootDir, (string) $config->get($key));

        $c->instance(self::class, $this);
        $c->instance(Container::class, $c);
        $c->instance(ContainerInterface::class, $c);
        $c->instance(Config::class, $config);
        $c->set(LoggerInterface::class, static fn (): LoggerInterface => new FileLogger(
            $config->get('logging.file') === null ? null : $path('logging.file'),
            (string) $config->get('logging.level', 'warning'),
            (string) $config->get('logging.date_format', 'd.m.Y H:i:s'),
        ));
        $c->set(CacheInterface::class, static function (Container $c) use ($config, $path): CacheInterface {
            if (!$config->get('cache.enabled', true)) {
                return new NullCache();
            }
            try {
                return new FileCache($path('paths.cache'));
            } catch (\RuntimeException $e) {
                // the site keeps working without cache (just slower) - but somebody should fix the permissions
                $c->get(LoggerInterface::class)->warning('Cache disabled: {message}', ['message' => $e->getMessage()]);
                return new NullCache();
            }
        });
        $c->set(ApplicationMap::class, fn (Container $c): ApplicationMap => (new MapBuilder($config, $this->rootDir, $c->get(CacheInterface::class)))->load());
        $c->set(RouteCollection::class, static fn (Container $c): RouteCollection => $c->get(ApplicationMap::class)->routes);
        $c->set(UrlGenerator::class, static fn (): UrlGenerator => new UrlGenerator($config, UrlGenerator::detectBasePath($config, $server)));
        $c->set(RequestContext::class, static fn (): RequestContext => new RequestContext());
        $c->set(View::class, fn (Container $c): View => new View($config, $this->rootDir, $c->get(TemplatePlugins::class)));
        $c->set(Connection::class, static fn (): Connection => new Connection((array) $config->get('database', [])));
        $c->set(UserProvider::class, static fn (Container $c): UserProvider => $c->make((string) $config->get('auth.provider')));
        $c->set(Session::class, static function (Container $c) use ($config): Session {
            $name = (string) $config->get('session.name', 'stuckmvc_sid');
            $request = $c->bound(Request::class) ? $c->get(Request::class) : null;
            // native sessions need a web SAPI - on the CLI (tests, console) the session lives in memory
            return new Session($name, $request?->isSecure() ?? false, PHP_SAPI !== 'cli', $request?->hasCookie($name) ?? false);
        });

        Model::setConnection($c->get(Connection::class));
    }
}
