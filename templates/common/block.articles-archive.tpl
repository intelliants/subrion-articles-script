{if isset($articles_archive)}
	{if $articles_archive}
		<div class="list-group">
			{foreach $articles_archive as $item}
				{assign var='month' value="month{$item.month}"}
				<a class="list-group-item{if (isset($curr_year) && isset($curr_month)) && ($curr_year == $item.year && $curr_month == $item.month)} active{/if}" href="{$item.url}">{lang key=$month} {$item.year}</a>
			{/foreach}
		</div>
	{else}
		<div class="alert alert-info">{lang key='no_articles_in_archive'}</div>
	{/if}
{/if}