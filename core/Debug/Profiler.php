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

namespace Stuck1A\StuckMVC\Core\Debug;

/**
 * Debugging tool to measure the execution time of code snippets.
 *
 * Wrap the snippet with start() and stop(). Repeating this in a loop and reading the average with finish() greatly
 * increases the precision, since random scattering is evened out.
 *
 * @example
 *   $profiler = new Profiler();
 *   for ($i = 0; $i < 100; $i++) {
 *       $profiler->start();
 *       // code to profile
 *       $profiler->stop();
 *   }
 *   echo 'Average (ms): ' . $profiler->finish() * 1000;
 */
final class Profiler
{
    private ?float $start = null;

    /** @var list<float> */
    private array $times = [];

    public function start(): void
    {
        $this->start = hrtime(true) / 1e9;
    }

    public function stop(): void
    {
        if ($this->start === null) {
            throw new \LogicException('Profiler::stop() called without start().');
        }
        $this->times[] = hrtime(true) / 1e9 - $this->start;
        $this->start = null;
    }

    /**
     * Measures a callable once and returns its execution time in seconds.
     */
    public static function measure(callable $callback): float
    {
        $start = hrtime(true);
        $callback();
        return (hrtime(true) - $start) / 1e9;
    }

    /**
     * Returns the average execution time of all measured iterations in seconds and resets the profiler.
     */
    public function finish(): float
    {
        if ($this->start !== null) {
            $this->stop();
        }
        $average = $this->times === [] ? 0.0 : array_sum($this->times) / count($this->times);
        $this->times = [];
        return $average;
    }
}
