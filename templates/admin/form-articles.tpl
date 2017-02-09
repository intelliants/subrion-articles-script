<form method="post" enctype="multipart/form-data" class="sap-form form-horizontal">
	{preventCsrf}
	<input type="hidden" id="js-entry-id" value="{$id|default:''}">

	{capture name='general' append='fieldset_before'}
		{include 'tree.tpl' url="{$smarty.const.IA_ADMIN_URL}publishing/categories/tree.json"}
	{/capture}

	{capture name='title' append='field_after'}
		<div class="row" id="field-title-alias"{if iaCore::ACTION_EDIT != $pageAction && empty($smarty.post.save)} style="display: none;"{/if}>
			<label class="col col-lg-2 control-label" for="input-alias">{lang key='title_alias'}</label>

			<div class="col col-lg-4">
				<input type="text" name="title_alias" id="input-alias" value="{if isset($item.title_alias)}{$item.title_alias|escape:'html'}{/if}">
				<p class="help-block text-break-word">{lang key='page_url_will_be'}: <span class="text-danger" id="js-url-preview">{$smarty.const.IA_URL}</span></p>
			</div>
		</div>
	{/capture}

	{capture name='systems' append='fieldset_before'}
		<div class="row">
			<label class="col col-lg-2 control-label">{lang key='sticky'}</label>
			<div class="col col-lg-4">
				{html_radio_switcher value=$item.sticky name='sticky'}
			</div>
		</div>
	{/capture}

	{ia_hooker name='smartyAdminSubmitItemBeforeFields'}

	{include file='field-type-content-fieldset.tpl' isSystem=true}
</form>
{ia_hooker name='smartyAdminSubmitItemBeforeFooter'}
{ia_add_media files='js:_IA_URL_packages/publishing/js/jquery.sisyphus.min, js:_IA_URL_packages/publishing/js/admin/articles'}