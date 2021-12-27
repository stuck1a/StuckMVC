<?php
/*
 * This file is part of StucksSeiten <https://stuck1a.de>,
 * Copyright (c) 2021.
 * StucksSeiten uses the StuckMVC framework, which is free software: you can
 * redistribute it and/or modify it under the terms of the GNU General Public
 * License version 3 as published by the Free Software Foundation.
 *
 * StucksSeiten is the official website of the StuckMVC framework and further
 * designed as an usage example. It is distributed in the hope that it will be useful,
 * but without any warranty; without even the implied warranty of merchantability
 * of fitness for a particular purpose. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * StucksSeiten. If not, see <https://www.gnu.org/licenses/>. 
 *
 * FOR A SOMEWHAT FREER WORLD.
 */

namespace Stuck1A\StuckMVC\Application\Widget;


class BreadcrumbWidget extends \Stuck1A\StuckMVC\Core\Controller\WidgetController {
  /**
   * Widgets main template file, which will returned to the renderer on call
   * @var string
   */
  private const template = 'views/breadcrumb.tpl';
  
  
  /**
   * BreadcrumbWidget constructor.
   */
  public function __construct() {
    parent::__construct();
  }
  
  
  /**
   * Called when including the widget in a template.
   *
   * @param array ...$params  Parameterlist from the include_widget call
   *
   * @return string  The resulting template path after processing the params
   */
  public function getTemplate(...$params) {
    return  __DIR__ . DIRECTORY_SEPARATOR . self::template;
  }
  
}
