{if $core.config.articles_categories_selector == 'Handy javascript tree'}
    <div id="tree_fieldzone" class="control-group  fieldzone regular">
        <div class="controls">
            <a href="#" onclick="return false" id="change_cat">{lang key='field_category_id'}
                [<span id="change_cat_title">{if $category.title != 'ROOT'}{$category.title}{else}{lang key='field_category_id_annotation'}{/if}</span>]
            </a>
            <div id="tree" class="tree">{lang key='loading'}</div>
            <input type="hidden" name="category_id" id="category_id" value="{$category.id}">
        </div>
    </div>
    {ia_add_js}
    intelli.categories_source_url = intelli.config.packages.publishing.url + 'add/read.json?a=tree{if isset($admin) && $admin}&h=1{/if}';
    {/ia_add_js}
    {ia_add_media files='jstree, js:_IA_URL_modules/publishing/js/categories-tree'}
    {ia_add_js order='1'}
        intelli.categories = [{if !empty($category.parents)}{$category.parents}, {/if}{if isset($category.id)}{$category.id},{else}{$root_cat.id},{/if}0];
        intelli.categories_parent = {$item.category_id};
        intelli.categories_select = {$item.category_id};
    {/ia_add_js}
{else}
    <label for="field_category_select" class="control-label">{lang key='field_category_id_annotation'}:</label>
    <div class="controls">
        <select name="category_id" id="field_category_select">{$category}</select>
    </div>
{/if}