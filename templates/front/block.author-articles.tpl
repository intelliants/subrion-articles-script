{if !empty($author_articles)}
	<ul class="list-unstyled author-articles">
		{foreach $author_articles as $article}
			<li><span class="label label-success">{$article.date_added|date_format}</span> {ia_url type='link' item='articles' data=$article text=$article.title}</li>
		{/foreach}
	</ul>
{/if}