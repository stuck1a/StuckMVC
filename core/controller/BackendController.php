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
 * Parent for all controllers which will be linked to a specific content page of the backend.
 * Can be understood as the "Backend's PageController" type. All Controllers which shall be linked to
 * backend sections must extend it.
 *
 * @package StuckMVC
 */
class BackendController extends \Stuck1A\StuckMVC\Core\Controller {
  /**
   * The identifier of the backend page to which the backend controller is responsible for. Must be offered by each
   * child, otherwise the autogeneration of the controller map becomes impossible.
   * Its allowed to add more than one entry to cover special case like variations of the SEO URL. Even if this will
   * allow to associate one than one backend section, as well, it's highly discouraged to do this, since it will break
   * with any best practises and unexpected behaviour should be excepted
   *
   * @var array
   */
  protected static $mapping;
  
  
  /**
   * Get list of SEO URL steps to which this backend controller is responsible.
   * Also can be a partly SEO URL. Any requested SEO URL which lays within this mapping will then be linked to this
   * controller. Usually the list only holds a single entry which leads to a backend section dir, so the controller
   * will be responsible for any SEO URLs which will request templates within this backend section dir.
   * @return array
   */
  public static function getMapping(): array {
    return self::$mapping;
  }
  
  /**
   * Only allows rendering of the requested backend content page if a user with admin rights is logged in.
   * Otherwise the requested SEO URL is changed to the backend content page "login".
   */
  function __construct(string $sTemplate = null, array $aParams = null) {
    // store the references to the Config and Smarty object for shorthand access
    $this->oConf = \Stuck1A\StuckMVC\Core\Config::getInstance();
    $this->renderer = \Stuck1A\StuckMVC\Core\RendererFacade::getInstance()->getSmarty();
  
    // render requested template
    $this->renderer->display($sTemplate);
  }
  
}
