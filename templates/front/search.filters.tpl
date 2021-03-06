{if isset($publishingFiltersCategories)}
    <div class="form-group">
        <label>{lang key='keywords'}</label>
        <input type="text" name="keywords" placeholder="{lang key='keywords'}" class="form-control"{if isset($filters.params.keywords)} value="{$filters.params.keywords|escape}"{/if}>
    </div>
    <div class="form-group">
        <label>{lang key='category'}</label>
        <select name="c" class="form-control no-js" id="js-a-c">
            <option value="">{lang key='any'}</option>
            {foreach $publishingFiltersCategories as $entry}
                <option value="{$entry.id}"{if isset($filters.params.c) && $filters.params.c == $entry.id} selected{/if}>{$entry.title|escape}</option>
            {/foreach}
        </select>
    </div>
    <div class="form-group">
        <label>{lang key='subcategory'}</label>
        <select name="sc" class="form-control no-js" id="js-a-sc" disabled{if !empty($filters.params.sc)} data-id="{$filters.params.sc|intval}"{/if}>
            <option value="">{lang key='any'}</option>
        </select>
    </div>
    {ia_print_js files='_IA_URL_modules/publishing/js/front/filters'}
{/if}