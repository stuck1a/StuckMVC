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

{block 'form_login'}
  <div class="form-login">
    <form method="post">
      <input name="cl" value="User" type="hidden">
      <input name="usr" type="text" placeholder="Name/E-Mail" required />
      <input name="pwd" type="password" placeholder="Passwort" required />
      <br />
      <button type="submit" name="fnc" value="login">Anmelden</button>
      <button type="submit" name="fnc" value="register">Registrieren</button>
    </form>
  </div>
{/block}
