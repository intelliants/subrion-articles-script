{if isset($featured_articles) && $featured_articles}
	<div class="ia-items featured-articles">
		{foreach $featured_articles as $article}
			<div class="media ia-item ia-item-bordered-bottom">
				<div class="media-body">
					<h5 class="media-heading">
						{ia_url item='articles' type='link' data=$article text=$article.title}
					</h5>
					<p class="ia-item-date">{lang key='on'} {$article.date_added|date_format:$core.config.date_format} <br> <i class="icon-folder-close"></i> <a href="{ia_url type='url' item='articlecats' data=$article}">{$article.category_title}</a></p>
					{$imgthumb = $article.image}
					{if $imgthumb}
						<a class="pull-right" href="{ia_url type='url' item='articles' data=$article}">
							{printImage imgfile=$imgthumb.path title=$article.title class='media-object'}
						</a>
					{/if}
					<p class="ia-item-body">{$article.summary|strip_tags|truncate:150:'...':false}</p>
				</div>
			</div>
		{/foreach}
	</div>
{/if}