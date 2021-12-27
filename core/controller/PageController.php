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
 * Parent for all page controllers. Those are controllers which are linked to a specific content page and provides
 * functionality which is specific for this content page. Any request to a content page which has an own page controller
 * will dispatch its page controller to start rendering except of this core page controller which will be used instead 
 * for any content page without own page controllers.
 *
 * @package StuckMVC
 */
class PageController extends \Stuck1A\StuckMVC\Core\Controller\FrontendController {
  /**
   * The identifier of the content page to which the page controller is responsible for. Must be offered by each
   * child, otherwise the autogeneration of the controller map becomes impossible.
   * Its allowed to add more than one entry to cover special case like variations of the SEO URL. Even if this will
   * allow to associate one than one backend section, as well, it's highly discouraged to do this, since it will break
   * with any best practises and unexpected behaviour should be excepted.
   *
   * @var array
   */
  protected static $mapping;
  
  
  /**
   * PageController constructor
   */
  public function __construct(string $sTemplate, array $aParams = null) {
    parent::__construct();
    
    // render requested template
    $this->renderer->display($sTemplate);
  }
  
  
  /**
   * Get list of content page identifiers to which this page controller is responsible.
   * Any requested SEO URL within this content page will become linked to this controller.
   * Use multiple entries only, if there are multiple SEO URLS for the same location.
   * This may usually occur, if there are different typo variations and one add routes
   * for all possible spellings to be more tolerant for URLs a user typed in manually.
   * Or in situations where a existing content page is renamed and its old URL shall
   * stay accessible for bookmarks etc.
   * @return array
   */
  public static function getMapping(): array {
    return self::$mapping;
  }
  
}
