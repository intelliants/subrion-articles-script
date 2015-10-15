{if isset($author_articles) && $author_articles}
	<ul class="unstyled author-articles">
		{foreach $author_articles as $article}
			<li><span class="label">{$article.date_added|date_format}</span> {ia_url type='link' item='articles' data=$article text=$article.title}</li>
		{/foreach}
	</ul>
{/if}