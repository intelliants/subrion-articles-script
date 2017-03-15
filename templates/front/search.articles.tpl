{if !empty($listings)}
	<div class="ia-items ia-items--cards">
		{foreach $listings as $listing}
			{include 'extra:publishing/list-articles'}
		{/foreach}
	</div>
{else}
	<div class="alert alert-info">{lang key='no_articles'}</div>
{/if}