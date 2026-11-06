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

namespace Stuck1A\StuckMVC\Core\Log;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Stringable;

/**
 * PSR-3 logger which appends entries to a log file.
 *
 * Messages below the configured minimum level are dropped. If the log file is not writable, PHP's native error
 * log is used as fallback, so log entries never get lost silently. Control characters are stripped from messages
 * to prevent log forging through user input.
 */
final class FileLogger extends AbstractLogger
{
    private const LEVELS = [
        LogLevel::DEBUG     => 0,
        LogLevel::INFO      => 1,
        LogLevel::NOTICE    => 2,
        LogLevel::WARNING   => 3,
        LogLevel::ERROR     => 4,
        LogLevel::CRITICAL  => 5,
        LogLevel::ALERT     => 6,
        LogLevel::EMERGENCY => 7,
    ];

    private readonly int $minLevel;

    public function __construct(
        private readonly ?string $file,
        string $minLevel = LogLevel::WARNING,
        private readonly string $dateFormat = 'd.m.Y H:i:s',
    ) {
        $this->minLevel = self::LEVELS[strtolower($minLevel)] ?? self::LEVELS[LogLevel::WARNING];
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $level = strtolower((string) $level);
        if (!isset(self::LEVELS[$level])) {
            throw new InvalidArgumentException("Unknown log level '{$level}'.");
        }
        if (self::LEVELS[$level] < $this->minLevel) {
            return;
        }
        $entry = sprintf("%s [%s] %s", date($this->dateFormat), strtoupper($level), $this->interpolate((string) $message, $context));
        if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
            $entry .= PHP_EOL . self::describe($context['exception']);
        }
        $entry .= PHP_EOL;
        if ($this->file === null || !$this->write($entry)) {
            error_log(rtrim($entry));
        }
    }

    /**
     * Formats an exception including its previous exceptions and stack trace.
     */
    public static function describe(\Throwable $e): string
    {
        $lines = [];
        do {
            $lines[] = sprintf('%s: %s in %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine());
            $lines[] = $e->getTraceAsString();
        } while ($e = $e->getPrevious());
        return implode(PHP_EOL, $lines);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value === null || $value instanceof Stringable) {
                $replace['{' . $key . '}'] = match (true) {
                    $value === null => 'null',
                    is_bool($value) => $value ? 'true' : 'false',
                    default         => (string) $value,
                };
            }
        }
        $message = strtr($message, $replace);
        // strip control characters (except tabs), so single entries can't be split or forged
        return (string) preg_replace('/[\x00-\x08\x0A-\x1F\x7F]/', ' ', $message);
    }

    private function write(string $entry): bool
    {
        $dir = dirname((string) $this->file);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }
        return @file_put_contents((string) $this->file, $entry, FILE_APPEND | LOCK_EX) !== false;
    }
}
