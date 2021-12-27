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


use Stuck1A\StuckMVC\Core\Exception\InvalidArgumentException as IAE;


/**
 * Map Generator (Singleton)<br><br>
 *
 * Responsible for generation of mapping tables of any kind by linking an objects logical access point with its real
 * (physical) access point (path, name etc)<br>
 * An objects logical address can be almost anything: simple names (like the string identifiers in the list of registered
 * controllers which points to its corresponding controller class file), but also things like URLs (like the SEO
 * URLs in the SEO routing table which points to its corresponding template file).
 *
 * @package StuckMVC
 */
class Mapper extends Singleton {
  /**
   * Registered directories<br><br>
   * Entry syntax: <br><code>'˂identifier˃' => '˂dirPath˃'</code>
   *
   * @var array
   */
  private $aDirMap = [];

  /**
   * Associations of SEO URLs to template files<br><br>
   * Entry syntax: <br><code>'˂urlRelativeFromDocRoot˃' => '˂PathToTpl˃'</code>
   * @var array
   */
  private $aRoutingMap = [];
  
  /**
   * Predefined associations of SEO URLs to template files<br><br>
   * Entry syntax: <br><code>'˂urlRelativeFromDocRoot˃' => '˂PathToTpl˃'</code>
   * @var array
   */
  private $aStaticRoutes = [];
  
  /**
   * Associations of backend/page controllers to SEO URLs (recursive)<br><br>
   * Entry syntax: <br><code>'˂seoURL˃' => '˂controllerFQN˃'</code>
   * @var array
   */
  private $aControllerMap = [];
  
  /**
   * Registered model classes<br><br>
   * Entry syntax: <br><code>'˂className˃' => '˂FQN of the model class˃'</code>
   * @var array
   */
  private $aModelMap = [];
    
  /**
   * Registered widgets<br><br>
   * Entry syntax: <br><code>'˂widgetName˃' => '˂FQN of the widget controller class˃'</code>
   * @var array
   */
  private $aWidgetMap = [];
  
  
  
  
  /**
   * Caching duration for mappings (unlimited, since its usually flushed from backend after changes)
   */
  const DEFAULT_TTL = null;
  
  
  
