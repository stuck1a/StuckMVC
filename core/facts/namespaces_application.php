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

//namespace Stuck1A\StuckMVC\Core\Facts;


return [
  // Application\Controller (Pages)
  'ErrorController' => 'Stuck1A\\StuckMVC\\Application\\Controller',
  'HomeController' => 'Stuck1A\\StuckMVC\\Application\\Controller',
  'MusikController' => 'Stuck1A\\StuckMVC\\Application\\Controller',
  // Application\Controller (Admin)
  'DashboardController' => 'Stuck1A\\StuckMVC\\Application\\Controller\\Admin',
  'LoginController' => 'Stuck1A\\StuckMVC\\Application\\Controller\\Admin',
  // Application\Model
  'User' => 'Stuck1A\\StuckMVC\\Application\\Model',
  // Application\Widget
  'LoginWidget' => 'Stuck1A\\StuckMVC\\Application\\Widget',
  'BreadcrumbWidget' => 'Stuck1A\\StuckMVC\\Application\\Widget',
];
