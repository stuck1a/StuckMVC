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


use Stuck1A\StuckMVC\Core\Controller\WidgetController;


/**
 * Central dispatcher unit. Is able to dispatch any application-specific class like controller, models and widgets.
 *
 * @package StuckMVC
 */
class Dispatcher {
  /**
   * Invokes a page or backend controller
   *
   * @param  string $sController FQN to the target controller
   * @param  string $sTemplate   Path to the template the controller shall attempt rendering
   * @param  ?array $aParams     Additional variables from query string for the controller
   */
  static function invokeController(string $sController, string $sTemplate, array $aParams = null) {
    new $sController($sTemplate, $aParams);
  }
  
  
  /**
   * Invokes a model
   * 
   * @param string $model name of the target model class (full qualified namespace)
 */
  static function invokeModel(string $model) {
    new $model();
  }
  
  
  /**
   * Invokes a widget (controller)
   *
   * @param string $widget Name of the target widget class (full qualified namespace)
   *                       
   * @return WidgetController  The invoked widget controller class
   */
  static function invokeWidget(string $widget) {
    return new $widget();
  }
  
}
