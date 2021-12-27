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


/*
 * Smarty plugin
 * ----------------------------------------------------------------------------------
 * File:     function.getThemeName.php
 * Type:     function
 * Name:     getThemeName
 * Purpose:  returns the internal name of the active theme
 * ----------------------------------------------------------------------------------
 */
function smarty_function_getThemeName(array $params, \Smarty_Internal_Template $template): string {
  return \Stuck1A\StuckMVC\Core\Config::getInstance()->getActiveTheme();
}
