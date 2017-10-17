{if !empty($sticky_articles)}
    <div class="ia-items sticky-articles">
        {foreach $sticky_articles as $article}
            <div class="ia-item ia-item--border-bottom">
                <div class="ia-item__content">
                    <h5 class="ia-item__title">{ia_url item='articles' type='link' data=$article text=$article.title}</h5>
                    <p>{$article.summary|strip_tags|truncate:150:'...':false}</p>
                    <p class="text-fade-50">{lang key='on'} {$article.date_added|date_format}</p>
                </div>
            </div>
        {/foreach}
    </div>
{/if}