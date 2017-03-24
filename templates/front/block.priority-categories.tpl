{if !empty($priority_categories)}
    {foreach $priority_categories as $priority_category}
        <h4{if !$priority_category@first} class="m-t-md"{/if}>
            <span class="badge pull-right">{$priority_category.num}</span>
            <a href="{ia_url type='url' item='articlecats' data=$priority_category}"{if $priority_category.nofollow == '1'} rel="nofollow"{/if}>{$priority_category.title|escape}</a>
        </h4>

        {if $priority_category.articles}
            <div class="ia-items">
                {foreach $priority_category.articles as $article}
                    <div class="ia-item ia-item--border">
                        {if $article.image}
                            <a class="ia-item__image" href="{ia_url type='url' item='articles' data=$article}">
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
    {/foreach}
{/if}