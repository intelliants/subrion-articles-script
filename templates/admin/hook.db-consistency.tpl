<div class="row">
	<label class="col col-lg-2 control-label">{lang key='repair_articlecats'}</label>
	<div class="col col-lg-1">
		<button class="btn btn-success btn-small" id="repair-articlecats">{lang key='start'}</button>
	</div>
	<div class="col col-lg-2">
		<div class="progress progress-striped hidden" id="repair-articlecats-progress">
			<div class="progress-bar progress-bar-success" style="width: 0"></div>
		</div>
	</div>
</div>
<div class="row">
	<label class="col col-lg-2 control-label">{lang key='rebuild_articlecats_paths'}</label>
	<div class="col col-lg-4">
		<button class="btn btn-success btn-small" name="type" value="rebuild_articlecats_paths">{lang key='start'}</button>
	</div>
</div>
<div class="row">
	<label class="col col-lg-2 control-label">{lang key='repair_articlecats_num'}</label>
	<div class="col col-lg-4">
		<button class="btn btn-success btn-small" name="type" value="repair_articlecats_num">{lang key='start'}</button>
	</div>
</div>
{ia_print_js files='_IA_URL_packages/publishing/js/admin/consistency'}