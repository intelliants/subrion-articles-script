{if isset($priority_categories)}
	<div class="list-group">
		{foreach $priority_categories as $priority_category}
			<a class="list-group-item" href="{ia_url type='url' item='articlecats' data=$priority_category}"{if $priority_category.nofollow == '1'} rel="nofollow"{/if}>
				<span class="badge">{$priority_category.num}</span>
				{$priority_category.title|escape:'html'}
			</a>

			{if $priority_category.articles}
				<div class="ia-items featured-articles">
					{foreach $priority_category.articles as $article}
						<div class="ia-item ia-item--border-bottom">
							{$imgthumb = $article.image}
							{if $imgthumb}
								<a class="center-block m-b" href="{ia_url type='url' item='articles' data=$article}">
									{printImage imgfile=$imgthumb.path title=$article.title class='img-responsive'}
								</a>
							{/if}

							<div class="ia-item__content">
								<h5 class="ia-item__title">
									{ia_url item='articles' type='link' data=$article text=$article.title}
								</h5>
								<div class="ia-item__additional">
									<p>{lang key='on'} {$article.date_added|date_format:$core.config.date_format} <span class="fa fa-folder"></span> <a href="{ia_url type='url' item='articlecats' data=$article}">{$article.category_title}</a></p>
								</div>
								
								<p>{$article.summary|strip_tags|truncate:150:'...':false}</p>
							</div>
						</div>
					{/foreach}
				</div>
			{/if}
		{/foreach}
	</div>
{/if}