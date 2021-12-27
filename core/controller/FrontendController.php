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

namespace Stuck1A\StuckMVC\Core\Controller;


/**
 * Parent for all rendering related controllers within the frontend.
 * Specifically, this includes all widget and page controllers of the application, but only the last one can be linked
 * to whole content pages while widget controllers are responsible to render the widgets templates at the location its
 * included in any template. A widget literally is a modular component which uses its own private MVC structure.
 *
 * @package StuckMVC
 */
class FrontendController extends \Stuck1A\StuckMVC\Core\Controller {
  /**
   * Set up mandatory properties
   */
  function __construct() {
    // store the references to the Config and Smarty object for shorthand access
    $this->oConf = \Stuck1A\StuckMVC\Core\Config::getInstance();
    $this->renderer = \Stuck1A\StuckMVC\Core\RendererFacade::getInstance()->getSmarty();
  }
}
