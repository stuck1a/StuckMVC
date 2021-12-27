<?php
/*
 * This file is part of StuckMVC <https://stuck1a.de>,
 * Copyright (c) 2021.
 * StucksSeiten uses the StuckMVC framework, which is free software: you can
 * redistribute it and/or modify it under the terms of the GNU General Public
 * License version 3 as published by the Free Software Foundation.
 *
 * StucksSeiten is the official website of the StuckMVC framework and further
 * designed as an usage example. It is distributed in the hope that it will be useful,
 * but without any warranty; without even the implied warranty of merchantability
 * of fitness for a particular purpose. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * StuckMVC. If not, see <https://www.gnu.org/licenses/>. 
 *
 * FOR A SOMEWHAT FREER WORLD.
 */

namespace Stuck1A\StuckMVC\Application\Model;

use Stuck1A\StuckMVC\Core\RendererFacade;
use Stuck1A\StuckMVC\Core\Types\SelectQuery;
use Stuck1A\StuckMVC\Core\Singleton;


/**
 * Logical representation of a database table or view. Offers functionality regarding any action which need to interact
 * with this data source somehow, like log in/out, registration, get user data, check user rights, ...
 *
 * @package StuckMVC
 */
class User extends \Stuck1A\StuckMVC\Core\Model {
  
  /**
   * Stores all active singleton objects. Only one instance per type is possible
   * @var object
   */
  private static $instance;
  
  
  /**
   * Name of this models data source (table/view/file)
   * @var string
   */
  protected $sourceName = 'vwuser';
  
  /**
   * Contains the users username as soon as the user has logged in
   * @var string
   */
  protected $activeUserName;  

  
  
  /**
   * Description of the data sources columns.
   * Only required for filebase mode!
   * @var array
   */
  public static $filebaseMeta = [
    'AUTO_INC' => 0,
    
    'cols' => [
      'id'         =>  'int(10) = <AUTO_INCREMENT>',
      'email'      =>  'string(50)',
      'role'       =>  'string(5) = user',
      'username'   =>  'string(25)',
      'confirmed'  =>  'bool() = 0',
      'passhash'   =>  'string(60)',
      'regdate'    =>  'timestamp() = <CURRENT_TIMESTAMP>',
      'lastlogin'  =>  'timestamp() = <CURRENT_TIMESTAMP>'
    ]
  ];
    
  
  /**
   * Constructs the model which represents the user data
   */
  function __construct() {
    parent::__construct();
  }
  
  
  // TODO: In ModelAdapter o.ä. auslagern (Zwischenmethode zw. concrete und Core Model.)
  //    ODER: Endlich das Singleton iwie zu einem Interface umbiegen
  //    ODER: Geht es vll doch als protected mit in das Core Model? 
  public static function getInstance() {
    if ( !isset(self::$instance) ) {
      self::$instance = new static();
    }
    return self::$instance;
  }
  
  
  
  public function getUsernameByID($id) {
    $qb = new SelectQuery();
    $qb->select('username')
       ->from($this->sourceName)
       ->where('id', $id);
    $stmt = $qb->build();
    $resultset = $qb->execute($stmt);
    return $resultset->fetch_row()[0] ?? null;
  }
  
  
  public function getPasshashByUserOrMail($UserOrMail) {
    $qb = new SelectQuery();
    $qb->select(['passhash'])
       ->from($this->sourceName)
       ->where('username', $UserOrMail)
       ->or('email', $UserOrMail);
    $stmt = $qb->build();
    $resultset = $qb->execute($stmt);
    return $resultset->fetch_assoc()['passhash'] ?? null;
  }
  
  
  /**
   * Callable by RequestHandler.
   * Checks whether the submitted login details are valid or not and performs login action if necessary.
   * Writes the result of the action to the global template variable $message.
   * 
   * @param array  $postData  POST data with at least the fields 'usr' and 'pwd'
   *                          
   * @return bool  True if login succeed, false otherwise.
   */
  public static function loginRequest($postData) {
    // fetch compare hash
    $oUser = self::getInstance();
    
    if ( !$_SESSION['bValid'] ) {
      // validate input data
      if ( !isset($postData['usr'], $postData['pwd']) ) {
        $msg = 'Ungültiger Login-Request!';
        RendererFacade::getInstance()->getSmarty()->assignGlobal('message', $msg);
        return false;
      }
      // validate username
      $dbHash = $oUser->getPasshashByUserOrMail($postData['usr']);
      if ( empty($dbHash) ) {
        $msg = 'Username unbekannt!';
        RendererFacade::getInstance()->getSmarty()->assignGlobal('message', $msg);
        return false;
      }
      // validate submitted password
      if ( !password_verify($postData['pwd'], $dbHash) ) {
        $msg = 'Passwort falsch!';
        RendererFacade::getInstance()->getSmarty()->assignGlobal('message', $msg);
        return false;
      }
    }
    
    // TODO: Gleioh alle Daten vom User downloaden und als Properties speichern, damit Userpages schneller laden
    // login user
    $_SESSION['bValid'] = true;
    $_SESSION['iStart'] = $_SESSION['iStart'] ?? time();
    $_SESSION['sUsername'] = $oUser->activeUserName = $postData['usr'] ?? $_SESSION['sUsername'];
    //$_SESSION['sid'] = session_id();
    

    $msg = 'Anmeldung erfolgreich!';
    RendererFacade::getInstance()->getSmarty()->assignGlobal('message', $msg);
    return true;
  }
  

  
  
  /**
   * Callable by RequestHandler.
   * Validates submitted data and tries to create a new user account.
   *
   * @param array  $postData  POST data with at least the fields 'usr' and 'pwd'
   *
   * @return bool  True if login succeed, false otherwise.
   */ 
  public static function register($postData) {
    if ( !isset($postData['usr'], $postData['pwd']) ) {
      $msg = 'Ungültiger Login-Request!';
      RendererFacade::getInstance()->getSmarty()->assignGlobal('message', $msg);
      return false;
    }
    $msg = 'Funktion befindet sich im Aufbau!';
    RendererFacade::getInstance()->getSmarty()->assignGlobal('message', $msg);
    return false;
  }
  
  
  
  /**
   * Template getter function.
   * Returns the username of the currently active user or an empty string if user is not logged in
   * 
   * @returns string  Username of currently logged in user or empty string if not logged in
   */
  public function getActiveUsername() {
    return $this->activeUserName ?? '';
  }
  
  
  /**
   * Sets the name of the currently active user.
   * This won't change the username stored in database, but the name used by the application to do user related stuff.
   * 
   * @param string  $activeUserName  Username of the currently logged in user
   */
  public function setActiveUserName(string $activeUserName) {
    $this->activeUserName = $activeUserName;
  }
  
  
}





