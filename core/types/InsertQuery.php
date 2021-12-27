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
 * For anyone who truly stands for courage, love and truth will instead apply
 * the WTFPL model: do what the fuck you want with this digital factory. 
 * “FOR A SOMEWHAT FREER WORLD”
 */

namespace Stuck1A\StuckMVC\Core\Types;

use mysqli;
use mysqli_stmt;
use mysqli_result;
use Stuck1A\StuckMVC\Core\DatabaseAdapter;
use Stuck1A\StuckMVC\Core\Logger;


class InsertQuery implements Query {
  /**
   * Stores already set parts of this query object
   * @var array
   */
  private $aQueryParts;
  
  /**
   * TypeString which describes the parameter types when building a prepared statement
   * @var string
   */
  private $sTypes;
  
  /**
   * List of params to bind when building a prepared statement
   * @var array
   */
  private $aParams;
  
  /**
   * Ordered list of affected rows
   *
   * @var array
   */
  private $aCols;
  
  /**
   * Holds the mysqli connection object as soon as it exists
   * @var mysqli
   */
  private $con;
  
  /**
   * Contains the real query string after the build process
   * @var string
   */
  private $sQuery;
  
  
  
  
  /**
   * Initialises a new insert query object
   *
   */
  public function __construct() {
    $this->aQueryParts = [ 'table' => null, 'cols' => [], 'values' => [] ];
    $this->sTypes = '';
    $this->aParams = [];
    $this->aCols = [];
    return $this;
  }
  
  
  public function __desctruct() {
    if ( $this->con ) {
      $this->con->close();
    }
  }
  
  /**
   * Builds the statement.
   * This will establish a connection to the database server to prepare the statement.
   *
   * @return mysqli_stmt Prepared statement with already bound parameters.
   */
  public function build() {
    if ( isset($this->aQueryParts['table']) && !empty($this->aQueryParts['cols']) && !empty($this->aQueryParts['values']) ) {
      
    } else {
      $msg = 'Error while preparing SQL-Statement. You must at least call table(), cols() and values() to get a valid query.';
      Logger::send($msg);
      die();
    }
    
  }
  
  
  /**
   * Executes a built statement.
   * To prevent SQL injection vulnerabilities it's recommend to use the build()
   * function to get a proper prepared statement, but experienced users might
   * build and submit a manually built statement as well.
   *
   * @param mysqli_stmt  $stmt  Prepared and ready-to-execute statement object.
   *
   * @return mysqli_result  Resultset object holding the database answer.
   */
  public function execute($stmt) {
    if ( !$stmt->execute() ) {
      $msg = 'SQL-Error: Could not receive resultset for query: "' . $this->sQuery . '" Please check syntax.';
      Logger::send($msg);
      die();
    }
    return $stmt->get_result();
  }
  
  
  /**
   * Sets the INSERT INTO part of this objects query string.
   * Overwrites any already set INSERT INTO phrase.
   *
   * @param string  $tableName  Name of the target table/view/object.
   *
   * @return InsertQuery  Current object for chaining.
   */
  public function insertInto($tableName) {
    $this->aQueryParts['table'] = 'INSERT INTO ' . $tableName;
    return $this;
  }
  
  
  /**
   * Assigns values for one or more columns for the new record.
   *
   * @param array  $aValues  List of col-value-pairs to assign, e.g. [ 'email' => 'new@email.com' ]
   *
   * @return InsertQuery  Current object for chaining.
   */
  public function addValues($aValues) {
    if ( is_string($aValues) ) {
      $aValues = [$aValues];
    }
    $this->aQueryParts['cols'] += array_keys($aValues);
    $this->aQueryParts['values'] += $aValues;
    return $this;
  }
  
  
}