  /**
   * Initialises all framework maps
   * If no mappings cached, it will generate and cache them.
   * If caching is disabled, it will generate the maps (on each request).
   *
   * @param bool $caching   [optional]<br>
   *                        Whether the caching system may be used to store the generated maps.
   *                        
   * @throws IAE
   */
  public static function init($caching = false) {
    $self = self::getInstance();
    $oConf = Config::getInstance();
    // check for cached maps if caching is enabled
    if ( $caching ) {
      $cacheKey = basename('mappings');
      $oCache = CacheAdapter::getInstance();
      if ( $aMaps = $oCache->get($cacheKey, false) ) {
        // restore maps from cache
        $self->aDirMap = $aMaps[0];
        $self->aRoutingMap = $aMaps[1];
        $self->aStaticRoutes = $aMaps[2];
        $self->aControllerMap = $aMaps[3];
        $self->aModelMap = $aMaps[4];
        return $self;
      }
      // generate and cache maps
      $self->buildAll($oConf);
      $aMaps = [ $self->aDirMap, $self->aRoutingMap, $self->aStaticRoutes, $self->aControllerMap, $self->aModelMap ];
      $oCache->set($cacheKey, $aMaps, self::DEFAULT_TTL);
      return $self;
    }
    // only generate maps
    $self->buildAll($oConf);
  }
  
  
  /**
   * Generates all mappings
   * 
   * @param Config  $oConf  Submitted to avoid unnecessary restoration
   */
  private function buildAll($oConf) {
    $configs = $oConf->getConfigSection('mapping');
    
    /* ConfigParser: (a) static routes (b) raw directory paths */
    $oParser = new Parser();
    foreach ( $configs['staticSeoRoutes'] as $url => $tpl ) {
      $this->aStaticRoutes[$url] = $oParser->parseConfig($tpl);
    }
    $dirs = [];
    foreach ( $configs['directories'] as $identifier => $path ) {
      $dirs[$identifier] = $oParser->parseConfig($path);
    }
    /* generate all maps */
    $this->buildDirMap($dirs)    /* for possibility of dir adjustments and facilitated access */
         ->buildSeoMap()               /* associates SEO URLs with template files */
         ->buildControllerMap()        /* associates page and backend controllers to SEO URL sections */
         ->buildModelMap()             /* for facilitated access */
         ->buildWidgetMap();           /* for facilitated access */
  }
  
  
  /**
   * Adds named paths to the directory map
   *
   * @param array  $dirs         Array containing directory map entries in format 'identifier' => 'path'.
   * @param bool $allowOverwrite If true, already existing entries will be overwritten, otherwise skipped (default: false)
   *                             
   * @return Mapper This instance for chaining
   */
  public function addDirs(array $dirs, bool $allowOverwrite = false): Mapper {
    foreach ( $dirs as $identifier => $path ) {
      if ( !$allowOverwrite && array_key_exists($identifier, $this->aRoutingMap) ) {
        continue;
      }
      $this->aDirMap[$identifier] = $path;
    }
    return $this;
  }
  
  
  /**
   * Adds one or more routes to the SEO map
   *
   * @param array $aRoutes        routes as array with one or more entries in format 'seoRoute' => 'pathRoute'
   * @param bool  $allowOverwrite If true, already existing entries will be overwritten, otherwise skipped (default: false)
   *                              
   * @return Mapper This instance for chaining
   */
  public function addSeoRoutes(array $aRoutes, bool $allowOverwrite = false): Mapper {
    foreach ( $aRoutes as $seoRoute => $pathRoute ) {
      if ( !$allowOverwrite && array_key_exists($seoRoute, $this->aRoutingMap) ) {
        continue;
      }
      $this->aRoutingMap[$seoRoute] = $pathRoute;
    }
    return $this;
  }
  
  
  /**
   * Adds one or more page controllers to the controller map
   * 
   * @param array $aControllerList Controllers as array with one or more entries in format 'contentpageID' => 'FQN to linked Controller'
   * @param bool  $allowOverwrite  If true, existing entries will be overwritten, otherwise skipped (default: false)
   *                               
   * @return Mapper This instance for chaining
   */
  public function addControllers(array $aControllerList, bool $allowOverwrite = false): Mapper {
    foreach ( $aControllerList as $seoScope => $clFQN ) {
      if ( !$allowOverwrite && array_key_exists($seoScope, $this->aControllerMap) ) {
        continue;
      }
      $this->aControllerMap[$seoScope] = $clFQN;
    }
    return $this;
  }
  
  
  /**
   * Adds one or more models to the model map
   *
   * @param array $aModels        Array with one or more entries in format 'className' => 'FQN'
   * @param bool  $allowOverwrite If true, existing entries will be overwritten, otherwise skipped (default: false)
   *                              
   * @return Mapper This instance for chaining
   */
  public function addModels(array $aModels, bool $allowOverwrite = false): Mapper {
    foreach ( $aModels as $modelClass => $modelFQN ) {
      if ( !$allowOverwrite && array_key_exists($modelClass, $this->aModelMap) ) {
        continue;
      }
      $this->aModelMap[$modelClass] = $modelFQN;
    }
    return $this;
  }
  
  
  /**
   * Adds one or more widgets to the widgets map
   *
   * @param array $aWidgets       Array with one or more entries in format 'widgetName' => 'FQN'
   * @param bool  $allowOverwrite If true, existing entries will be overwritten, otherwise skipped (default: false)
   *
   * @return Mapper This instance for chaining
   */
  public function addWidgets(array $aWidgets, bool $allowOverwrite = false): Mapper {
    foreach ( $aWidgets as $widgetName => $widgetFQN ) {
      if ( !$allowOverwrite && array_key_exists($widgetName, $this->aWidgetMap) ) {
        continue;
      }
      $this->aWidgetMap[$widgetName] = $widgetFQN;
    }
    return $this;
  }
  
  
  /**
   * Generates the directory map including the document root as first entry
   * 
   * @param array $paths Parsed paths from configs
   * 
   * @return Mapper This instance for chaining
   */
  public function buildDirMap(array $paths): Mapper {
    $docRoot = dirname($_SERVER['SCRIPT_NAME']) . '/';
    $docRoot = empty(trim($docRoot, '/')) ? '/' : $docRoot;
    $this->addDirs(['ROOT' => $docRoot] + $paths);
    return $this;
  }
  
  
  /**
   * Generates the SEO routing map<br><br>
   *
   * Process:<br>
   * Scans all directories which are registered as storage locations for content/backend page dirs. For any of those
   * "base dirs" the method will generate routes for it as follows:
   *   - Basically, all SEO URLs are structured in such a way that they reflect the directory structure within base dir
   *   - Every found directory (also base dir) or TPL file in any depth will get an SEO URL, except...
   *       ...TPL files whose file name ends with ".inc.block"
   *       ...directories which are named "inc" (any content will be ignored, too)
   *   - Routes to TPL files will use the file name (without extension) as last step in their associated SEO URL
   *   - Routes to directories will use append the default template name (without extension) to their mapped paths
   *
   * Note:
   * static (predefined) routes will overwrite generated routes if their URLs are identical, but not vice versa!
   * 
   * @return Mapper This instance for chaining
   */
  public function buildSeoMap(): Mapper {
    $oConf = Config::getInstance();
  
    // register predefined static routes
    $routesMap = [];
    foreach ( $this->aStaticRoutes as $seoUrl => $tplPath ) {
      $routesMap[$seoUrl] = realpath($tplPath);
    }
    $this->addSeoRoutes($routesMap, true);
    
    // get all base directories for (backend) content pages
    // TODO: as soon as modules are added, we need to loop through all baseDirs
    $baseDir = $this->getDir('viewsPages', 2);
    $paths = FileSystemAdapter::getRecursivePathListFromDir($baseDir, '/(?<!\.inc)\.tpl$/i', ['inc']);
    $routesMap = [];
    for ( $i = 0; $i < count($paths); $i++ ) {
      $seoUrl = Base::str_rtrim(ltrim(str_replace('\\', '/', str_replace($baseDir, '', $paths[$i])) , '\\/'), '.tpl');
      $tplPath = realpath(ltrim(str_replace($this->getDir('ROOT', 2), '', $paths[$i]), '\\/'));
      // append default template name for directory routes
      if ( is_dir($tplPath) ) {
        $tplPath .= DIRECTORY_SEPARATOR . $oConf->getConfigSection('templates')['defaultName'];
      }
      $routesMap[$seoUrl] = $tplPath;
    }
    $this->addSeoRoutes($routesMap);
    return $this;
  }
  
  
  /**
   * Builds the controller map <br><br>
   * 
   * The controller map assigns page controllers to content pages and also backend controllers to backend pages, so in
   * other words, it defines the scopes/responsibilities of the controllers.
   * 
   * @return Mapper This instance for chaining
   */
  public function buildControllerMap(): Mapper {
    $clList = [];
    // walk through known locations of PAGE controllers and scan them for mapping data (FQN and scope)
    $filter = '\\Stuck1A\\StuckMVC\\Core\\Controller\\PageController';
    $targetDir = $this->getDir('controller', 2);
    foreach ( FileSystemAdapter::getClassesInDir($targetDir, $filter, FileSystemAdapter::TYPEFILTER_CHILD) as $controller ) {
      // skip duplicates
      if ( !in_array($controller, $clList) ) {
        // try to fetch the the controllers 'mapping' property
        try {
          $oReflection = new \ReflectionClass($controller);
          $mappings = $oReflection->getStaticPropertyValue('mapping');
        } catch ( \ReflectionException $ex ) {
          // log and skip on error
          $msg = "ReflectionError occurred while trying to fetch the property 'mapping' from controller '{$controller}'. Skipped.";
          Logger::send($msg, 'warning');
          continue;
        }
        // build entries for each mapped SEO URL part
        foreach ( $mappings as $targetContentPageDir ) {
          $clList[$targetContentPageDir] = $controller;
        }
      }
    }
    // walk through known locations of BACKEND controllers and scan them for mapping data (FQN and scope)
    $filter = '\\Stuck1A\\StuckMVC\\Core\\Controller\\BackendController';
    $targetDir = $this->getDir('clAdmin', 2);
    foreach ( FileSystemAdapter::getClassesInDir($targetDir, $filter) as $controller ) {
      // skip if already collected
      if ( !in_array($controller, $clList) ) {
        // try to fetch the mapping property and
        try {
          $oReflection = new \ReflectionClass($controller);
          $mappings = $oReflection->getStaticPropertyValue('mapping');
        } catch ( \ReflectionException $ex ) {
          // log and skip on error
          $msg = "ReflectionError occurred while trying to fetch property 'mapping' from controller '{$controller}'. Skipped.";
          Logger::send($msg, 'warning');
          continue;
        }
        // build entries for each mapped SEO URL part
        foreach ( $mappings as $targetContentPageDir ) {
          $clList[$targetContentPageDir] = $controller;
        }
      }
    }
    $this->addControllers($clList);
    return $this;
  }
    
  
  /**
   * Builds the model map
   * 
   * @return Mapper This instance for chaining
   */
  public function buildModelMap(): Mapper {
    $modelList = [];
    // walk through known locations of models and fetch all children of the core Model
    $filter = 'Stuck1A\\StuckMVC\\Core\\Model';
    $targetDir = $this->getDir('models', 2);
    foreach ( FileSystemAdapter::getClassesInDir($targetDir, $filter, FileSystemAdapter::TYPEFILTER_CHILD) as $model ) {
      // skip duplicates
      if ( !in_array($model, $modelList) ) {
        // add model to collection
        $modelList[basename($model)] = $model;
      }
    }
    $this->addModels($modelList);
    return $this;
  }
  
  
  /**
   * Builds the widget map.
   * Widgets offer a self-contained function, so it is up to them to resolve their dependencies such as the classes they use.
   * So this map covers only the widgets WidgetController as its entrypoint.
   *
   * @return Mapper This instance for chaining
   */
  public function buildWidgetMap(): Mapper {
    $widgetList = [];
    // walk through known locations of widgets and fetch all children of the core WidgetController
    $filter = 'Stuck1A\\StuckMVC\\Core\\Controller\\WidgetController';
    $targetDir = $this->getDir('widgets', 2);
    foreach ( FileSystemAdapter::getSubDirNames($targetDir) as $widgetDir ) {
      foreach ( FileSystemAdapter::getClassesInDir($targetDir . DIRECTORY_SEPARATOR . $widgetDir, $filter, FileSystemAdapter::TYPEFILTER_CHILD) as $widget ) {
        // skip duplicates
        if ( !in_array($widget, $widgetList) ) {
          // add widget controller to collection
          $widgetList[$widgetDir] = $widget;
        }
      }
    }
    $this->addWidgets($widgetList);
    return $this;
  }
  
  
  /**
   * Returns the path of a registered directory or the complete directory map if no identifier is given
   *
   * @param ?string $identifier    name under which the directory path got registered
   * @param int    $option        modifier options: 0 = as stored, 1 = as relative path, 2 = as full realpath (always absolute)
   *                              These modifiers will only take effect when requesting single entry, not the whole map!
   *
   * @return array|string|null Directory path registered under <var>$identifier</var> or null if theres no matching entry
   *                             in the directory map or an array with all registered dirs if no identifier submitted.
   */
  public function getDir(string $identifier = null, int $option = 0) {
    if ( !isset($identifier) ) {
      return $this->aDirMap;
    }
    if ( !array_key_exists($identifier, $this->aDirMap) ) {
      return null;
    }
    switch ( $option ) {
      // realpath (always absolute)
      case 2: {
        // special treatment for the document root required
        if ( $identifier === 'ROOT' ) {
          return dirname($_SERVER['SCRIPT_FILENAME']);
        }
        return realpath($this->aDirMap[$identifier]);
      }
      // relative from document root
      case 1: {
        // special treatment for the document root required
        if ( $identifier === 'ROOT' ) {
          return ''; // doc root "relative to doc root"
        }
        return $this->aDirMap['ROOT'] . '/' . $this->aDirMap[$identifier];
      }
      // as stored
      case 0: default: return $this->aDirMap[$identifier];
    }
  }
  
  
  /**
   * Returns the real name of a registered directory (not the identifier under which its registered but the basename!)
   *
   * Can be understood as an improved, custom version of basename() for mapped directories.
   *
   * @param string        $identifier    Directory identifier under which the requested directory is registered.
   * @param bool    $prependSeparator    If true prepends a directory separator (default: false).
   * @param bool     $appendSeparator    If true appends a directory separator (default: false).
   *
   * @return ?string    Directory name or null if no directory registered under <var>$identifier</var>.
   */
  public function getDirRealname(string $identifier, bool $prependSeparator = false, bool $appendSeparator = false): ?string {
    if ( array_key_exists($identifier, $this->aDirMap) ) {
      $name = substr($this->aDirMap[$identifier], strrpos($this->getDir($identifier), '/'));
      $name = $prependSeparator ? $name : ltrim($name, '/');
      return $appendSeparator ?  $name . '/' : $name;
    }
    return null;
  }
  
  
  /**
   * Returns the template path to a given SEO identifier or the complete SEO map if no parameter submitted
   *
   * @param  ?string $seoURL       [optional]
   *                                A SEO identifier (the relative SEO URL).
   *
   * @return array|string|null     Template path to which a given route points to or the full routing map with all
   *                               known SEO URLs and their mapped template paths as associative Array if no
   *                               <var>$identifier</var> given. If the requested routes isn't known, it will return
   *                               an empty string.
   */
  public function getTemplateFromSEO(string $seoURL = null) {
    if ( !isset($seoURL) ) {
      return $this->aRoutingMap;
    }
    if ( array_key_exists($seoURL, $this->aRoutingMap) ) {
      return $this->aRoutingMap[$seoURL];
    }
    return null;
  }
  
  
  /**
   * Returns the controller map or the mapped controller of a specific identifier (SEO URL)
   *
   * @param  ?string $identifier  [optional]
   *                              If only a single entry is required, specify its identifier here. Autogenerated entries
   *                              will always use the controllers class name as identifier.
   *                             
   * @return array|string|null   value from $this->aControllerMap[$identifier] or null if no matching entry found or the
   *                             complete map as array is no parameter or null submitted.
   */
  public function getController(string $identifier = null) {
    if ( !isset($identifier) ) {
      return $this->aControllerMap;
    }
    if ( array_key_exists($identifier, $this->aControllerMap) ) {
      return $this->aControllerMap[$identifier];
    }
    return null;
  }
  
  
  /**
   * Returns the full qualified namespace (FQN) of any known model or the full model map
   *
   * @param  ?string $identifier [optional]
   *                             If only a single entry is required, specify its identifier here. Autogenerated entries
   *                             will always use the models class name as identifier.
   *                             
   * @return array|string|null   value from $this->aModelMap[$identifier] or null if no matching entry found or the
   *                             complete map as array if no parameter or null submitted.
   */
  public function getModel(string $identifier = null) {
    if ( !isset($identifier) ) {
      return $this->aModelMap;
    }
    if ( array_key_exists($identifier, $this->aModelMap) ) {
      return $this->aModelMap[$identifier];
    }
    return null;
  }
  
  
  
  /**
   * Returns the full qualified namespace (FQN) of any known widget controller or the full widget map
   *
   * @param  ?string $identifier If only a single entry is required, specify its identifier here. Autogenerated entries
   *                             will always use the widgets directory name as identifier.
   * @return array|string|null   value from $this->aWidgetMap[$identifier] or null if no matching entry found or the
   *                             complete map as array if no parameter or null submitted.
   */
  public function getWidget(string $identifier = null) {
    if ( !isset($identifier) ) {
      return $this->aWidgetMap;
    }
    if ( array_key_exists($identifier, $this->aWidgetMap) ) {
      return $this->aWidgetMap[$identifier];
    }
    return null;
  }
  
}
