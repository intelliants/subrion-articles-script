Ext.onReady(function()
{
	if (Ext.get('js-grid-placeholder'))
	{
		intelli.categories = new IntelliGrid(
		{
			columns:[
				'selection',
				'expander',
				{name: 'title', title: _t('title'), width: 1, editor: 'text'},
				{name: 'parent_title', title: _t('parent_category'), renderer: function(value, metadata, record)
				{
					return (record.data.level <= 1)
						? value
						: '<a href="' + window.location.href + 'edit/' + record.data.parent_id + '/">' + value + '</a>'
				}, width: 1},
				{name: 'title_alias', title: _t('title_alias'), width: 1},
				{name: 'num_articles', title: _t('num_articles'), width: 40},
				{name: 'num_all_articles', title: _t('all'), width: 40},
				{name: 'order', title: _t('order'), width: 50, editor: 'number'},
				'status',
				'update',
				'delete'
			],
			expanderTemplate: '{description}',
			fields: ['description', 'parent_id', 'level'],
			texts: {
				delete_multiple: _t('are_you_sure_to_delete_selected_articlecats'),
				delete_single: _t('are_you_sure_to_delete_selected_articlecat')
			}
		}, false);

		intelli.categories.toolbar = new Ext.Toolbar({items:[
		{
			emptyText: _t('title'),
			listeners: intelli.gridHelper.listener.specialKey,
			name: 'title',
			width: 250,
			xtype: 'textfield'
		}, {
			displayField: 'title',
			editable: false,
			emptyText: _t('status'),
			name: 'status',
			store: intelli.categories.stores.statuses,
			typeAhead: true,
			valueField: 'value',
			width: 100,
			xtype: 'combo'
		}, {
			handler: function(){intelli.gridHelper.search(intelli.categories);},
			id: 'fltBtn',
			text: '<i class="i-search"></i> ' + _t('search')
		}, {
			handler: function(){intelli.gridHelper.search(intelli.categories, true);},
			text: '<i class="i-close"></i> ' + _t('reset')
		}]});

		intelli.categories.init();
	}
});

intelli.titleCache = '';
intelli.fillUrlBox = function()
{
	var alias = $('#input-alias').val();
	var title = ('' == alias ? $('#field_title').val() : alias);
	var category = $('#input-tree').val();
	var cache = title + '%%' + category;

	if ('' != title && intelli.titleCache != cache)
	{
		var params = {title: title, category: category};
		if ('' != alias)
		{
			params.alias = 1;
		}

		$.getJSON(intelli.config.admin_url + '/publishing/categories/alias.json', params, function(response)
		{
			if ('' != response.data)
			{
				$('#js-url-preview')
					.html(response.data + (response.exists ? ' <b style="color:red">' + response.exists + '</b>' : ''))
					.fadeIn();
			}
		});
	}

	intelli.titleCache = cache;
};


$(function()
{
	$('#field_title').keyup(function()
	{
		$('#field-title-alias').show();
	});

	$('#field_title, #input-alias').blur(intelli.fillUrlBox).blur();

	// auto save for publishing
	$('#main-form').sisyphus(
	{
		onRestore: function()
		{
			if (typeof CKEDITOR != 'undefined')
			{
				CKEDITOR.instances.description.setData($('textarea[name="description"]').val());
			}
		},
		onSave: function()
		{
			if (typeof CKEDITOR != 'undefined')
			{
				$('textarea[name="description"]').val(CKEDITOR.instances.description.getData());
			}
		},
		timeout: 15,
		excludeFields: $('input:file, input:hidden, input:disabled')
	});
});