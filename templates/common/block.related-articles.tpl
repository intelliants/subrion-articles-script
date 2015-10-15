{if isset($related_articles)}
	<ul class="related-articles">
		{foreach $related_articles as $article}
			<li><span class="label">{$article.date_added|date_format}</span> <a href="{ia_url type='url' item='articles' data=$article}">{$article.title}</a> <span class="author">by {if $article.account_fullname}{$article.account_fullname}{else}{lang key='guest'}{/if}</span></li>
		{/foreach}
	</ul>
{/if}