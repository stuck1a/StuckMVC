{extends 'layout/main.tpl'}
{block 'title'}Unknown error{/block}
{block 'content'}<section><h1>{$status} – Something went wrong</h1><p>The request could not be processed. Please try again later.</p><a href="{url}">Back to the start page</a></section>{/block}
