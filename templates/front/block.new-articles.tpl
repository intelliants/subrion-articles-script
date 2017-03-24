{if !('publishing_home' == $core.page.name && '1' != $category.id)}
    <h2 class="page-header">{lang key='new_articles'}</h2>
    {if $new_articles}
        <div class="new-articles">
            {foreach $new_articles as $listing}
                {include 'extra:publishing/list-articles'}
            {/foreach}
        </div>
    {else}
        <div class="alert alert-info">{lang key='no_articles'}</div>
    {/if}
{/if}