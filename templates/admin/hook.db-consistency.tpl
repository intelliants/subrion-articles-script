<div class="row">
    <label class="col col-lg-2 control-label">{lang key='repair_articlecats'}</label>
    <div class="col col-lg-1">
        <button class="btn btn-success btn-small" name="type" value="repair_article_categories">{lang key='start'}</button>
    </div>
</div>
<div class="row">
    <label class="col col-lg-2 control-label">{lang key='rebuild_articlecats_paths'}</label>
    <div class="col col-lg-1">
        <button class="btn btn-success btn-small js-repair" data-action="rebuild_articlecats_paths" data-pre="pre_repair_articlecats_paths">{lang key='start'}</button>
    </div>
    <div class="col col-lg-2">
        <div class="progress progress-striped hidden js-repair-progress">
            <div class="progress-bar progress-bar-success" style="width: 0"></div>
        </div>
    </div>
</div>
<div class="row">
    <label class="col col-lg-2 control-label">{lang key='repair_articlecats_num'}</label>
    <div class="col col-lg-1">
        <button class="btn btn-success btn-small js-repair" data-action="recount_counters" data-pre="pre_recount_counters">{lang key='start'}</button>
    </div>
    <div class="col col-lg-2">
        <div class="progress progress-striped hidden js-repair-progress">
            <div class="progress-bar progress-bar-success" style="width: 0"></div>
        </div>
    </div>
</div>
<div class="row">
    <label class="col col-lg-2 control-label">{lang key='rebuild_article_paths'}</label>
    <div class="col col-lg-1">
        <button class="btn btn-success btn-small js-repair" data-action="rebuild_article_paths" data-pre="pre_rebuild_article_paths">{lang key='start'}</button>
    </div>
    <div class="col col-lg-2">
        <div class="progress progress-striped hidden js-repair-progress">
            <div class="progress-bar progress-bar-success" style="width: 0"></div>
        </div>
    </div>
</div>
{ia_print_js files='_IA_URL_modules/publishing/js/admin/consistency'}