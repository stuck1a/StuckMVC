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

use Exception;
use Stuck1A\StuckMVC\Core\Exception\ControllerNotFoundException;


/**
 * Internal POST-Request handler.
 * Gets invokes whenever an user request contains POST-Data.
 * The core is managing internal requests from form POST actions,
 * where it excepts the mandatory routing data fields 'cl' and 'fnc' which tells the handler the requests target 
 * controller and function to process the POST data.
 * After processing the handler will adjust the routing data regarding to the request target page.
 * While the target is mostly the origin referer, there are exceptions like after successful login to the backend,
 * where the referer is the backend login page and the target is the backend landing page.
 * The framework excepts the target page to be stored as data field 'dest' and the origin as 'ref'
 * 
 * Mandatory fields:  
 * 'cl'   => target controller (class name)                                                                        
 * 'fnc'  => target function within the target controller (static and non-static allowed)                          
 * 'dest' => target page to load after processing the request (may be altered by the target function)              
 * --------------------                                                                                            
 * 'ref'  => source page which triggered the request (mandatory but autogenerated for internal requests)
 * 
 * @package StuckMVC
 */
class RequestHandler {
  /**
   * The currently used request data
   * @var array
   */
  private $requestData;
  
  
  
  /**
   * Creates a new handler object that allows access to functions to evaluate request data.
   * Any array can be used as the source for the request data, as long as it contains all mandatory fields
   *
   * @param ?array  $requestData  [optional]<br>
   *                              Utilizable request data. By default $_REQUEST is used.
   */ 
  public function __construct($requestData = null) {
    $this->setRequestData($requestData);
  }
  
  
  /**
   * Utilizes the stored request data.
   * 
   * Processing steps:
   *   - Find the target controller class
   *   - Get function context (static/object)
   *   - Invoke the target function considering its context TODO So machen?
   *   - The request data is passed as param list (except 'cl' and 'fnc')
   *   - < execution of cl::fnc($requestData) >  (includes altering of 'dest', if necessary)
   *   - Adjust routing data regarding to 'dest' considering the seo routes
   *
   * @return bool  True, if the request processed successfully, false otherwise.
   *               
   * @throws ControllerNotFoundException  If the target controller is not found
   */
  public function process() {
    // prepare
    $data = $this->getRequestData();
    $cl = $data['cl'];
    $fnc = $data['fnc'];
    //$dest = $data['dest'];
    //$ref = $data['ref'] ?? 'LAST VISITED PAGE (MUST BE INTRODUCED!)';
    
    // add namespace if one is found
    if ( !empty($fqn = Facts::getInstance()->getNamespace($cl)) ) {
      $cl = "\\{$fqn}\\{$cl}";
    }
   
    
    // process
    if  ( class_exists($cl) ) {
      if ( method_exists($cl, $fnc) ) {
        unset($data['cl'], $data['fnc']);
        try {
          // try to invoke static context
          $cl::$fnc($data);
        } catch ( Exception $ex ) {
          try {
            // try to invoke in object context
            (new $cl())->$fnc($data);
          } catch ( Exception $ex ) {
            try {
              // try to invoke in function context
              call_user_func_array($fnc, $data);
            } catch ( Exception $ex ) {
              // give up
              Logger::send('[RequestHandler] Target function is not callable!');
              return false;
            }
          }
        }
        // success
        // TODO adjust routing data 
        return true;
      }
      Logger::send('[RequestHandler] Cannot find target function!');
      return false;
    }
    Logger::send('[RequestHandler] Cannot find target class!');
    return false;
  }
  
  
  
