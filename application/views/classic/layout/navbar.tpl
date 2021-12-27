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

{block 'navbar'}
  <nav>
    <ul class="navbar-nav d-flex">
      
      {* Home *}
      <li class="mb-2">
        <a href="{getRootLink}home" style="text-decoration: none; color: var(--body-color_primary);">
          <img src="{getImagesPath}buttons/sidebar.svg" class="position-absolute" alt="" style="width: 10%;">
          <p class="position-relative fs-1 m-0 ms-4">Home</p>
        </a>
      </li>

     {* Musik *}
      <li class="mb-2">
        <a href="{getRootLink}music" style="text-decoration: none; color: var(--body-color_primary);">
          <img src="{getImagesPath}buttons/sidebar.svg" class="position-absolute" alt="" style="width: 10%;">
          <p class="position-relative fs-1 m-0 ms-4">Musik</p>
        </a>
      </li>

     {* Musik (Sub) *}
      <li class="mb-2">
        <a href="{getRootLink}music/byartist" style="text-decoration: none; color: var(--body-color_primary);">
          <img src="{getImagesPath}buttons/sidebar.svg" class="position-absolute" alt="" style="width: 10%;">
          <p class="position-relative fs-1 m-0 ms-4">Musik<span class="fs-5">Sub</span></p>
        </a>
      </li>

      {* To-Do *}
      <li class="mb-2">
        <a href="{getRootLink}todo" style="text-decoration: none; color: var(--body-color_primary);">
          <img src="{getImagesPath}buttons/sidebar.svg" class="position-absolute" alt="" style="width: 10%;">
          <p class="position-relative fs-1 m-0 ms-4">To-Do</p>
        </a>
      </li>
      
    </ul>
  </nav>
{/block}
