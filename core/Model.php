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
use mysqli_stmt;
use mysqli_result;


/**
 * Parent Model Class. Manages all models and contains functions related to the model-database-relation.
 * Will be created withing the bootstrap process. Opens a new database connection, if necessary.
 * Then it will scan mapped directory for model classes and dispatch all found models, submitting them the connection
 * object (or a null object if database mode is disabled), so the model classes can do their database jobs.
 * @package StuckMVC
 */
abstract class Model {
  /**
   * Whether the application is running in database (true) or filebase (false) mode.
   * @var bool
   */
  protected $databaseMode;
  
  /**
   * Realpath to the models data file (filebase mode only).
   * @var ?string
   */
  protected $datafile;

  
  
  /**
   * Model constructor<br><br>
   * 
   * Always comes from non-abstract child. Sets properties regarding to database configurations.
   * Provides general properties and database operations every model class will inherit.
   */
  protected function __construct() {
    $this->databaseMode = Config::getInstance()->getUseDatabase();
    $this->datafile = $this->databaseMode ? Mapper::getInstance()->getDir('filebase', 2) . DIRECTORY_SEPARATOR . $this->sourceName : null;
    RendererFacade::getInstance()->getSmarty()->assignGlobal('User', $this);
  }
  
  
  /**
   * Returns all column names<br>
   *
   * @return array  List of column names
   */
  protected function db_getColNames() {
    return $this->db_getMeta('Field');
  }
    
  
  /**
   * Yields all Records from database as ArrayObjects or imploded to strings
   * 
   * @param ?string $sep  [optional]<br>
   *                      Specify a separator to receive concatenated records separated by the specified separator.
   *
   * @return iterable     Iterable list of records. As array structure if no separator specified or concatenated
   *                      to strings, if a valid separator is set.
   */
  protected function db_getAllRecords($sep = null): iterable {
    // build and execute statement
    $resultset = $this->db_sendSelect('SELECT * FROM ' . $this->sourceName);
    // set result type depending on request type
    $type = $sep ? MYSQLI_NUM : MYSQLI_ASSOC;
    // yield the records
    foreach ( $resultset->fetch_all($type) as $record ) {
      yield (isset($sep)? implode($sep, $record) : $record);
    }
    
  }
  
 
  /**
   * Executes a <code>SHOW</code> statement to receive meta data of the columns from the models database source.
   *
   * @param ?string $property [optional]<br>
   *                          Set to only receive a specific instead of all meta properties.<br><br>
   *                          Each col object will have following six string properties:<ul>
   *                          <li>'Default' = col default values</li>
   *                          <li>'Extra'   = ?</li>
   *                          <li>'Field'   = col names</li>
   *                          <li>'Key'     = col key definitions</li>
   *                          <li>'Null'    = whether cols are nullable or not</li>
   *                          <li>'Type'    = col definition strings (e.g. 'int(10) unsigned')</li></ul>
   *
   * @return ?array  If no property set:       entries are associative arrays with all properties (one per col)<br>
   *                 If valid property set:    entries are strings with the requested property (one per col)<br>
   *                 If invalid property set:  returns null<br>
   */
  protected function db_getMeta(string $property = null): ?array {
    // build and execute statement
    $resultset = $this->db_sendSelect('SHOW COLUMNS FROM ' . $this->sourceName);
    // fetch results as array<string> or array<array<string>> if no property specified
    $results = [];
    while ( $metas = $resultset->fetch_array(MYSQLI_ASSOC) ) {
      // if single property requested
      if ( isset($property) ) {
        // abort if requested invalid property
        if ( !isset($metas[$property]) ) {
          $results = null;
          break;
        }
        // collect current cols property
        $results[] = $metas[$property];
        continue;
      }
      // if no specific property requested, collect all metas of current col
     $results[] = $metas;
    }
    return $results;
  }
  

  /**
   * Executes an <code>SELECT</code> query on the models database view<br><br>
   *
   * Although this function is designed to process <code>SELECT</code> statements, it
   * may work for execution of any <code>DQL</code> or <code>DCL</code> command as well.
   * 
   * @param string $query The SQL query to execute.
   *                      
   * @return mysqli_result|false The fetched resultset or false on failure.
   */
  protected function db_sendSelect(string $query): mysqli_result {
    // establish database connection and build the prepared statement
    $con = (new DatabaseAdapter())->connect();
    $stmt = $con->prepare($query);
    // any errors occurred while creating statement object?
    if ( !$stmt ) {
      $msg = 'SQL-Error: Could not create prepared statement for query: \"' . $query . '\"';
      Logger::send($msg, 'warning');
      return false;
    }
    // execute statement
    if ( !$stmt->execute() ) {
      $msg = 'SQL-Error: Could not receive resultset for query: \"' . $query . 'Please check syntax.';
      Logger::send($msg, 'warning');
      return false;
    }
    // build the resultset object and close connection
    $resultset = $stmt->get_result();
    $con->close();
    return $resultset;
  }
  
}
