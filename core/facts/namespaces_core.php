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
  // Psr\SimpleCache
  'CacheInterface' => 'Psr\\SimpleCache',
  'CacheException' => 'Psr\\SimpleCache',
  'InvalidArgumentException' => 'Psr\\SimpleCache',
  // Core
  'Application' => 'Stuck1A\\StuckMVC\\Core',
  'Base' => 'Stuck1A\\StuckMVC\\Core',
  'CacheAdapter' => 'Stuck1A\\StuckMVC\\Core',
  'Config' => 'Stuck1A\\StuckMVC\\Core',
  'Controller' => 'Stuck1A\\StuckMVC\\Core',
  'DatabaseAdapter' => 'Stuck1A\\StuckMVC\\Core',
  'Dispatcher' => 'Stuck1A\\StuckMVC\\Core',
  'FileSystemAdapter' => 'Stuck1A\\StuckMVC\\Core',
  'Logger' => 'Stuck1A\\StuckMVC\\Core',
  'Mapper' => 'Stuck1A\\StuckMVC\\Core',
  'Model' => 'Stuck1A\\StuckMVC\\Core',
  'Parser' => 'Stuck1A\\StuckMVC\\Core',
  'Profiler' => 'Stuck1A\\StuckMVC\\Core',
  'RendererFacade' => 'Stuck1A\\StuckMVC\\Core',
  'RequestHandler' => 'Stuck1A\\StuckMVC\\Core',
  'Router' => 'Stuck1A\\StuckMVC\\Core',
  'Singleton' => 'Stuck1A\\StuckMVC\\Core',
  'Facts' => 'Stuck1A\\StuckMVC\\Core',
  // Core\Controller
  'BackendController' => 'Stuck1A\\StuckMVC\\Core\\Controller',
  'FrontendController' => 'Stuck1A\\StuckMVC\\Core\\Controller',
  'PageController' => 'Stuck1A\\StuckMVC\\Core\\Controller',
  'WidgetController' => 'Stuck1A\\StuckMVC\\Core\\Controller',
  // Core\Exception
  'ControllerNotFoundException' => 'Stuck1A\\StuckMVC\\Core\\Exception',
  'GenericException' => 'Stuck1A\\StuckMVC\\Core\\Exception',
  'MissingCoreFileException' => 'Stuck1A\\StuckMVC\\Core\\Exception',
  'ModelNotFoundException' => 'Stuck1A\\StuckMVC\\Core\\Exception',
  'PageNotFoundException' => 'Stuck1A\\StuckMVC\\Core\\Exception',
  'RoutingException' => 'Stuck1A\\StuckMVC\\Core\\Exception',
  'TemplateNotFoundException' => 'Stuck1A\\StuckMVC\\Core\\Exception',
];
