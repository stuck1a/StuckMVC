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
 * Base class for any type of controller
 * @package StuckMVC
 */
abstract class Controller {
  /**
   * Stores a reference to the Config instance in any controller
   * @var \Stuck1A\StuckMVC\Core\Config
   */
  protected $oConf;
  
  /**
   * Stores a reference to the Smarty instance in any controller
   * @var \Smarty
   */
  protected $renderer;
}
