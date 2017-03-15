<form id="article_data" method="post" enctype="multipart/form-data" class="ia-form">
	{preventCsrf}

	{capture name='general' append='fieldset_before'}
		{include 'plans.tpl'}
	{/capture}

	{capture name='title' append='field_before'}
		<div class="form-group">
			{if $core.config.articles_categories_selector == 'Handy javascript tree'}
				{include 'tree.tpl' url="{$core.packages.publishing.url}add.json"}
			{else}
				<label for="field_category_select">{lang key='field_category_id_annotation'}:</label>
				<select class="form-control" name="category_id" id="field_category_select">{$categories}</select>
			{/if}
		</div>
	{/capture}

	{capture append='fieldset_after' name='general'}
		{include 'captcha.tpl'}
	{/capture}

	{capture append='tabs_after' name='__all__'}
		<div class="fieldset__actions">
			<button type="submit" class="btn btn-primary" name="data-article">{lang key='save'}</button>
			{if $member}
				<button type="submit" name="draft" class="btn btn-default">{lang key='save_as_draft'}</button>
			{/if}
			{if iaCore::ACTION_EDIT == $pageAction}
				<a href="{$smarty.const.IA_URL}delete/{$item.id}" class="btn btn-danger js-delete-article">{lang key='delete'}</a>
			{/if}
		</div>
	{/capture}

	{include 'item-view-tabs.tpl'}
</form>
{ia_add_media files='js:_IA_URL_modules/publishing/js/jquery.sisyphus.min'}
{ia_add_js}
$(function()
{
	$('.js-delete-article').on('click', function(e)
	{
		e.preventDefault();

		intelli.confirm(_t('do_you_really_want_to_delete_article'), { url: $(this).attr('href') });
	});

	console.log(intelli.config.lang)

	$('#article_data').sisyphus(
	{
		onRestore: function(){ CKEDITOR.instances['body[' + intelli.config.lang + ']'].setData($('textarea[name="body[' + intelli.config.lang + ']"]').val()); },
		onSave: function(){ $('textarea[name="body[' + intelli.config.lang + ']"]').val(CKEDITOR.instances['body[' + intelli.config.lang + ']'].getData()); },
		timeout: 30,
		excludeFields: $('input:file, #securityCode')
	});
});
{/ia_add_js}