{*
 * This file is part of ClassicTheme, a subpackage of StuckMVC.
 * Copyright (c) 2021-2026.
 * ClassicTheme is free software: you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 3 as published by the Free
 * Software Foundation.
 *
 * “FOR A SOMEWHAT FREER WORLD”
 *
 * Variables: $items - list of routes from the start page to the current page
*}

{block 'widget_breadcrumb'}
  {if !empty($items)}
    <nav aria-label="Brotkrumennavigation">
      <ol class="breadcrumb">
        {foreach $items as $item}
          {if $item@last}
            <li class="breadcrumb-item active" aria-current="page">{$item->title}</li>
          {else}
            <li class="breadcrumb-item"><a href="{url path=$item->path}">{$item->title}</a></li>
          {/if}
        {/foreach}
      </ol>
    </nav>
  {/if}
{/block}
