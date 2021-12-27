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
 * Contains general helper functions for the framework.
 * @package StuckMVC
 */
class Base {
  
  /**
   * Returns everything of a given string between the <b>last</b> occurrences of start and end (those needles excluded)
   * 
   * @param   string $haystack The subject string.
   * @param   string $start    The start needle.
   * @param   string $end      The end needle.
   *                           
   * @return  false|string The substring between last matches of $start and $end or false on failure.
   *                           
   * @example echo Base::substr_between('he.php.llo/meow/foo_bar.php', '/', '.php');    // output: 'foo_bar'
   */
  public static function substr_between(string $haystack, string $start, string $end): ?string {
    return substr($haystack, strrpos($haystack, $start), strrpos($haystack, $end));
  }
  
  
  /**
   * Same as str_replace but only replaces the first match.
   *
   * Note:
   * While str_replace allows arrays as well, this function is limited to strings to prevent unnecessary overhead.
   * 
   * @param string $search
   * @param string $replace
   * @param string $subject
   *
   * @return string
   */
  public static function str_replace_first(string $search, string $replace, string $subject): string {
    $pos = strpos($subject, $search);
    if ( $pos !== false ) {
      return substr_replace($subject, $replace, $pos, strlen($search));
    }
    return $subject;
  }


  
  /**
   * Checks if $needle is found in $haystack and returns a boolean value (true/false) whether or not the $needle was
   * found<br><i>Polyfill-Wrapper of str_contains for PHP 4-7</i>
   *
   * @param string $haystack The subject string.
   * @param string $needle   The search string.
   *
   * @return bool True, if $subject contains $needle, false otherwise.
   */
  public static function str_contains(string $haystack, string $needle): bool {
    if ( function_exists('str_contains') ) {
      return str_contains($haystack, $needle);
    } else {
      return empty($needle) || strpos($haystack, $needle) !== false;
    }
  }
  
  
  /**
   * Polyfill of str_starts_with for PHP 7.<br>
   * The function returns true if the passed $haystack starts with the $needle string or false otherwise.
   * 
   * @param string $haystack
   * @param string $needle
   *                      
   * @return bool
   */
  public static function str_starts_with(string $haystack, string $needle): bool {
    if ( function_exists('str_starts_with') ) {
      return str_starts_with($haystack, $needle);
    } else {
      return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
  }

  
  /**
   * Polyfill of str_ends_with for PHP 7.<br>
   * The function returns true if the passed $haystack ends with the $needle string or false otherwise.
   * 
   * @param string $haystack
   * @param string $needle
   *               
   * @return bool
   */
  public static function str_ends_with(string $haystack, string $needle): bool {
    if ( function_exists('str_ends_with') ) {
      return str_ends_with($haystack, $needle);
    } else {
      $length = strlen($needle);
      return !($length > 0) || substr($haystack, -$length) === $needle;
    }
  }
  
  
  /**
   * Returns trailing name component of a path<br>
   * <i>Improved version of PHPs basename() - works with mixed path patterns (Windows/UNIX)</i>
   * 
   * @param string      $path   A path.
   * @param null|string $suffix [optional]<br>If the filename ends in $suffix this will also be cut off.
   *
   * @return string The base name of the given path.
   */
  public static function base_name(string $path, string $suffix = null): string {
    $basename = '';
    if ( preg_match('@^.*[\\\\/]([^\\\\/]+)$@s', $path, $matches) ) {
      $basename = $matches[1];
    } else if ( preg_match('@^([^\\\\/]+)$@s', $path, $matches) ) {
      $basename = $matches[1];
    }
    return isset($suffix) ?  Base::str_rtrim($basename, 1) : $basename;
  }
  
  
  /**
   * Removes some or all repetitions of a string sequence from the end of another string<br>
   * <i>Like rtrim() but for whole strings instead of an unordered charlist</i>
   *
   * @param string   $subject The string that will be trimmed.
   * @param string   $strip   The sequence to trim.
   * @param int|null $max     [optional]<br>Maximal amount of strip repetitions to remove.<br>
   *                          By default or if NULL it will trim any number of trailing repetitions.
   *
   * @return string The trimmed string.
   * @example print Base::str_rtrim('configs.inc.php', '.php');    // output: 'configs.inc'
   */
  public static function str_rtrim(string $subject, string $strip, int $max = null): string {
    $last = '';
    // limited variant
    if ( isset($max) ) {
      // shortcut if nothing shall be trimmed
      if ( $max++ < 1 ) {
        return $subject;
      }
      $lines = explode($strip, $subject);    // 'jpgFOO.jpgBAR.jpg.jpg' --> [ 'jpgFOO', 'BAR', '', '' ]
      do {
        // remove empty entries (they represent trailing matches)
        $last = array_pop($lines);
      } while ( --$max > 0 && empty($last) && count($lines) );
      // reconstruct result (insert strip between entries, those were non-trailing matches)
      return implode($strip, array_merge($lines, array($last)));    // [ 'jpgFOO', '.jpg', 'BAR' ] --> 'jpgFOO.jpgBAR'
    }
    // unlimited variant
    $lines = explode($strip, $subject);
    do {
      $last = array_pop($lines);
    } while ( empty($last) && count($lines) );
    return implode($strip, array_merge($lines, array($last)));
  }
  
  
  /**
   * Checks whether a given string contains alphanumeric (letters and digits) characters only or not.
   * Additional characters can be allowed by specify them as entries of an array as the optional whitelist parameter.
   * Note: For reasons of performance and compatibility, the most efficient option for this task the actual PHP
   * interpreter can offer will be determined and used.
   *
   * @param string $subject   the string value to be tested
   * @param array  $whitelist (optional) additional characters the subject string may contain (default: none)
   *
   * @return bool true if subject string contains alphanumeric (or whitelisted characters) only, false otherwise.
   * @example Check whether $name consists only of letters, digits and some special characters:<br>
   *          echo Base::isAlphanumeric($name, ['-', '_', '@', '#', '€', '%']) ? "success" : "invalid input";
   */
  public static function isAlphanumeric(string $subject, array $whitelist = []): bool {
    // ctype possible?
    if ( function_exists('ctype_alnum') ) {
      return ctype_alnum(str_replace($whitelist, '', $subject));
    // regex possible?
    } elseif ( function_exists("preg_match") ) {
      return preg_match('/^[a-z0-9]+$/i', $subject) > 0;
    // do by string manipulation
    } else {
      return trim($subject, '0..9A..Za..Z' . implode($whitelist)) == '';
    }
  }
  
  
  /**
   * FIXME: This will return true for remote hosts connected through ssh tunnel
   * TODO: Umsteigen auf runningInDevMode() o.ä...
   * Checks the remote address for ip addresses which infers that the execution finds place local<br>
   * This four possibly ip addresses should suit for most cases, except some proxy configurations
   * 
   * @return bool True, if script runs on a loopback or local-link address, false otherwise.
   */
  public static function isExecOnLocalhost(): bool {
    // TODO: If ( sshTunnel ) return false;
    if ( $_SERVER['REMOTE_ADDR'] === '127.0.0.1' ||
         $_SERVER['REMOTE_ADDR'] === '::1' ||
         Base::str_starts_with($_SERVER['REMOTE_ADDR'], 'fe80:') ||
         Base::str_starts_with($_SERVER['REMOTE_ADDR'], '169.254.') ) {
      return true;
    }
    return false;
  }
  
  

}
