{if isset($top_categories)}
	<ul class="ia-list-items">
		{foreach $top_categories as $top_category}
			<li{if isset($category.id) && $category.id == $top_category.id} class="active"{/if}><a href="{ia_url type='url' item='articlecats' data=$top_category}"{if $top_category.nofollow == '1'} rel="nofollow"{/if}>{$top_category.title|escape:'html'}</a> &mdash; {$top_category.num}</li>
		{/foreach}
	</ul>
{/if}