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

use Smarty;


/**
 * Provides a simplified interface for initialisation and configuration of Smarty
 *
 * @package StuckMVC
 */
class RendererFacade extends Singleton {
  /**
   * Reference to the smarty instance
   * @var Smarty
   */
  private $smarty;
  
  /**
   * Smarty setting, whether templates shall be recompiled on requests, even if there are valid cached compilations)
   * @var bool
   */
  private $forceViewRecompile;
  
  /**
   * Assumed file name of default templates<br>
   * SEO URLs whose path points to a directory will append this name when they are generated.
   * @var string
   */
  private $defaultTemplateName;
  
  
  
  /**
   * Creates and configures the Smarty object.
   */
  public static function init() {
    $self = self::getInstance();
    $oConf = Config::getInstance();
    $oMapper = Mapper::getInstance();
    $configs = $oConf->getConfigSection('templates');
    // class init
    $self->smarty = $oSmarty = new Smarty();
    $self->defaultTemplateName = $configs['defaultName'];
    $oSmarty->setForceCompile($self->forceViewRecompile = $configs['forceRecompile']);
    // disable unneeded overhead stuff and enable some settings which safely speeds up Smarty
    
    // activate filter plugins
    $oSmarty->addAutoloadFilters([ 'output' => ['protect_email'] ]);
    // path mappings
    $oSmarty->addPluginsDir($oMapper->getDir('plugins', 2));
    $oSmarty->setCompileDir($oMapper->getDir('cacheComp', 2));
    $oSmarty->setConfigDir($oMapper->getDir('views', 2));
    $oSmarty->setCacheDir($oMapper->getDir('cacheRend', 2));
    $oSmarty->setTemplateDir($oMapper->getDir('views', 2));
    $oSmarty->addTemplateDir($oMapper->getDir('viewsTheme', 2));
    // config files
    $oSmarty->configLoad('facts.conf', 'general');
    $oSmarty->configLoad('facts.conf', $oConf->getActiveTheme());
    // model access in templates
    foreach ( $oMapper->getModel() as $name => $model ) {
      $oSmarty->assign($name, new $model(), true);
    }
  }
  
  
  /**
   * Returns the Smarty object instance
   * 
   * @return Smarty
   */
  public function getSmarty(): Smarty {
    return $this->smarty;
  }
  
  
  /**
   * Returns the template name used for SEO URLs to directories
   * 
   * @return string
   */
  public function getDefaultTemplateName(): string {
    return $this->defaultTemplateName;
  }
  
}
