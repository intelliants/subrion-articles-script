{if isset($sticky_articles)}
	<div class="ia-items sticky-articles">
		{foreach $sticky_articles as $article}
			<div class="media ia-item ia-item-bordered-bottom">
				<div class="media-body">
					<h5 class="media-heading">
						{ia_url item='articles' type='link' data=$article text=$article.title}
					</h5>
					<p class="ia-item-body">{$article.summary|strip_tags|truncate:150:'...':false}</p>
					<p class="ia-item-date">{lang key='on'} {$article.date_added|date_format:$core.config.date_format}</p>
				</div>
			</div>
		{/foreach}
	</div>
{/if}