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

namespace Stuck1A\StuckMVC\Application\Controller;


/**
 * Page controller for the error content page.
 *
 * @package StuckMVC
 */
class ErrorController extends \Stuck1A\StuckMVC\Core\Controller\PageController {
  /**
   * Identifier of the content page for which this page controller is responsible
   *
   * @var array
   */
  protected static $mapping = [ 'error' ];
  
  
  /**
   * Processes submitted params and renders template
   *
   * @param string $sTemplate
   * @param ?array $aParams
   */
  public function __construct(string $sTemplate, array $aParams = null) {
    // specific logic regarding to associated content page here (e.g. change/alter template depending on params)
    // ...
  
    // parent will render the templates
    parent::__construct($sTemplate, $aParams);
  }
  
}
