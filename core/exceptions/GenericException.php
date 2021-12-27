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

use Exception;
use Throwable;


/**
 * Basic exception class of the framework.
 */
class GenericException extends Exception implements Throwable{
  /**
   * GenericException constructor. Extend regular exception class by parameter $data which can be used to offer any
   * relevant data for the exception, e.g. data to build a log message.
   * @param string         $message  message of the exception
   * @param int            $code     exception code
   * @param Throwable|null $previous previous thrown exception
   */
  function __construct($message = "GenericException", $code = 0, Throwable $previous = null) {
    parent::__construct($message, $code, $previous);
  }
  
}
