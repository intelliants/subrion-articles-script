{if isset($most_viewed_articles)}
    <ul class="list-unstyled most-viewed-articles">
        {foreach $most_viewed_articles as $article}
            <li><span class="label label-warning"><span class="fa fa-star"></span> {$article.views_num} hits</span> <a href="{ia_url type='url' item='articles' data=$article}">{$article.title}</a> <span class="text-i text-fade-50">by {if $article.account_fullname}{$article.account_fullname}{else}{lang key='guest'}{/if}</span></li>
        {/foreach}
    </ul>
{else}
    <p>{lang key='no_articles'}</p>
{/if}