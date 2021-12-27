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


use Stuck1A\StuckMVC\Application\Model\User;


/**
 * Grants application control. Called on every request.
 *
 * @package StuckMVC
 */
class Application {
  /**
   * Wraps bootstrap und launch sequences to ensure correct execution order.
   */
  public function start() {
    $this->boot();
    $this->run();
  }
  
  
  /**
   * Executes the bootstrap sequence which will prepare the framework to take off. Any stored input (cache etc)
   * will be restored if possible. Anything else will be rebuilt from scratch and -if possible - stored to speed up
   * further requests.
   */
  private function boot() {
    /* set up general config */
    $oConf = Config::init();
    /* restore or generate base stuff */
    if ( $caching = $oConf->isUseCaching() ) {
      CacheAdapter::init($oConf->getConfigSection('mapping')['directories']['cache']);
    }
    Mapper::init($caching);
    RendererFacade::init();
  }
  
  
  /**
   * Executes current request.
   * Requires the application being ready for execution, what means the bootstrap sequence has been executed already.
   */
  private function run() {
    $this->initializeSession();
    /* check for received POST request input and process if so */
    if ( !empty($_POST) ) {
      (new RequestHandler($_REQUEST))->process();
    }
    /* resolve requested route */
    $aData = Router::utilizeRequest($_SERVER['REQUEST_URI']);
    /* invoke matching controller to trigger rendering for resolved template path */
    Dispatcher::invokeController($aData['controller'], $aData['template'], $aData['params']);
  }
  
  
  /**
   * Part of the boot process.
   * Searchs server-side for stored session data.
   * If a session is stored for the current client, restores it (including auto-login),
   * otherwise starts a new session for this client.
   *
   * Management of the session id (authentication token): 
   *   - If the client allows cookies, the SID is stored as cookie
   *   - If the client denied cookies, the SID is carried along as session variable
   */
  private function initializeSession() {
    // restore or start new session
    session_start();
    // auto login user
    if ( isset($_SESSION['bValid']) && $_SESSION['bValid'] ) {
      if (isset($_SESSION['sUsername']) ) {
        User::getInstance()->setActiveUserName($_SESSION['sUsername']);
      }
    }
  }
  
}
