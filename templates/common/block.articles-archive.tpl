{if isset($articles_archive)}
	{if $articles_archive}
		<ul class="ia-list-items">
			{foreach $articles_archive as $item}
				{assign var='month' value="month{$item.month}"}
				<li {if (isset($curr_year) && isset($curr_month)) && ($curr_year == $item.year && $curr_month == $item.month)}class="active"{/if}>
					<i class="icon-calendar"></i> <a href="{$item.url}">{lang key=$month} {$item.year}</a>
				</li>
			{/foreach}
		</ul>
	{else}
		<div class="alert alert-info">{lang key='no_articles_in_archive'}</div>
	{/if}
{/if}