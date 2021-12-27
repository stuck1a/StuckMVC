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

//namespace Stuck1a\StuckMVC\Core\Plugin;

use Stuck1A\StuckMVC\Core\Mapper;
use Stuck1A\StuckMVC\Core\Logger;
use Stuck1A\StuckMVC\Core\Dispatcher;

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     compiler.include_widget.php
 * Type:     compiler (only executed once at compile time)
 * Name:     include_widget
 * Purpose:  Searches the widget register for an entry matching
 *           the 'name' param and invokes it with the params
 *           submitted. This WidgetController will return a
 *           template path accordingly which is used to assemble
 *           a common {include} tag to this path, which will
 *           replace the origin call in the source template.  
 * -------------------------------------------------------------
 */

/**
 * Includes a registered widget.
 * Attribute 'name' must specify the widgets internal name.
 * Any further attribute will be passed as parameter to the getTemplate() function of the widgets controller.
 * 
 * @param array  $params Precompiled parameters from call as strings
 * @param Smarty $smarty Reference to the smarty instance
 *
 * @return string  The resulting include call for the widgets template
 */
function smarty_compiler_include_widget($params, $smarty) {
  // fix Smarty excessive quotes bug // TODO: WTF? Ist Smarty da echt so krass buggy?? Dringend reporten!
  array_walk($params,function(&$x) { $x = trim($x, '\'\"'); });
  // search and invoke widget
  $name = $params['name'];
  if ( !empty($name) ) {
    $widget = Mapper::getInstance()->getWidget($name);
    unset($params['name']);
    $path = Dispatcher::invokeWidget($widget)->getTemplate($params);
    // render received widget template
    try {
      return $smarty->fetch($path);
    } catch ( SmartyException | Exception $ex ) {
      // invalid template
      $msg = 'Error while parsing template (' . $ex->getLine() . ')' . $path . '. Please check its source code for syntax errors.';
      Logger::send('[SmartyException] ' . $msg . ' Stack Trace: ' . $ex->getTraceAsString());
      return '';
    }
  }
  // invalid name
  $msg = "Requested unknown widget '{$name}'. This may be either a typo or the widget does not inherit the cores abstract WidgetController, so that it is not recognized as a widget.";
  Logger::send($msg);
  return '';
}
