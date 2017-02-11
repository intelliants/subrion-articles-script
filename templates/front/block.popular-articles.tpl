{if isset($popular_articles) && $popular_articles}
	<div class="ia-items popular-articles">
		{foreach $popular_articles as $article}
			<div class="ia-item">
				<div class="ia-item__content">
					<h5 class="ia-item__title">{ia_url type='link' item='articles' data=$article text=$article.title}</h5>
					<p>{$article.summary|strip_tags|truncate:150:'...':false}</p>
					<p class="text-fade-50"><span class="fa fa-eye"></span> {$article.views_num} {lang key='views'}</p>
				</div>
			</div>
		{/foreach}
	</div>
{/if}