{*
 * Minimal base layout of the 'classic' starter theme.
 * Blocks: title, head, styles, scripts, base (page body)
*}
{assign var=cssRoot value={getStylesPath}}
{assign var=jsRoot  value={getScriptsPath}}
<!DOCTYPE html>
<html lang="en">
  <head>
    {block 'head'}
      <title>{block 'title'}{#title#} | {$smarty.block.child}{/block}</title>
      <meta charset="{#metaEncoding#}">
      <meta name="author" content="{#metaAuthor#}">
      <meta name="description" content="{if $currentRoute && $currentRoute->description}{$currentRoute->description}{else}{#metaDescription#}{/if}">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      {block 'styles'}<link rel="stylesheet" href="{$cssRoot}theme.css">{/block}
      {block 'scripts'}{/block}
    {/block}
  </head>
  <body>
    {block 'viewport'}
      {include 'layout/header.tpl'}
      {block 'base'}{/block}
      {include 'layout/footer.tpl'}
    {/block}
  </body>
</html>
