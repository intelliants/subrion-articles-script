<div id="article_{$item.id}" class="media ia-item ia-item-view {$item.status}{if $item.sponsored} ia-item-sponsored{/if}{if $item.featured == 1} ia-item-featured{/if}">
	{if $item.featured}<span class="ia-badge ia-badge-featured" title="{lang key='featured'}"><i class="icon-asterisk"></i></span>{/if}
	{if $item.sponsored}<span class="ia-badge ia-badge-sponsored" title="{lang key='sponsored'}"><i class="icon-dollar"></i></span>{/if}

	<p class="ia-item-date">
		{lang key='by'} 
		{if $item.account_username}
			<a href="{ia_url item='members' data=$author type='url'}">{$item.account_fullname}</a>
		{else}
			{lang key='guest'} 
		{/if}
		{lang key='on'} {$item.date_added|date_format:$core.config.date_format}
	</p>

	{assign 'imgthumb' $item.image}
	{if $imgthumb}
		<div class="thumbnail pull-right">
			<a href="{printImage imgfile=$imgthumb.path url=true fullimage=true}" rel="ia_lightbox[{$item.title}]">
				{printImage imgfile=$imgthumb.path alt=$item.title|escape:'html'}
			</a>
		</div>
	{/if}

	<div class="ia-item-body">{$item.body}</div>

	{if $item.url && $item.url != 'http://'}
		<div class="url well-light">
			<a href="{$item.url}" target="_blank">{$item.url}</a><br>
			{$item.url_description}
		</div>
	{/if}

	{if $sections}
		{include file='item-view-tabs.tpl' isView=true exceptions=array('title', 'body', 'url', 'url_description')}
	{/if}

	<!-- AddThis Button BEGIN -->
	<div class="addthis_toolbox addthis_default_style">
		<a class="addthis_button_facebook_like" fb:like:layout="button_count"></a>
		<a class="addthis_button_tweet"></a>
		<a class="addthis_button_google_plusone" g:plusone:size="medium"></a>
		<a class="addthis_counter addthis_pill_style"></a>
	</div>
	<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#username=xa-4c6e050a3d706b83"></script>
	<!-- AddThis Button END -->

	<div class="ia-item-panel">
		<span class="panel-item pull-left"><i class="icon-folder-close"></i> <a href="{ia_url item='articlecats' data=$item type='url'}">{$item.category_title}</a></span>
		<span class="panel-item pull-left"><i class="icon-eye-open"></i> {$item.views_num} {lang key='views'}</span>

		{printFavorites item=$item itemtype='articles' classname='pull-left'}
		{accountActions item=$item itemtype='articles' classname='btn-info pull-right'}
	</div>

	{if !empty($item.prev_article) || !empty($item.next_article)}
		<ul class="pager">
			{if !empty($item.prev_article)}
				<li class="pull-left text-small">
					<a href="{ia_url item='articles' data=$item.prev_article type='url'}" title="{$item.prev_article.title}">&larr; {lang key='previous_article'}</a>
				</li>
			{/if}
			{if !empty($item.next_article)}
				<li class="pull-right text-small">
					<a href="{ia_url item='articles' data=$item.next_article type='url'}" title="{$item.next_article.title}">{lang key='next_article'} &rarr;</a>
				</li>
			{/if}
		</ul>
	{/if}

	<!-- display author adsense -->
	{if $core.config.articles_allow_adsense_code && isset($author.adsense_id) && $author.adsense_id}
		<div class="box">
			<script type="text/javascript">
			<!--
				google_ad_client = "{$author.adsense_id}";
				google_ad_width = 468;
				google_ad_height = 60;
			-->
			</script>
			<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
		</div>
	{/if}

</div>

{ia_hooker name='smartyViewListingBeforeFooter'}