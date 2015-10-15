{if isset($most_viewed_articles)}
	<ul class="unstyled most-viewed-articles">
		{foreach $most_viewed_articles as $article}
			<li><span class="label label-warning"><i class="icon icon-star"></i> {$article.views_num} hits</span> <a href="{ia_url type='url' item='articles' data=$article}">{$article.title}</a> <span class="help-inline">by {if $article.account_fullname}{$article.account_fullname}{else}{lang key='guest'}{/if}</span></li>
		{/foreach}
	</ul>
{else}
	<p>{lang key='no_articles'}</p>
{/if}