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

namespace Stuck1A\StuckMVC\Core\Autoloader;

use LogicException;


/**
 * Currently supports following types of autoload logic:
 * 
 *   - <b>class maps:</b><br>
 *     Uses a prefabricated list that assigns a resolvable path to each class (or file).
 *     Usually an one-dimensional associative Array is used as class map containing entries
 *     formatted like <br><code>'Namespace\\MyClass' => 'path/to/MyClass.php'</code><br>
 *     Characteristics:<br>
 *     quite fast, simple and fail-safe, prioritization per single class/file, large maps, high maintenance effort,
 *     old-fashion, pretty flexible (easy autoload of arbitrary files at any reachable location, even dynamic ones),
 *     support for files with and without namespace
 * 
 *   - <b>namespace maps:</b><br>
 *     Uses a prefabricated list that assigns (at least) one resolvable path to each namespace.<br>
 *     While its possible to add multiple paths to a single namespace, it's not recommend due to conventions.<br>
 *     Characteristics:<br>
 *     reduced map size and maintenance effort (only new namespaces need to be added),
 *     reduced performance (the slower the more members in namespace as it have to traverse target namespace)
 * 
 *   - <b>PSR-4 convention:</b><br>
 *     Relies on the "PSR-4" convention being adhered to, i.e. the directory structure reflects the namespace structure
 *     (not case-sensitive) and each class must be defined in a separate file of the same name (plus extension '.php')
 *     Characteristics:<br>
 *     no maps needed and therefore no maintenance effort, best performance (no I/O operations needed for searching),
 *     state of the art, no prioritization (basically same performance for all classes), enforces use of namespaces,
 *     can lead to unnecessarily complex directory structures, users must know the "PSR-4" conventions and adhere to it
 *     when extending the application
 *
 * <br><b><i>Notes:</i></b><br>
 *   - <i>Always includes the full corresponding file, even if this file contains further classes and regardless of
 *     the used autoload type. Thereby any file-level code is executed while the autoload process! <br>
 *     Tricky programmers can use this behavior to place code which shall be executed before/after a class is loaded</i>
 *
 * @package StuckMVC
 */
class Autoloader {
  /**
   * Class map for framework classes
   * Used by autoloader function of type 'Class Map'
   * @var array
   */
  private static $classes;

  /**
   * @deprecated
   * Processed paths of autoload source directories
   * Used by autoloader function of type 'Namespace Map'
   * @var array
   */
  private static $namespaces;
  /**
   * @var mixed
   */
  
  
  
  /**
   * Prepares and registers autoload function of the specified type<br><br>
   *
   * If you want to keep already registered autoloader functions (including the default one), use the second parameter.
   * This will append it to existing ones, meaning it will be used if any other autoloader failed. This allows to
   * define a prioritized list of autoloader types the application shall try to include files with.
   *
   * @param string   $type  [optional]<br>
   *                        The type of logic the autoloader shall use. See class description for more details about the
   *                        different types of autoload logic.<br>Possible types are:<i>
   *                        <br>- 'classmap' (default/fallback)
   *                        <br>- 'namespaces'
   *                        <br>- 'psr4'</i>
   * @param bool  $prepend  [optional]<br>
   *                        If true, prepends the autoloader on the autoload queue instead of appending it, so the
   *                        application will try to autoload files by this autoloader first.
   * @param bool  $replace  [optional]<br>
   *                        If true, removes already registered autoloader functions from the autoload queue before
   *                        registering this one, so it will be the only autoloader the application uses.   
   */
  public static function register(string $type = 'classmap', bool $prepend = false, bool $replace = false) {
    switch ( $type ) {
      default:
      case 'classmap': {
        self::init_classmap($prepend, $replace);
        return;
      }
      case 'namespaces': {
        self::init_namespaces($prepend, $replace);
        return;
      }
      case 'psr4': {
      self::init_psr4($prepend, $replace);
        return;
      }
    }
  }
  

