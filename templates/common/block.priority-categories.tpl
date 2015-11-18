{if isset($priority_categories)}
	<div class="list-group">
		{foreach $priority_categories as $priority_category}
			<a class="list-group-item" href="{ia_url type='url' item='articlecats' data=$priority_category}"{if $priority_category.nofollow == '1'} rel="nofollow"{/if}>
				<span class="badge">{$priority_category.num}</span>
				{$priority_category.title|escape:'html'}
			</a>
		{/foreach}
	</div>
{/if}