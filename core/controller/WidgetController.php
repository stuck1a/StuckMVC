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
 * Core parent for any WidgetController. WidgetController are the entry point for widget rendering.
 *
 * @package StuckMVC
 */
abstract class WidgetController extends \Stuck1A\StuckMVC\Core\Controller {
  /**
   * Internal name of the concrete widget. Used as name for the assigned accessor variable in templates. 
   * @var string
   */
  protected $name;
  
  /**
   * Set up mandatory controller properties and make the WidgetController
   */
  function __construct() {
    // store the references to the Config and Smarty object for shorthand access
    $this->oConf = \Stuck1A\StuckMVC\Core\Config::getInstance();
    $this->renderer = \Stuck1A\StuckMVC\Core\RendererFacade::getInstance()->getSmarty();
    // enable access from templates (the widgets dir name is used as the widgets internal name)

    $this->renderer->assign($this->name, $this);
  }
  
  
  /**
   * Called when including a concrete widget in a template.
   *
   * @param array ...$params  Parameterlist from the include_widget call
   *
   * @return string  The resulting template path after processing the params
   */
  public abstract function getTemplate(...$params);
  
}
