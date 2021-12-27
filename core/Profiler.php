<?php
/*
 *  This file is part of StuckMVC <https://stuck1a.de/coding/stuckmvc>,
 *  Copyright (c) 2021.
 *  The StuckMVC framework is free software: you can redistribute it and/or modify it
 *  under the terms of the GNU General Public License version 3 as published by
 *  the Free Software Foundation.
 *  
 *  It is distributed in the hope that it will be useful, but without any warranty;
 *  without even the implied warranty of merchantability of fitness for a
 *  particular purpose. See the GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License along with
 *  StuckMVC. If not, see <https://www.gnu.org/licenses/>. 
 *
 *  FOR A SOMEWHAT FREER WORLD.
 */

namespace Stuck1A\StuckMVC\Core;


/**
 * Debugging Tool to perform simple speed tests on any code snippet<br><br>
 * 
 * Use <code>Profiler::start()</code> to start time capture, then execute any snippet you want to profile.
 * To stop time capture and store the execution time, call <code>Profiler::stop()</code> right after the snippet.
 * To get the captured time in seconds, call <code>Profiler::finish()</code>.
 *
 * You can also wrap the profiling sequence in an loop or any iterative context to capture the execution time more than
 * once. If so, <code>Profiler::finish()</code> will return the average execution time. This will counteract the effect
 * of random scattering and therefore <i>greatly</i> increase the results precision and expressiveness.
 *
 * @example iterative variant, 100 repetitions
 *          $reps = 100;
 *          for ( $i = 0; $i < $reps; $i++ ) {
 *            \Stuck1A\StuckMVC\Core\Profiler::start();
 *            // insert code to profile here...
 *            \Stuck1A\StuckMVC\Core\Profiler::stop();
 *          }
 *          print_r("[PROFILER] Executed {$reps} aTimes - average (ms): " . (\Stuck1A\StuckMVC\Core\Profiler::finish() * 1000));
 * 
 * @example non-iterative variant
 *          \Stuck1A\StuckMVC\Core\Profiler::start();
 *          // insert code to profile here...
 *          \Stuck1A\StuckMVC\Core\Profiler::stop();
 *          print_r('[PROFILER] Execution time (s): ' . \Stuck1A\StuckMVC\Core\Profiler::finish();
 *          
 * @package StuckMVC
 */
class Profiler {
  /**
   * Start microtime of the currently profiled iteration 
   * @var ?float
   */
  private static $fStart = null;
  
  /**
   * Stores all time differences per iteration while profiling code snippets
   * @var array
   */
  private static $aTimes = [];
  
  
  /**
   * Marks the start of a new profiling iteration by capturing the current microtime
   */
  public static function start() {
    Profiler::$fStart = microtime(true);
  }
  
  
  /**
   * Marks the end of an active profiling iteration by adding the difference of iteration start and now to the storage
   */
  public static function stop() {
    Profiler::$aTimes[] = microtime(true) - Profiler::$fStart;
  }
  
  
  /**
   * Calculates the average of all currently saved iteration times and clears the storage for further profiling jobs
   * 
   * @return float Average execution time of the profiled snipped in seconds.
   */
  public static function finish(): float {
    $sum = array_reduce(Profiler::$aTimes, function ($last, $curr) { return $last + $curr; });
    $avg = $sum / count(Profiler::$aTimes);
    Profiler::$fStart = null;
    Profiler::$aTimes = [];
    return $avg;
  }
  
}
