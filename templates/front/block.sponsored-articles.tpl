{if !empty($sponsored_articles)}
    <div class="ia-items sponsored-articles">
        {foreach $sponsored_articles as $article}
            <div class="ia-item ia-item--border-bottom">
                {if $article.image}
                    <a class="center-block m-b" href="{ia_url type='url' item='articles' data=$article}">
                        {ia_image file=$article.image type='thumbnail' title=$article.title class='img-responsive'}
                    </a>
                {/if}

                <div class="ia-item__content">
                    <h5 class="ia-item__title">
                        {ia_url item='articles' type='link' data=$article text=$article.title}
                    </h5>
                    <div class="ia-item__additional">
                        <p>{lang key='on'} {$article.date_added|date_format:$core.config.date_format} <span class="fa fa-folder"></span> <a href="{ia_url type='url' item='articlecats' data=$article}">{$article.category_title}</a></p>
                    </div>

                    <p>{$article.summary|strip_tags|truncate:150:'...':false}</p>
                </div>
            </div>
        {/foreach}
    </div>
{/if}