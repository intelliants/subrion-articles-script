Ext.onReady(function()
{
	if (Ext.get('js-grid-placeholder'))
	{
		var grid = new IntelliGrid(
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
				{name: 'locked', title: _t('locked'), width: 60, align: intelli.gridHelper.constants.ALIGN_CENTER, renderer: intelli.gridHelper.renderer.check, editor: Ext.create('Ext.form.ComboBox',
				{
					typeAhead: false,
					editable: false,
					lazyRender: true,
					store: Ext.create('Ext.data.SimpleStore', {fields: ['value','title'], data: [[0, _t('no')],[1, _t('yes')]]}),
					displayField: 'title',
					valueField: 'value'
				})},
				{name: 'num_articles', title: _t('num_articles'), width: 40},
				{name: 'num_all_articles', title: _t('all'), width: 40},
				{name: 'order', title: _t('order'), width: 50, editor: 'number'},
				{name: 'date_added', title: _t('date_added'), width: 100, hidden: true},
				{name: 'date_modified', title: _t('date_modified'), width: 100, hidden: true},
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

		grid.toolbar = new Ext.Toolbar({items:[
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
			store: grid.stores.statuses,
			typeAhead: true,
			valueField: 'value',
			width: 100,
			xtype: 'combo'
		}, {
			handler: function(){intelli.gridHelper.search(grid);},
			id: 'fltBtn',
			text: '<i class="i-search"></i> ' + _t('search')
		}, {
			handler: function(){intelli.gridHelper.search(grid, true);},
			text: '<i class="i-close"></i> ' + _t('reset')
		}]});

		grid.init();
	}
	else
	{
		$('#field_articlecats_title').keyup(function()
		{
			$('#field-title-alias').show();
		});

		$('#field_articlecats_title, #input-alias').blur(intelli.fillUrlBox).blur();
	}
});

intelli.titleCache = '';
intelli.fillUrlBox = function()
{
	var alias = $('#input-alias').val();
	var title = ('' == alias ? $('#field_articlecats_title').val() : alias);
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