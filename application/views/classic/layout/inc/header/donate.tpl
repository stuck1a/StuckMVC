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

{block 'logo'}
  <div class="container-donate">
    <form action="https://www.paypal.com/donate" method="post" target="tab">
      <input type="hidden" name="hosted_button_id" value="T6F4X9VJ6DDW8" /> {* TODO: Konto mit Pseudonym einrichten *}
      <button type="submit" class="btn-donate" style="border-radius: 50%; padding: 10px;">Donate</button>
    </form>
  </div>
{/block}