  /**
   * Loads the class map and registers new autoloader function of type "Class Map"
   *
   * @param bool  $prepend   Whether the autoload function shall be prepended to the autoload queue instead of appended
   * @param bool  $replace   Whether the autoload queue shall be cleared before registration
   * 
   * @throws LogicException  If the registration of the autoload function failed
   */
  private static function init_classmap(bool $prepend, bool $replace) {
    // merge maps to yield an enormous speed up
    self::$classes = (require 'classmap_core.php') + (require 'classmap_application.php') + (require 'classmap_smarty.php');
    if ( $replace ) {
      self::autoloader_unregister_all();
    }
    spl_autoload_register(self::class . '::loader_classmap', true, $prepend);
  }
  
  
  /**
   * @deprecated
   * 
   * Loads the namespace map and registers new autoloader function of type "Namespace Map"
   * 
   * @param bool  $prepend   Whether the autoload function shall be prepended to the autoload queue instead of appended
   * @param bool  $replace   Whether the autoload queue shall be cleared before registration
   * 
   * @throws LogicException  If the registration of the autoload function failed
   */
  private static function init_namespaces(bool $prepend, bool $replace) {
    $namespaceMap = include('namespaces.php');
    $flatten = [];
    // unpacks ("flatten") paths of namespaces with multiple locations  
    foreach ( $namespaceMap as $entry ) {
      if ( is_array($entry) ) {
        foreach ( $entry as $path ) {
          $flatten[] = $path;
        }
        continue;
      }
      $flatten[] = $entry;
    }
    self::$namespaces = $flatten;
    if ( $replace ) {
      self::autoloader_unregister_all();
    }
    spl_autoload_register(self::class . '::loader_namespaces', true, $prepend);
  }
  
  
  /**
   * @deprecated
   * 
   * Registers new autoloader function of type "PSR-4 Convention".
   *
   * @param bool  $prepend   Whether the autoload function shall be prepended to the autoload queue instead of appended
   * @param bool  $replace   Whether the autoload queue shall be cleared before registration
   *                        
   * @throws LogicException  If the registration of the autoload function failed
   */
  private static function init_psr4(bool $prepend, bool $replace) {
    if ( $replace ) {
      self::autoloader_unregister_all();
    }
    spl_autoload_register(self::class . '::loader_psr4', true, $prepend);
  }
  
  
  /**
   * Autoload function used by autoloader type "Class Map".
   * 
   * Walks through all paths from the class map and searchs for the target file.
   * Will use include() to load files as it offers the best performance.
   * Supports autoload of any plain text file using any/no file extension
   *
   * @param string $class FQN of target class or file.
   *
   * @return bool True on success, false on failure.
   */
  public static function loader_classmap($class) {
    if ( array_key_exists($class, self::$classes) ) {
      include self::$classes[$class];
      return true;
    }
    return false;
  }
  
  
  /**
   * @deprecated 
   *            
   * Autoload function used by autoloader type "Namespace Map".
   * 
   * Walks through all paths from the namespace map and searchs for the target class or file.
   * Will use include() to load files as it offers the best performance.
   * Supports autoload for files with file extension: '.php'
   * 
   * @param string $class FQN of target class or file.
   *
   * @return bool True on success, false on failure.
   */
  public static function loader_namespaces($class) {
    foreach ( self::$namespaces as $dir ) {
      $pre = $dir . DIRECTORY_SEPARATOR . basename($class);
      if ( file_exists($file = $pre . '.php' ) ) {
        include $file;
        return true;
      }
      if ( file_exists($file = $pre . '.class.php' ) ) {
        include $file;
        return true;
      }
    }
    return false;
  }
  
  
  /**
   * @deprecated
   *             
   * Autoload function used by autoloader type "PSR-4 convention".
   *
   * Generates the excepted path of the target class referring to the PSR-4 conventions.
   * Will use include() to load files as it offers the best performance.
   * Supports autoload for files with file extension: '.php', '.class.php' and '.php.inc'
   *
   * @param string $class FQN of target class or file.
   *
   * @return bool True on success, false on failure.
   */
  public static function loader_psr4($class) {
    // remove the base namespace since its most likely virtual if framework was is not installed as composer package 
    $class = str_replace('Stuck1A\\StuckMVC\\', '', $class);
    $pre = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    
    if ( file_exists($file = $pre . '.php') ) {
      include $file;
      return true;
    }
    if ( file_exists($file = $pre . '.class.php') ) {
      include $file;
      return true;
    }
    if ( file_exists($file = $pre . '.php.inc') ) {
      include $file;
      return true;
    }
    return false;
  }
  
  
  /**
   * Clears current autoloader queue.
   *
   * Removes any SPL autoload function from the queue, including PHP default and third party autoloader, disabling
   * autoload feature if no other autoloader function will be registered afterwards.
   */
  private static function autoloader_unregister_all() {
    $registered = spl_autoload_functions();
    if ( !$registered ) {
      return;
    }
    foreach ( $registered as $autoloader ) {
      spl_autoload_unregister($autoloader);
    }
  }
  
  
  /**
   * Returns current class map. Mainly for debugging purposes.
   * @return array
   */
  public static function getClasses() {
    return self::$classes;
  }
  
}
