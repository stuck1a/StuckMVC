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


use mysqli;
use mysqli_sql_exception;


/**
 * Contains functions to connect to the database.
 *
 * @package StuckMVC
 */
class DatabaseAdapter {
  /**
   * Hostname or IP of the currently used database.
   * @var string
   */
  private $dbHost;
    
  /**
   * Port of the currently used database.
   * @var int
   */
  private $dbPort;
  
  /**
   * Name of the currently used database.
   * @var string
   */
  private $dbName;
  
  /**
   * Username for the currently used database.
   * @var string
   */
  private $dbUser;
  
  /**
   * Password for the currently used database.
   * @var string
   */
  private $dbPass;
  
  
  
  /**
   * DatabaseAdapter constructor.<br>
   * Load submitted database configs and sets the SQL reporting level
   *
   * @param int   $reportingMode Flags for database reporting (mysqli_reports).<br>
   *                             Default: MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT
   */
  public function __construct(int $reportingMode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT) {
    // store connection details in corresponding properties
    $this->init();
    // set submitted reporting options
    mysqli_report($reportingMode);
    // for chaining
    return $this;
  }

  
  /**
   * Decides which database details are necessary, then loads and stores them.
   */
  private function init() {
    $oConf = Config::getInstance();
    $configs = $oConf->isLocalhost() ? $oConf->getConfigSection('database')['local'] : $oConf->getConfigSection('database')['web'];
    $this->dbHost = (string) $configs['host'];
    $this->dbPort = (int) $configs['port'];
    $this->dbName = $configs['name'];
    $this->dbUser = $configs['user'];
    $this->dbPass = $configs['pass'];
  }
  
  
  /**
   * Establishes a new database connection and returns the ressource handle.
   * 
   * @return mysqli connection object
   */
  public function connect(): mysqli {
    return new mysqli($this->dbHost, $this->dbUser, $this->dbPass, $this->dbName, $this->dbPort);
  }
  
  
  /**
   * Returns the database name
   * 
   * @return string The configured name of the used Database.
   */
  public function getDbName(): string {
    return $this->dbName;
  }
  
}
