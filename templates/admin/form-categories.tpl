<form method="post" enctype="multipart/form-data" class="sap-form form-horizontal">
	{preventCsrf}

	{if 0 != $item.parent_id}
		{capture name='general' append='fieldset_before'}
			{include file='tree.tpl' url="{$smarty.const.IA_ADMN_URL}publishing/categories/tree.json"}
		{/capture}

		{capture name='title' append='field_after'}
			<div class="row" id="field-title-alias"{if iaCore::ACTION_EDIT != $pageAction && empty($smarty.post.save)} style="display: none;"{/if}>
				<label class="col col-lg-2 control-label" for="input-alias">{lang key='title_alias'}</label>

				<div class="col col-lg-4">
					<input type="text" name="title_alias" id="input-alias" value="{if isset($item.title_alias)}{$item.title_alias}{/if}">
					<p class="help-block text-break-word">{lang key='page_url_will_be'}: <span class="text-danger" id="js-url-preview">{$smarty.const.IA_URL}</span></p>
				</div>
			</div>
		{/capture}
	{else}
		<input type="hidden" name="parent_id" id="parent_id" value="0">
	{/if}

	{capture name='systems' append='fieldset_before'}
		<div class="row">
			<label class="col col-lg-2 control-label">{lang key='priority'}</label>

			<div class="col col-lg-4">
				{html_radio_switcher name='priority' value=$item.priority|default:0}
			</div>
		</div>

		<div class="row">
			<label class="col col-lg-2 control-label">{lang key='enable_no_follow'}</label>

			<div class="col col-lg-4">
				{html_radio_switcher value=$item.nofollow name='nofollow'}
			</div>
		</div>
	{/capture}

	{include file='field-type-content-fieldset.tpl' isSystem=true}
</form>
{ia_hooker name='smartyAdminSubmitListingBeforeFooter'}
{ia_add_media files='js:_IA_URL_packages/publishing/js/jquery.sisyphus.min, js:_IA_URL_packages/publishing/js/admin/categories'}