{if !empty($related_articles)}
	<ul class="list-unstyled related-articles">
		{foreach $related_articles as $article}
			<li><span class="label label-info">{$article.date_added|date_format}</span> <a href="{ia_url type='url' item='articles' data=$article}">{$article.title}</a> <span class="text-fade-50">by {if $article.account_fullname}{$article.account_fullname}{else}{lang key='guest'}{/if}</span></li>
		{/foreach}
	</ul>
{/if}