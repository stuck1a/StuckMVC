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
 * Abstract class Singleton
 * 
 * Transforms any extending class into a singleton.
 * This will ensure that only ONE SINGLE instance of the class will ever be used, which can be restored at any time.
 * This is done by providing a static wrapper for any child constructor which stores the created object as a static
 * member and therefore which therefore will be kept in memory. Further getter calls will return its reference only,
 * instead of creating a new object.<br>
 * Make sure to prevent direct calls to singleton constructors and always use <code>get()</code> instead.<br><br>
 *
 * <b>Note:</b><br>
 * The singleton pattern has some disadvantages (like complex test cases and easy messing around), but when used properly
 * it's perfectly suited for small classes which are required in plenty different locations, e.g. resource facades,
 * config classes, loggers, etc.<br>
 * To adapt the singleton pattern to your class, just let it extend this one.
 * 
 * @package StuckMVC
 */
abstract class Singleton {
  /**
   * Stores all active singleton objects. Only one instance per type is possible
   * @var array
   */
  private static $storage = [];
  
  
  
  /**
   * If a concrete singleton does not need a constructor, it can use this default one.
   */
   protected function __construct() {}
  
  
  /**
   * General getter for defined singletons<br><br>
   * 
   * If no instance was created yet, calls its constructor once, add it to the instance storage and then return it
   *
   */
  public static function getInstance() {
    // get requested class FQN
    $child = static::class;
    // check storage for already existing instance
    if ( !isset(self::$storage[$child]) ) {
      // create and store a new instance, if none stored yet
      self::$storage[$child] = new static();
    }
    // return the instance
    return self::$storage[$child];
  }
  
  
  /**
   * Prevents cloning as it would interfere with the singleton pattern
   */
  final protected function __clone() {
    $msg = 'Tried to clone singleton class \'' . __CLASS__ . '\' which is forbidden. Instead you may use get() or ' .
           'assign operators to duplicate at least the instance REFERENCE. If you really need to re-instantiate it, ' .
           'you may destroy the current one by calling free() right before using get(). Doing this, however, ' .
           'undermines logic of the singleton pattern. Therefore, you should really ONLY do this when there really ' .
           'isn\'t a more suitable option like using another creational pattern for your class.';
    Logger::send($msg, 'warning');
  }
  
  
  /**
   * Prevents unserialization as it would interfere with the singleton pattern
   */
  final protected function __wakeup() {
    $msg = 'Tried to unserialize singleton class \'' . __CLASS__ . '\' - which is forbidden. By default, any singleton ' .
           'instance will be stored in RAM and therefore no manual (un)serialization is required. Instead use the ' .
           'universal instance getter function \'' . __CLASS__ . '::get()\' every singleton inherits to access data' .
           'which were stored as a singleton class.';
    Logger::send($msg, 'warning');
  }
  
}
