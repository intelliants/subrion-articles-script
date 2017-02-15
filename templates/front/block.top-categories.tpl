{if isset($top_categories)}
	<div class="list-group">
		{foreach $top_categories as $top_category}
			<a class="list-group-item" href="{ia_url type='url' item='articlecats' data=$top_category}"{if $top_category.nofollow} rel="nofollow"{/if}>
				<span class="badge">{$top_category.num}</span>
				{$top_category.title|escape:'html'}
			</a>
		{/foreach}
	</div>
{/if}