  /**
   * Checks whether the received data contains all mandatory fields to be able to process the request.
   * The setter <code>setRequestData()</code> uses this function to validate any data before storing it,
   * but it can be called directly as well, to only validate any request data.
   *
   * Note:
   * This will only check for the existence of the fields 'cl', 'fnc' and 'dest' but does not validate its content.
   * The content validation takes place while processing.
   * 
   * @param array $requestData  The data to validate
   * @param bool  $logFailure   [optional]<br>
   *                            Whether validation failures shall be logged or not. Default is true.
   *                             
   * @returns bool  True if all mandatory fields exists, false otherwise.
   */
  public static function validateInput($requestData, $logFailure = true) {
    if ( isset($requestData['cl'], $requestData['fnc']) ) {
      return true;
    }
    // validation failed
    if ( $logFailure ) {
      $msg = 'RequestHandler failed to validate received data. Missing one or more mandatory fields (cl, fnc, dest).';
      Logger::send($msg);
    }
    return false;
  }
  
  
  /**
   * Gets the request data, the handler will use for processing.
   * 
   * @return array  Currently stored request data of this handler object.<br>
   *                If no data is yet stored, an empty array is returned instead.
   */
  public function getRequestData() {
    return $this->requestData ?? [];
  }
  
  
  /**
   * Sets the request data, the handler will use for processing.
   * 
   * @param null|array $requestData  Utilizable request data. By default, the content of $_REQUEST is used.
   *                                 
   * @return bool  True, if successfully stored or false if input validation failed.
   */
  public function setRequestData($requestData = null) {
    // if no data received, use $_REQUEST as default
    if ( !isset($requestData) ) {
      $requestData = $_REQUEST ?? [];
    }
    // only write the data if it contains the mandatory fields
    if ( $this->validateInput($requestData) ) {
      $this->requestData = $requestData;
      return true;
    }
    return false;
  }
  
  
  /**
   * Analyzes any callable object, array or string and returns the its type, regarding to following table:
   *
   *  Received callable form          | Normalization                   | Type returned
   * ---------------------------------+---------------------------------+--------------
   *  function (...) use (...) {...}  | function (...) use (...) {...}  | 'closure'
   *  $object                         | $object                         | 'invocable'
   *  "function"                      | "function"                      | 'function'
   *  "class::method"                 | ["class", "method"]             | 'static'
   *  ["class", "parent::method"]     | ["parent of class", "method"]   | 'static'
   *  ["class", "self::method"]       | ["class", "method"]             | 'static'
   *  ["class", "method"]             | ["class", "method"]             | 'static'
   *  [$object, "parent::method"]     | [$object, "parent::method"]     | 'object'
   *  [$object, "self::method"]       | [$object, "method"]             | 'object'
   *  [$object, "method"]             | [$object, "method"]             | 'object'
   * ---------------------------------+---------------------------------+--------------
   *  other callable                  | idem                            | 'unknown'
   * ---------------------------------+---------------------------------+--------------
   *  not a callable                  | null                            | false
   *
   * If the "strict" parameter is set to true, additional checks are performed, in particular:
   *  - when a callable string of the form "class::method" or a callable array
   *    of the form ["class", "method"] is given, the method must be a static one,
   *  - when a callable array of the form [$object, "method"] is given, the
   *    method must be a non-static one.
   *
   */
  function callableType($callable, $strict = true, callable& $norm = null) {
    if ( !is_callable($callable) ) {
      switch ( true ) {
        // process objects
        case is_object($callable):
          $norm = $callable;
          return 'Closure' === get_class($callable) ? 'closure' : 'invocable';
        // process strings
        case is_string($callable):
          $m = null;
          if ( preg_match('~^(?<class>[a-z_][a-z0-9_]*)::(?<method>[a-z_][a-z0-9_]*)$~i', $callable, $m) ) {
            list($left, $right) = [$m['class'], $m['method']];
            if ( !$strict || (new \ReflectionMethod($left, $right))->isStatic() ) {
              $norm = [$left, $right];
              return 'static';
            }
          } else {
            $norm = $callable;
            return 'function';
          }
          break;
        // process arrays 
        case is_array($callable):
          $m = null;
          if ( preg_match('~^(:?(?<reference>self|parent)::)?(?<method>[a-z_][a-z0-9_]*)$~i', $callable[1], $m) ) {
            if ( is_string($callable[0]) ) {
              if ( 'parent' === strtolower($m['reference']) ) {
                list($left, $right) = [get_parent_class($callable[0]), $m['method']];
              } else {
                list($left, $right) = [$callable[0], $m['method']];
              }
              if ( !$strict || (new \ReflectionMethod($left, $right))->isStatic() ) {
                $norm = [$left, $right];
                return 'static';
              }
            } else {
              if ( 'self' === strtolower($m['reference']) ) {
                list($left, $right) = [$callable[0], $m['method']];
              } else {
                list($left, $right) = $callable;
              }
              if ( !$strict || !(new \ReflectionMethod($left, $right))->isStatic() ) {
                $norm = [$left, $right];
                return 'object';
              }
            }
          }
          break;
      }
      $norm = $callable;
      return 'unknown';
    }
    $norm = null;
    return false;
  }
  
}
