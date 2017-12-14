{if !empty($random_articles)}
    <div class="ia-items random-articles">
        {foreach $random_articles as $article}
            <div class="ia-item">
                <div class="ia-item__content">
                    <h5 class="ia-item__title">{ia_url type='link' item='articles' data=$article text=$article.title}</h5>
                    <p>{$article.summary|strip_tags|truncate:150:'...':false}</p>
                    {*<p class="text-fade-50"><span class="fa fa-folder"></span> <a href="{ia_url type='url' item='articlecats' data=$article}">{$article.category_title}</a></p>*}
                </div>
            </div>
        {/foreach}
    </div>
{/if}