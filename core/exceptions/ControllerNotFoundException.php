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


use Stuck1A\StuckMVC\Core\Logger;


/**
 * Will be thrown if the framework tries to dispatch an controller, which class file can't be found.
 * This happens e.g. when trying to open an none existent content page (like from invalid seo links).
 */
class ControllerNotFoundException extends GenericException {
  /**
   * ControllerNotFoundException constructor.
   * @param string $message message of the exception
   */
  function __construct($message = "ControllerNotFoundException") {
    parent::__construct($message);
 
    // since this will be thrown whenever an user tries to call a unknown link, log only at debug level
    Logger::send($message, 'debug');
  }
  
}
