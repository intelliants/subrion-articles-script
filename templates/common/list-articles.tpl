<div id="article_{$listing.id}" class="media ia-item ia-item-bordered {$listing.status}{if $listing.sponsored} ia-item-sponsored{/if}{if $listing.featured} ia-item-featured{/if}">
	{if $listing.featured}<span class="ia-badge ia-badge-featured" title="{lang key='featured'}"><i class="icon-star"></i></span>{/if}
	{if $listing.sponsored}<span class="ia-badge ia-badge-sponsored" title="{lang key='sponsored'}"><i class="icon-dollar"></i></span>{/if}
	{if $member && $member.id == $listing.member_id && iaCore::STATUS_ACTIVE != $listing.status}
		<span class="ia-badge ia-badge-{$listing.status}" title="{lang key=$listing.status default=$listing.status}"><i class="icon-warning-sign"></i></span>
	{/if}

	{if $listing.image}
		<a class="pull-left" href="{ia_url type='url' item='articles' data=$listing}">
			{printImage imgfile=$listing.image.path title=$listing.title width=150 class='media-object'}
		</a>
	{/if}

	<div class="media-body">
		<h3 class="media-heading">
			{ia_url item='articles' type='link' data=$listing text=$listing.title}
		</h3>

		<p class="ia-item-date">
			{lang key='by'}
			{if $listing.account_username}
				<a href="{$smarty.const.IA_URL}member/{$listing.account_username}.html">{$listing.account_fullname}</a>
			{else}
				{lang key='guest'}
			{/if}
			{lang key='on'} {$listing.date_added|date_format:$core.config.date_format}
		</p>

		<p class="ia-item-body">{$listing.summary} <a href="{ia_url type='url' item='articles' data=$listing}">{lang key='continue_reading'}</a></p>
	</div>

	<div class="ia-item-panel">
		{ia_url type='icon' item='articles' data=$listing classname='btn-info pull-left'}
		{printFavorites item=$listing itemtype='articles' classname='pull-left'}
		{if !empty($listing.transaction_id)}
			<a rel="nofollow" href="{$smarty.const.IA_URL}pay/{$listing.transaction_id}/" class="btn btn-small btn-danger pull-right"><i class="icon-usd"></i> {lang key='pay'}</a>
		{/if}
		{accountActions item=$listing itemtype='articles' classname='btn-info pull-right'}

		{if 'publishing_home' != $core.page.name && $listing.category_title}
			<span class="panel-item pull-left"><i class="icon-folder-close"></i> <a href="{ia_url type='url' item='articlecats' data=$listing}">{$listing.category_title}</a></span> 
		{/if}

		<span class="panel-item pull-right"><i class="icon-eye-open"></i> {$listing.views_num} {if 1 == $listing.views_num}{lang key='view'}{else}{lang key='views'}{/if}</span>
	</div>
</div>