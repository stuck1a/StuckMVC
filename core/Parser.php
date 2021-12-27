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
 * Responsible for any parsing job within application logic (Smarty render engine parses presentation logic).
 * Offers different parsing functions for different requirements like the config parser which simply replaces
 * any known config tag from a single config values.
 *
 * @package StuckMVC
 */
class Parser {
  
  /**
   * Replaces all known config tags from a given config value.
   * Only strings are parseable, configs with other types (e.g. bool or int) won't be affected.
   * 
   * @param  mixed  $config    The raw config value.
   *                      
   * @return mixed             The parsed config value.
   */
  public function parseConfig($config) {
    if ( !is_string($config) ) {
      return $config;
    }
    $parsed = $config;
    foreach ( $this->getConfigTags() as $tag => $val ) {
      $parsed = str_replace($tag, $val, $config);
    }
    return $parsed;
  }
  
  
  /**
   * Fetches list of tags used by the config parser from fact sheet
   */
  private function getConfigTags(): array {
    return [
      '<themename>' => Config::getInstance()->getActiveTheme()
    ];
  }
  
}
