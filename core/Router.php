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
 * Scans request strings and utilizes the mapped routing data from it
 *
 * @package StuckMVC
 */
class Router {
  /**
   * Extracts the SEO URL part from a request string and returns any matching routing data from relevant maps 
   * 
   * @param string $sRequest Request string including absolute or relative URL and Query String, if submitted.
   * 
   * @return array Gathered routing data as array containing following three entries<br>
   *               'controller' => ˂FQN of the responsible page or backend controller as string˃<br>
   *               'template' => ˂Path to the template the requested SEO URL is linked to˃<br>
   *               'params' => ˂Array of received query string parameters in the format ˂'name'˃ => ˂'value'˃˃
   */
  public static function utilizeRequest(string $sRequest): array {
    $oMapper = Mapper::getInstance();
    // remove host part and document root path from requested URL
    $sRequest = Base::str_replace_first($oMapper->getDir('ROOT'), '', $sRequest);
    // autocorrection of minor errors like double slashes
    $sRequest = filter_var($sRequest, FILTER_SANITIZE_URL);
    // split remaining request in route and query
    if ( Base::str_contains($sRequest, '?') ) {
      // query params submitted
      [$sRoute, $sQuery] = explode('?', $sRequest);
    } else {
      // no query params submitted
      $sRoute = $sRequest;
    }
    // allows requests ending with a slash as well
    $sRoute = rtrim($sRoute, '/');
    // split query into single params and store them
    $aParams = [];
    if ( isset($sQuery) ) {
      foreach ( explode('&', $sQuery) as $entry ) {
        [$sKey, $sVal] = explode('=', $entry);
        $aParams[$sKey] = $sVal;
      }
    }
    // lookup template for requested SEO route
    $sTPL = $oMapper->getTemplateFromSEO($sRoute);
    // alter route to the 404 page if the request matches no route
    if ( !isset($sTPL) || !is_file($sTPL) ) {
      // TODO: Irgendwie error/404 dynamisch erkennen... Aber wie? Statische Routes für die error pages?
      $sRoute = 'error/404';
      $sTPL = $oMapper->getTemplateFromSEO($sRoute);
    }
    // lookup responsible controller for the content page the template belongs to
    $sContentPage = explode('/', $sRoute)[0];
    $sController = $oMapper->getController($sContentPage);
    // delegate to core controller if no controller is mapped to that content page
    if ( !isset($sController) ) {
      // TODO: Unterscheidung Page-/BackendController! Allg. richtig nice wäre eine Funktion $oMapper->isBackendPage($sRoute)
      $sController = '\\Stuck1A\\StuckMVC\\Core\\Controller\\PageController';
    }
    // return gathered routing data
    return ['controller' => $sController, 'template' => $sTPL, 'params' => $aParams];
  }
}
