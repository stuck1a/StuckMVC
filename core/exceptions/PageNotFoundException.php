<?php
/*
 * This file is part of StuckMVC <https://stuck1a.de/coding/stuckmvc>,
 * Copyright (c) 2021.
 * StuckMVC framework is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License version 3 as published by
 * the Free Software Foundation.
 * 
 * It is distributed in the hope that it will be useful, but without any warranty;
 * without even the implied warranty of merchantability of fitness for a
 * particular purpose. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * StuckMVC. If not, see <https://www.gnu.org/licenses/>. 
 *
 * FOR A SOMEWHAT FREER WORLD.
 */

namespace Stuck1A\StuckMVC\Core\Exception;


/**
 * Will be thrown if requesting a non existent content page, e.g. from invalid seo links. 
 */
class PageNotFoundException extends GenericException {
  /**
   * PageNotFoundException constructor.
   * @param string $message message of the exception
   */
  function __construct($message = "PageNotFoundException") {
    parent::__construct($message);
  }
  
}
