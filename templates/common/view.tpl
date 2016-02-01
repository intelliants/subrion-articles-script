<div class="ia-item-view {$item.status}{if $item.sponsored} ia-item-sponsored{/if}{if $item.featured == 1} ia-item-featured{/if}" id="article_{$item.id}">
	<div class="ia-item-view__info">
		{if $item.sponsored}<span class="ia-item-view__info__item"><span class="label label-warning">{lang key='sponsored'}</span></span>{/if}
		{if $item.featured}<span class="ia-item-view__info__item"><span class="label label-info">{lang key='featured'}</span></span>{/if}
		<span class="ia-item-view__info__item">
			<span class="fa fa-user"></span>
			{lang key='by'} 
			{if $item.account_username}
				<a href="{ia_url item='members' data=$author type='url'}">{$item.account_fullname}</a>
			{else}
				{lang key='guest'} 
			{/if}
			{lang key='on'} {$item.date_added|date_format:$core.config.date_format}
		</span>
		<span class="ia-item-view__info__item"><span class="fa fa-folder"></span> <a href="{ia_url item='articlecats' data=$item type='url'}">{$item.category_title}</a></span>
		<span class="ia-item-view__info__item"><span class="fa fa-eye"></span> {$item.views_num} {lang key='views'}</span>
	</div>

	{if $item.image}
		<a class="ia-item-view__image center-block m-b" href="{printImage imgfile=$item.image.path url=true fullimage=true}" rel="ia_lightbox[{$item.title}]">
			{printImage imgfile=$item.image.path class='img-responsive' fullimage=true alt=$item.title|escape:'html'}
		</a>
	{/if}

	<div class="ia-item-view__body m-b">{$item.body}</div>

	{if !empty($item.gallery)}
		<div class="ia-item-view__section">
			<h3>{lang key='field_gallery'}</h3>
			{ia_add_media files='fotorama'}
			{$gal = unserialize($item.gallery)}

			<div class="ia-item-view__gallery">
				<div class="fotorama" 
					 data-nav="thumbs"
					 data-width="100%"
					 data-ratio="800/400"
					 data-allowfullscreen="true"
					 data-fit="cover">
					{foreach $gal as $entry}
						<a class="ia-item-view__gallery__item" href="{printImage imgfile=$entry.path url=true fullimage=true}">{printImage imgfile=$entry.path title=$entry.title}</a>
					{/foreach}
				</div>
			</div>
		</div>
	{/if}

	{if $item.url && $item.url != 'http://'}
		<div class="ia-item-view__section">
			<h3>{lang key='field_url'}</h3>
			<a href="{$item.url}" target="_blank">{$item.url}</a><br>
			{$item.url_description}
		</div>
	{/if}

	{if $sections}
		{include file='item-view-tabs.tpl' isView=true exceptions=array('title', 'body', 'url', 'url_description', 'gallery')}
	{/if}

	<!-- simple sharing buttons -->
	<ul class="list-inline share-buttons">
		<li><a href="https://www.facebook.com/sharer/sharer.php?u={$smarty.const.IA_SELF|escape:'url'}&t={$item.title}" target="_blank" title="Share on Facebook"><i class="fa fa-facebook-square fa-2x"></i></a></li>
		<li><a href="https://twitter.com/intent/tweet?source={$smarty.const.IA_SELF|escape:'url'}&text={$item.title}:{$smarty.const.IA_SELF|escape:'url'}" target="_blank" title="Tweet"><i class="fa fa-twitter-square fa-2x"></i></a></li>
		<li><a href="https://plus.google.com/share?url={$smarty.const.IA_SELF|escape:'url'}" target="_blank" title="Share on Google+"><i class="fa fa-google-plus-square fa-2x"></i></a></li>
		{if $item.image}
			<li><a href="http://pinterest.com/pin/create/button/?url={$smarty.const.IA_SELF|escape:'url'}&media={printImage imgfile=$item.image.path url=true fullimage=true}&description={$item.body|strip_tags|truncate:250:'...'}" target="_blank" title="Pin it"><i class="fa fa-pinterest-square fa-2x"></i></a></li>
		{/if}
		<li><a href="mailto:?subject={$item.title}&body={$item.body|strip_tags|truncate:250:'...'}:{$smarty.const.IA_SELF|escape:'url'}" target="_blank" title="Email"><i class="fa fa-envelope-square fa-2x"></i></a></li>
	</ul>

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

{ia_add_js}
$(function() {
	$('.js-delete-article').on('click', function(e) {
		e.preventDefault();

		intelli.confirm(_t('do_you_really_want_to_delete_article'), { url: $(this).attr('href') });
	});
});
{/ia_add_js}