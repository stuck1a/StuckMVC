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
  // Packages
  'Smarty' => 'vendor/smarty/smarty/libs/Smarty.class.php',
  'SmartyBC' => 'vendor/smarty/smarty/libs/SmartyBC.class.php',
  'Composer\InstalledVersions' => 'vendor/composer/InstalledVersions.php',
  // Psr\SimpleCache
  'Psr\\SimpleCache\\CacheInterface' => 'core/psr/CacheInterface.php',
  'Psr\\SimpleCache\\CacheException' => 'core/psr/CacheException.php',
  'Psr\\SimpleCache\\InvalidArgumentException' => 'core/psr/InvalidArgumentException.php',
  // Core
  'Stuck1A\\StuckMVC\\Core\\Application' => 'core/Application.php',
  'Stuck1A\\StuckMVC\\Core\\Base' => 'core/Base.php',
  'Stuck1A\\StuckMVC\\Core\\CacheAdapter' => 'core/CacheAdapter.php',
  'Stuck1A\\StuckMVC\\Core\\Config' => 'core/Config.php',
  'Stuck1A\\StuckMVC\\Core\\Controller' => 'core/Controller.php',
  'Stuck1A\\StuckMVC\\Core\\DatabaseAdapter' => 'core/DatabaseAdapter.php',
  'Stuck1A\\StuckMVC\\Core\\Dispatcher' => 'core/Dispatcher.php',
  'Stuck1A\\StuckMVC\\Core\\FileSystemAdapter' => 'core/FileSystemAdapter.php',
  'Stuck1A\\StuckMVC\\Core\\Logger' => 'core/Logger.php',
  'Stuck1A\\StuckMVC\\Core\\Mapper' => 'core/Mapper.php',
  'Stuck1A\\StuckMVC\\Core\\Model' => 'core/Model.php',
  'Stuck1A\\StuckMVC\\Core\\Parser' => 'core/Parser.php',
  'Stuck1A\\StuckMVC\\Core\\Profiler' => 'core/Profiler.php',
  'Stuck1A\\StuckMVC\\Core\\RendererFacade' => 'core/RendererFacade.php',
  'Stuck1A\\StuckMVC\\Core\\RequestHandler' => 'core/RequestHandler.php',
  'Stuck1A\\StuckMVC\\Core\\Router' => 'core/Router.php',
  'Stuck1A\\StuckMVC\\Core\\Singleton' => 'core/Singleton.php',
  'Stuck1A\\StuckMVC\\Core\\Facts' => 'core/Facts.php',
  // Core\Controller
  'Stuck1A\\StuckMVC\\Core\\Controller\\BackendController' => 'core/controller/BackendController.php',
  'Stuck1A\\StuckMVC\\Core\\Controller\\FrontendController' => 'core/controller/FrontendController.php',
  'Stuck1A\\StuckMVC\\Core\\Controller\\PageController' => 'core/controller/PageController.php',
  'Stuck1A\\StuckMVC\\Core\\Controller\\WidgetController' => 'core/controller/WidgetController.php',
  // Core\Types
  'Stuck1A\\StuckMVC\\Core\\Types\\Query' => 'core/types/Query.php',
  'Stuck1A\\StuckMVC\\Core\\Types\\SelectQuery' => 'core/types/SelectQuery.php',
  'Stuck1A\\StuckMVC\\Core\\Types\\InsertQuery' => 'core/types/InsertQuery.php',
  // Core\Exception
  'Stuck1A\\StuckMVC\\Core\\Exception\\ControllerNotFoundException' => 'core/exceptions/ControllerNotFoundException.php',
  'Stuck1A\\StuckMVC\\Core\\Exception\\GenericException' => 'core/exceptions/GenericException.php',
  'Stuck1A\\StuckMVC\\Core\\Exception\\MissingCoreFileException' => 'core/exceptions/MissingCoreFileException.php',
  'Stuck1A\\StuckMVC\\Core\\Exception\\ModelNotFoundException' => 'core/exceptions/ModelNotFoundException.php',
  'Stuck1A\\StuckMVC\\Core\\Exception\\PageNotFoundException' => 'core/exceptions/PageNotFoundException.php',
  'Stuck1A\\StuckMVC\\Core\\Exception\\RoutingException' => 'core/exceptions/RoutingException.php',
  'Stuck1A\\StuckMVC\\Core\\Exception\\TemplateNotFoundException' => 'core/exceptions/TemplateNotFoundException.php',
];
