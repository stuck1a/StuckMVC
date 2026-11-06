{extends 'layout/layout.tpl'}
{block 'base'}
  {include_widget name=breadcrumb}
  {foreach $flashes as $flash}
    <p class="notification notification-{$flash.type}" role="{if $flash.type == 'error' || $flash.type == 'warning'}alert{else}status{/if}">{$flash.message}</p>
  {/foreach}
  <main id="main-content" tabindex="-1">{block 'content'}{/block}</main>
{/block}
