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

<div>
  <h1>Login</h1>
  <p>Bitte anmelden, um fortzufahren:</p>
  <form action="{$getControllerDirectory()}LoginController.php" method="POST">
    <label for="usr"><b>Benutzer:</b></label>
    <input name="usr" type="text" placeholder="Enter username..." required />
    <br>
    <label for="psw"><b>Passwort:</b></label>
    <input name="psw" type="password" placeholder="Enter password..." required />
    <br>
    <button name="submitCreds" type="submit">Anmelden</button>
  </form>
</div>
