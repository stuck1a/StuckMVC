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

<html lang="de">
  <head>
    {block 'head'}
      <title>{block 'title'}{#title#} | {$smarty.block.child}{/block}</title>
      {block 'meta'}
        <meta charset="{#metaEncoding#}">
        <meta name="author" content="{#metaAuthor#}">
        <meta name="description" content="{#metaDescription#}">
        <meta name="keywords" content="{#metaKeywords#}">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="apple-touch-icon" sizes="180x180" href="{getImagesPath}favicons/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="{getImagesPath}favicons/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="{getImagesPath}favicons/favicon-16x16.png">
        <link rel="manifest" href="{getImagesPath}favicons/site.webmanifest">
        <link rel="mask-icon" href="{getImagesPath}favicons/safari-pinned-tab.svg" color="{#windowsTileColor#}">
        <link rel="shortcut icon" href="{getImagesPath}favicons/favicon.ico">
        <meta name="msapplication-TileColor" content="{#windowsTileColor#}">
        <meta name="msapplication-config" content="{getImagesPath}favicons/browserconfig.xml">
      {/block}
      {block 'styles'}
      <link rel="stylesheet" href="{getStylesPath}libs/bootstrap.min.css">
      <link rel="stylesheet" href="{getStylesPath}theme.css">
      {/block}
    {/block}
  </head>
  <body>
    {block 'viewport'}
      {include './header.tpl'}
      {block 'base'}{/block}
      {include './footer.tpl'}
    {/block}
    

    {include './sidebars.tpl'}

    {block 'scripts'}
      <script src="{getScriptsPath}libs/jquery.min.js"></script>
      <script src="{getScriptsPath}theme.js"></script>
      <script src="{getScriptsPath}libs/sidebars.js"></script>
    {/block}
  </body>
</html>
