{if isset($most_recent_articles)}
	<ul class="unstyled most-recent-articles">
		{foreach $most_recent_articles as $article}
			<li><span class="label">{$article.date_added|date_format}</span> <a href="{ia_url type='url' item='articles' data=$article}">{$article.title}</a> <span class="help-inline">by {if $article.account_fullname}{$article.account_fullname}{else}{lang key='guest'}{/if}</span></li>
		{/foreach}
	</ul>
{else}
	<p>{lang key='no_articles'}</p>
{/if}