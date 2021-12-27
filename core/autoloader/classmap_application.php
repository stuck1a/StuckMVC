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

namespace Stuck1A\StuckMVC\Core\Autoloader;


return [
  // Application\Controller (Pages)
  'Stuck1A\\StuckMVC\\Application\\Controller\\ErrorController'            => 'application/controller/ErrorController.php',
  'Stuck1A\\StuckMVC\\Application\\Controller\\HomeController'             => 'application/controller/HomeController.php',
  'Stuck1A\\StuckMVC\\Application\\Controller\\MusikController'            => 'application/controller/MusikController.php',
  // Application\Controller (Admin)
  'Stuck1A\\StuckMVC\\Application\\Controller\\Admin\\DashboardController' => 'application/controller/admin/DashboardController.php',
  'Stuck1A\\StuckMVC\\Application\\Controller\\Admin\\LoginController'     => 'application/controller/admin/LoginController.php',
  // Application\Model
  'Stuck1A\\StuckMVC\\Application\\Model\\User'                            => 'application/models/User.php',
  // Application\Widget
  'Stuck1A\\StuckMVC\\Application\\Widget\\LoginWidget'                    => 'application/widgets/login/LoginWidget.php',
  'Stuck1A\\StuckMVC\\Application\\Widget\\BreadcrumbWidget'               => 'application/widgets/breadcrumb/BreadcrumbWidget.php',
];
