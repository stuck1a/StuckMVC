{*
 * This file is part of ClassicTheme, a subpackage of StuckMVC.
 * Copyright (c) 2021.
 * ClassicTheme requires StuckMVC and therein operates as the default theme.
 * ClassicTheme is free software: you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 3 as published by the Free
 * Software Foundation.
 * 
 * ClassicTheme is distributed in the hope that it will be useful, but without any
 * warranty; without even the implied warranty of merchantability of fitness for
 * a particular purpose. See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along
 * with ClassicTheme. If not, see <https://www.gnu.org/licenses/>. 
 *
 * “FOR A SOMEWHAT FREER WORLD”  
*}

{$username = $User->getActiveUsername()}

{block 'header'}
  <header>
    <div class="container-fluid">
      <div class="row">
        <div class="col-3">{include './inc/logo.tpl'}</div>
        <div class="col my-auto">{include './inc/header/search.tpl'}</div>
        <p class="col-auto my-auto">Anmeldestatus: {if empty($username)}nicht angemeldet{else}angemeldet als {$username}{/if}</p>
        {*
        <button onclick="toggle_sidebar('sidebar-left-1')" class="sidebar-toggler">L1</button>
        <button onclick="toggle_sidebar('sidebar-left-2')" class="sidebar-toggler">L2</button>
        <button onclick="toggle_sidebar('sidebar-right-1')" class="sidebar-toggler">R1</button>
        <button onclick="toggle_sidebar('sidebar-right-2')" class="sidebar-toggler">R2</button>
        *}
        <div class="col my-auto">{include_widget name=login}</div>
        <div class="col-auto my-auto">{include './inc/header/donate.tpl'}</div>
      </div>
    </div>
  </header>
{/block}


