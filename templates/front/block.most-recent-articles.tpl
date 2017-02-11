{if isset($most_recent_articles)}
	<ul class="list-unstyled most-recent-articles">
		{foreach $most_recent_articles as $article}
			<li><span class="label label-info">{$article.date_added|date_format}</span> <a href="{ia_url type='url' item='articles' data=$article}">{$article.title}</a> <span class="text-i text-fade-50">by {if $article.account_fullname}{$article.account_fullname}{else}{lang key='guest'}{/if}</span></li>
		{/foreach}
	</ul>
{else}
	<p>{lang key='no_articles'}</p>
{/if}