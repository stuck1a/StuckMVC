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

namespace Stuck1A\StuckMVC\Core\Error;

use ErrorException;
use Psr\Log\LoggerInterface;
use Stuck1A\StuckMVC\Core\Log\FileLogger;

/**
 * Last line of defense for errors which are not handled by the kernel (boot failures, fatal errors).
 *
 * In debug mode, PHP warnings and notices are converted to exceptions, so they're noticed immediately. In production
 * they're only logged. Visitors never see error details unless debug mode is enabled; they get the offline page.
 */
final class ErrorHandler
{
    private const FATAL = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;

    private ?LoggerInterface $logger = null;

    private function __construct(private bool $debug, private readonly string $offlinePage)
    {
    }

    public static function register(bool $debug, string $offlinePage): self
    {
        $handler = new self($debug, $offlinePage);
        ini_set('display_errors', $debug ? '1' : '0');
        error_reporting(E_ALL);
        set_error_handler($handler->handleError(...));
        set_exception_handler($handler->handleException(...));
        register_shutdown_function($handler->handleShutdown(...));
        return $handler;
    }

    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
        ini_set('display_errors', $debug ? '1' : '0');
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $level)) {
            // suppressed by the @ operator
            return false;
        }
        if ($this->debug && !in_array($level, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
        $this->log($level & (E_DEPRECATED | E_USER_DEPRECATED) ? 'debug' : 'warning', "{$message} in {$file}:{$line}");
        return true;
    }

    public function handleException(\Throwable $e): void
    {
        $this->log('critical', 'Uncaught ' . FileLogger::describe($e));
        $this->render($e);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error === null || !($error['type'] & self::FATAL)) {
            return;
        }
        $this->log('critical', "Fatal error: {$error['message']} in {$error['file']}:{$error['line']}");
        $this->render(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
    }

    /**
     * Renders the error details (debug mode) or the offline page.
     */
    public function render(\Throwable $e): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code(503);
            header('Content-Type: text/html; charset=UTF-8');
            header('Cache-Control: no-store');
        }
        if ($this->debug) {
            echo self::debugPage($e);
            return;
        }
        echo is_readable($this->offlinePage)
            ? (string) file_get_contents($this->offlinePage)
            : '<!DOCTYPE html><title>Offline</title><p>This website is currently offline due to maintenance.</p>';
    }

    /**
     * HTML page with exception details for debug mode. All details are escaped.
     */
    public static function debugPage(\Throwable $e): string
    {
        $details = htmlspecialchars(FileLogger::describe($e), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $title = htmlspecialchars($e::class . ': ' . $e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return "<!DOCTYPE html><html lang=\"en\"><head><meta charset=\"UTF-8\"><title>Error</title></head><body style=\"font-family:sans-serif;margin:2rem\">"
            . "<h1 style=\"font-size:1.3rem;color:#a11\">{$title}</h1><pre style=\"white-space:pre-wrap;background:#f6f6f6;padding:1rem\">{$details}</pre>"
            . '<p><small>Debug mode is enabled. Disable it on live systems (application.debug).</small></p></body></html>';
    }

    private function log(string $level, string $message): void
    {
        if ($this->logger !== null) {
            try {
                $this->logger->log($level, $message);
                return;
            } catch (\Throwable) {
                // fall through to the native log
            }
        }
        error_log("[{$level}] {$message}");
    }
}
