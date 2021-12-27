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
 * Static class Logger. Provides logging functions.
 * @package Stuck1A\StuckMVC\Core
 */
class Logger {
  /**
   * Appends a new entry to the given logfile. Prefixes given message with type and current date/time.
   * @param  string $msg  message which will be appended to the logfile (recommended to match the frameworks default pattern)
   * @param  string $type level of the entry. May be anything, but it's recommended to use ERROR (default), WARNING, INFO or DEBUG only
   * @param  string $path absolute path to the logfile. if no path is given, it will fetch the configured path
   * @return bool         true if message logged successful 
   * @throws \RuntimeException file is either not found, not writeable or locked
   */
  static function send(string $msg, string $type = 'error', string $path = ''): bool {
    // load default logfile path if no path is given
    if ( $path === '' ) {
      $path = (include('configs.php'))['project']['logging']['filepath'];
    }
    $path = realpath($path);
    // build entry
    $msg = '[' . date('d.m.Y H:i:s') . '] [' . strtoupper($type) . '] ' . $msg . PHP_EOL;
    // write to logfile
    if ( is_writable($path) ) {
      if ( !$handle = fopen($path, 'a') ) {
        throw new \RuntimeException('failed to open logfile: ' . $path . ' - check \'logfile\' path in configs.php!');
      }
      if ( fwrite($handle, $msg) === false ) {
        throw new \RuntimeException('failed to write to logfile: ' . $path . ' - check file permissions!');
      }
      fclose($handle);
      return true;
    } else {
      throw new \RuntimeException('access denied when attempting to write to file: ' . $path . ' - remove write protection!');
    }
  }

 
}

