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


/**
 * Processes and assigns all necessary configurations, capture some meta data like server-client-relation (local or
 * remote?) and also attempts to create the directory map, because it is required 
 * 
 * @package StuckMVC
 */
class Config extends Singleton {
  /**
   * Name of the application (identical with the name of the docRoot directory)
   * @var string
   */
  private $projectName;
  
  /**
   * Active data source mode<br>
   * If true, uses specified database. Otherwise the application will run in "filebase" mode which uses local files as
   * replacement for real database tables. Models do not care about their data source, so the only major difference
   * in filebase mode is an overall slowdown, because I/O operations in the filesystem usually are way slower than
   * database requests.
   * @var bool
   */
  private $useDatabase;
  
  /**
   * Whether the caching system is enabled or not.
   * @var bool
   */
  private $useCaching;
  
  /**
   * Path to the logfile
   * @var string
   */
  private $logfile;
  
  /**
   * Name of the active theme 
   * @var string
   */
  private $activeTheme;
  
  /**
   * Whether the application is currently executed on a local host (dev environment or tunneling).
   * Enables different document root locations for local and web hosts without using vHosts (useful for shared server),
   * but also allows to use different databases, so no fancy docker stuff is required to archive this.
   * @var bool 
   */
  private $localhost;
  
  /**
   * Content of file the configs.php core file
   * @var array
   */
  private $configSections;
  
  
  /**
   * Processes all general configurations from the raw configs but also store the whole raw configs array to provide
   * them for other classes which will process their respective configs from it.
   * 
   * @return Config The config instance
   */
  public static function init() {
    $self = self::getInstance();
    
    // fetch raw configs
    $self->configSections = include 'configs.php';
    $configs = $self->getConfigSection('project');
    
    // set project settings
    $self->logfile = $configs['logging']['filepath'];
    error_reporting($configs['logging']['php_error_reporting']);
    ini_set('error_reporting', $configs['logging']['php_error_reporting']);
  
    $self->projectName = $configs['name'];
    $self->useDatabase = $configs['useDatabase'];
    $self->localhost = Base::isExecOnLocalhost();
  
    // get and store active theme name
    // TODO: replace hardcoded asap
    $self->setActiveTheme('classic');
    
    // set up cache system, if caching is enabled
    $self->useCaching = $configs['useCaching'];
    
    // chaining
    return $self;
  }
  
  
  /**
   * Getter function for class property useDatabase.
   * @return bool value of property Config->useDatabase
   */
  public function getUseDatabase(): bool {
    return $this->useDatabase;
  }
  
  
  /**
   * Getter function for class property projectName.
   * @return string
   */
  public function getProjectName(): string {
    return $this->projectName;
  }
  
  
  /**
   * Returns the path to the log file.
   * @return string Path to log file.
   */
  public function getLogfile(): string {
    return $this->logfile;
  }
  
  
  /**
   * Returns whether the application is running on a local machine or not.
   * @return bool true if running on localhost, false otherwise
   */
  public function isLocalhost(): bool {
    return $this->localhost;
  }
  
  
  /**
   * Returns whether the caching system is enabled by settings or not.
   * @return bool Value of setting 'useCaching'
   */
  public function isUseCaching(): bool {
    return $this->useCaching;
  }
  
  
  /**
   * Returns full list of the raw (unprocessed tags) as stored in configs.php
   * 
   * @param ?string $section [optional]
   *                         The identifier of the (top level) section to return. With no identifier provided, instead
   *                         returns an array containing all sections. 
   *
   * @return array           Associative Array with configs. Either the entries from a specific or all sections.
   *                         If the section identifier provided is unknown, returns null and logs a warning. 
   * Requested section including any subsections of it.
   */
  public function getConfigSection(string $section = null): ?array {
    if ( isset($section) ) {
      if ( array_key_exists($section, $this->configSections) ) {
        return $this->configSections[$section];
      }
      $msg = "Invalid configFile section {$section} requested. Depending from context, this may or may not cause an fatal error. " .
             "So or So, this is not good. Except unexpected behaviour.";
      Logger::send($msg, 'warning');
      return null;
    }
    return $this->configSections;
  }
  
  
  /**
   * Template variable getter for class property activeTheme.
   * @return string name of the active theme
   */
  public function getActiveTheme(): string {
    return $this->activeTheme;
  }
  
  
  /**
   * Custom setter function for property activeTheme. Allowed characters are letters, digits, _, -, #, @ and spaces.
   * For the change to take effect, cached directory mappings must be dumped, forcing the application to parse it new
   * with the next request while using the new value for any ˂themename˃ tag.
   * You may trigger the request by yourself to avoid a longer loading time for the next visitor.
   *
   * @param string $activeTheme    Name of the new active theme.
   */
  public function setActiveTheme(string $activeTheme) {
    if ( Base::isAlphanumeric($activeTheme, ['_', '-', '#', '@', ' ']) ) {
      $this->activeTheme = $activeTheme;
    }
  }
  
}
