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


class Facts extends Singleton {
  /**
   * Global Namespace map which links pure class names to their used namespace
   * @var array
   */
  private $namespaces;
  
  
  
  /**
   * Reads and process all facts.
   */
  protected function __construct() {
    parent::__construct();
    $this->namespaces = (require 'facts/namespaces_core.php') + (require 'facts/namespaces_application.php');
  }
  
  
  /**
   * Returns the full qualified namespace (FQN) of any known class
   *
   * @param  ?string $class  [optional]
   *                         Class name from which the FQN is required or do not set to receive the full register.
   *
   * @return array|string  FQN of the requested class or empty string no namespace is registered for the requested class
   *                       or an array with all registered classes and their namespaces if no or null value is set for
   *                       parameter $class.
   */
  public function getNamespace(string $class = null) {
    if ( !isset($class) ) {
      return $this->namespaces;
    }
    if ( array_key_exists($class, $this->namespaces) ) {
      return $this->namespaces[$class];
    }
    return '';
  }
  
}
