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
 * File:     function.getDownloadsPath.php
 * Type:     function
 * Name:     getDownloadsPath
 * Purpose:  returns the absolute path to the registered directory for downloadable files
 * ----------------------------------------------------------------------------------
 */
function smarty_function_getDownloadsPath(array $params, Smarty_Internal_Template $template): string {
  // fetch document root path from server variables
  $result = ($_SERVER['REQUEST_SCHEME']?:'https') . '://' . $_SERVER['SERVER_NAME'];
  // add path to the downloads directory plus trailing slash fot better usability of plugin
  return $result . \Stuck1A\StuckMVC\Core\Mapper::getInstance()->getDir('downloads', 1) . '/';
}
