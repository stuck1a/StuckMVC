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


class SelectQuery implements Query {
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
   * Holds the mysqli connection object as soon as it exists
   * @var mysqli
   */
  private $con;
  
  /**
   * Contains the real query string after the build process.
   * @var string
   */
  private $sQuery;
  
  
  /**
   * Initialises a new query object
   * 
   * @return 
   */
  public function __construct() {
    $this->aQueryParts = [ 'select' => 'SELECT *', 'from' => null ];
    $this->sTypes = '';
    $this->aParams = [];
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
    if ( isset($this->aQueryParts['from']) ) {
      $sQuery = '';
      foreach ( $this->aQueryParts as $part ) {
        if ( is_string($part) ) {
          if ( !empty($part) ) {
            $sQuery .= $part . ' ';
          }
          continue;
        }
        if ( is_array($part) ) {
          foreach ( $part as $entry ) {
            if ( !empty($entry) ) {
              $sQuery .= $entry . ' ';
            }
          }
        }
      }
      $this->sQuery = $sQuery;
    } else {
      Logger::send('Error while preparing SQL-Statement. You must at least call from() to get a valid query.');
      die();
    }
    $this->con = (new DatabaseAdapter())->connect();
    if ( !$stmt = $this->con->prepare($this->sQuery) ) {
      $msg = 'SQL-Error: Could not create prepared statement for query: "' . $this->sQuery . '"';
      Logger::send($msg);
      die();
    }
    if ( !empty($this->sTypes) ) {
      if ( !$stmt->bind_param($this->sTypes, ...$this->aParams) ) {
        $msg = 'Failed to bind parameters to Query: "' . $this->sQuery . '"';
        Logger::send($msg);
        die();
      }
    }
    return $stmt;
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
   * Sets the SELECT part of this objects query string.
   * Overwrites any already set SELECT phrase. 
   *
   * @param string|string[]  $fields  [optional]<br>
   *                         Name or list of names of the target columns to fetch.
   *                         By default all columns will be fetched. (asterisk)
   *                           
   * @return SelectQuery  Current object for chaining.
   */
  public function select($fields = '*') {
    $sSelect = 'SELECT ';
    if ( is_string($fields) ) {
      $fields = [$fields];
    }
    for ( $i = 0; $i < count($fields); $i++ ) {
      $sSelect .= $fields[$i] . ($i < count($fields) - 1 ? ',' : '');
    }
    $this->aQueryParts['select'] = $sSelect;
    return $this;
  }
  
  
  /**
   * Sets the mandatory FROM part of this objects query string.
   * Overwrites any already set FROM phrase.
   * 
   * @param string  $name  Name of the database table, view or object from which the data shall be fetched.
   *                
   * @return SelectQuery  Current object for chaining.
   */
  public function from($name) {
    if ( is_string($name) ) {
      $this->aQueryParts['from'] = 'FROM ' . $name;
    }
    return $this;
  }
  
  
  /**
   * Sets the WHERE part of this objects query string.
   * Overwrites any already set WHERE condition.
   * To add further conditions use and() or or() (whatever logic suits for your needs)
   *
   * @param string  $col  Name of the column this condition affects.
   * @param mixed   $val  Comparison value of the condition.
   *       
   * @return SelectQuery  Current object for chaining.
   */
  public function where($col, $val) {
    $this->aQueryParts['where'] = $this->addCondition('WHERE',$col, $val);
    return $this;
  }
  

  /**
   * Adds an top-level AND condition to this objects query string.
   *
   * @param string  $col  Name of the column this condition affects.
   * @param mixed   $val  Comparison value of the condition.
   * 
   * @return SelectQuery  Current object for chaining.
   */
  public function and($col, $val) {
    $this->aQueryParts['and'][] = $this->addCondition('AND',$col, $val);
    return $this;
  }
  
  
  /**
   * Adds an top-level OR condition to this objects query string.
   *
   * @param string  $col  Name of the column this condition affects.
   * @param mixed   $val  Comparison value of the condition.
   * 
   * @return SelectQuery  Current object for chaining.
   */
  public function or($col, $val) {
    $this->aQueryParts['or'][] = $this->addCondition('OR',$col, $val);
    return $this;
  }
  
  
  /**
   * Sets the ORDER BY part of this objects query string.
   * Overwrites any already set WHERE phrase.
   *
   * @param array  $aColExpr  List of col names or order-by-expressions. (like COUNT)
   * @param bool   $desc      [optional]<br>
   *                          If set to true, orders results in descending ranking.
   *                          By default, the results are ordered in ascending ranking.
   *                          
   * @return SelectQuery  Current object for chaining.
   */
  public function orderBy($aColExpr, $desc = false) {
    $sOrder = 'ORDER BY ';
    for ( $i = 0; $i < count($aColExpr); $i++ ) {
      $sOrder .= $aColExpr[$i] . ($i < count($aColExpr) - 1 ? ',' : '');
    }
    $sOrder .= $desc ? ' ASC' : ' DESC';
    $this->aQueryParts['orderby'] = $sOrder;
    return $this;
  }
  
  
  /**
   * Sets the LIMIT part of this objects query string.
   * Overwrites any already set LIMIT phrase.
   * If not set, the query will request unlimited (all matching) records.
   * 
   * @param int  $limit   Maximum number of records to fetch.
   * 
   * @return SelectQuery  Current object for chaining.
   */
  public function limit($limit) {
    if ( is_int($limit) ) {
      $this->aQueryParts['limit'] = 'LIMIT ' . $limit;
    }
    return $this;
  }
  
  
  /**
   * Holds the common code to process any conditional phrases (WHERE, AND; OR)
   *
   * @param string  $type  Condition tag.
   * @param string  $col   Name of the column this condition affects.
   * @param mixed   $val   Comparison value of the condition.
   *
   * @return string  The computed query string part.
   */
  private function addCondition($type, $col, $val) {
    $sExpr = $type . ' ';
    $sType = '';
    if ( is_int($val) || is_bool($val) ) {
      $sExpr .=  $col . ' = ?';
      $sType .= 'i';
    } elseif ( is_double($val) ) {
      $sExpr .=  $col . ' = ?';
      $sType .= 'd';
    } else {
      $sExpr .= $col . ' LIKE ?';
      $sType .= 's';
    }
    $this->sTypes .= $sType;
    $this->aParams[] = $val;
    return $sExpr;
  }
  
}
