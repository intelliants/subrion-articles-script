Ext.onReady(function()
{
	if (Ext.get('js-grid-placeholder'))
	{
		intelli.articles = {
			columns:[
				'selection',
				'expander',
				{name: 'title', title: _t('title'), width: 2, editor: 'text'},
				{name: 'category_title', title: _t('category'), renderer: function(value, metadata, record)
				{
					return (record.data.category_level > 0)
						? '<a href="' + intelli.config.admin_url + '/publishing/categories/edit/' + record.data.category_id + '/">' + value + '</a>'
						: value;

				}, width: 1},
				{name: 'member', title: _t('owner'), width: 100, renderer: function(value, metadata, record)
				{
					return record.data.member_id
						? '<a href="' + intelli.config.admin_url + '/members/edit/' + record.data.member_id + '/">' + value + '</a>'
						: value;
				}},
				{name: 'date_added', title: _t('date_added'), width: 130},
				{name: 'date_modified', title: _t('date_modified'), width: 130},
				'status',
				'update',
				'delete'
			],
			expanderTemplate: '{summary}',
			fields: ['category_level', 'category_id', 'member_id', 'summary'],
			statuses: ['active', 'approval', 'rejected', 'hidden', 'suspended', 'draft', 'pending'],
			texts: {
				delete_multiple: _t('are_you_sure_to_delete_selected_articles'),
				delete_single: _t('are_you_sure_to_delete_selected_article')
			}
		};

		var searchParam = intelli.urlVal('status');
		if (searchParam)
		{
			intelli.articles.storeParams = {status: searchParam};
		}

		intelli.articles = new IntelliGrid(intelli.articles, false);
		intelli.articles.toolbar = new Ext.Toolbar({items:[
		{
			emptyText: _t('title'),
			id: 'fltTitle',
			listeners: intelli.gridHelper.listener.specialKey,
			name: 'title',
			width: 250,
			xtype: 'textfield'
		},{
			emptyText: _t('member'),
			listeners: intelli.gridHelper.listener.specialKey,
			name: 'member',
			width: 150,
			xtype: 'textfield'
		},{
			displayField: 'title',
			editable: false,
			emptyText: _t('status'),
			id: 'fltStatus',
			name: 'status',
			store: intelli.articles.stores.statuses,
			typeAhead: true,
			valueField: 'value',
			width: 100,
			xtype: 'combo'
		},{
			handler: function(){intelli.gridHelper.search(intelli.articles);},
			id: 'fltBtn',
			text: '<i class="i-search"></i> ' + _t('search')
		},{
			handler: function(){intelli.gridHelper.search(intelli.articles, true);},
			text: '<i class="i-close"></i> ' + _t('reset')
		}]});

		if (searchParam)
		{
			Ext.getCmp('fltStatus').setValue(searchParam);
		}

		intelli.articles.init();
	}
});

intelli.titleCache = '';
intelli.fillUrlBox = function()
{
	var alias = $('#input-alias').val();
	var title = ('' == alias ? $('#field_title').val() : alias);
	var category = $('#input-tree').val();
	var cache = title + '%%' + category;
	var id = $('#js-entry-id').val();

	if ('' != title && intelli.titleCache != cache)
	{
		var params = {title: title, category: category};
		if (id != '')
		{
			params.id = id;
		}

		$.getJSON(intelli.config.admin_url + '/publishing/articles/alias.json', params, function(response)
		{
			if (response.data)
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

	$('#field_title, #field_title_alias').blur(intelli.fillUrlBox).blur();
});