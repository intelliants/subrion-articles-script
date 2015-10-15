{if isset($random_articles) && $random_articles}
	<div class="ia-items random-articles">
		{foreach $random_articles as $article}
			<div class="media ia-item ia-item-bordered-bottom">
				<div class="media-body">
					<h5 class="media-heading"><a href="{ia_url type='url' item='articles' data=$article}">{$article.title}</a></h5>
					<p class="ia-item-body">{$article.summary|strip_tags|truncate:150:'...':false}</p>
					<p class="ia-item-date"><i class="icon-folder-close"></i> <a href="{ia_url type='url' item='articlecats' data=$article}">{$article.category_title}</a></p>
				</div>
			</div>
		{/foreach}
	</div>
{/if}