Ext.onReady(function () {
    if (Ext.get('js-grid-placeholder')) {
        var grid = new IntelliGrid(
            {
                columns: [
                    'selection',
                    'expander',
                    {name: 'title', title: _t('title'), width: 1, editor: 'text'},
                    {
                        name: 'category_title', title: _t('category'), renderer: function (value, metadata, record) {
                        return (record.data.category_level > 0)
                            ? '<a href="' + intelli.config.admin_url + '/publishing/categories/edit/' + record.data.category_id + '/">' + value + '</a>'
                            : value;

                    }, width: 1
                    },
                    {
                        name: 'member', title: _t('owner'), width: 150, renderer: function (value, metadata, record) {
                        return record.data.member_id
                            ? '<a href="' + intelli.config.admin_url + '/members/edit/' + record.data.member_id + '/">' + value + '</a>'
                            : value;
                    }
                    },
                    {name: 'date_added', title: _t('date_added'), width: 170, hidden: true},
                    {name: 'date_modified', title: _t('date_modified'), width: 170},
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
            }, false);

        grid.toolbar = new Ext.Toolbar({
            items: [
                {
                    emptyText: _t('title'),
                    id: 'fltTitle',
                    listeners: intelli.gridHelper.listener.specialKey,
                    name: 'title',
                    width: 250,
                    xtype: 'textfield'
                }, {
                    emptyText: _t('member'),
                    listeners: intelli.gridHelper.listener.specialKey,
                    name: 'member',
                    width: 150,
                    xtype: 'textfield'
                }, {
                    displayField: 'title',
                    editable: false,
                    emptyText: _t('status'),
                    id: 'fltStatus',
                    name: 'status',
                    store: grid.stores.statuses,
                    typeAhead: true,
                    valueField: 'value',
                    width: 100,
                    xtype: 'combo'
                }, {
                    handler: function () {
                        intelli.gridHelper.search(grid);
                    },
                    id: 'fltBtn',
                    text: '<i class="i-search"></i> ' + _t('search')
                }, {
                    handler: function () {
                        intelli.gridHelper.search(grid, true);
                    },
                    text: '<i class="i-close"></i> ' + _t('reset')
                }]
        });

        grid.init();
    }
});

intelli.titleCache = '';
intelli.fillUrlBox = function () {
    var alias = $('#input-alias').val();
    var title = ('' === alias ? $('#field_article_title').val() : alias);
    var category = $('#input-tree').val();
    var cache = title + '%%' + category;
    var id = $('#js-entry-id').val();

    if ('' !== title && intelli.titleCache !== cache) {
        var params = {title: title, category: category};
        if (id != '') {
            params.id = id;
        }

        $.getJSON(intelli.config.admin_url + '/publishing/articles/slug.json', params, function (response) {
            if (response.data) {
                $('#js-url-preview')
                    .html(response.data + (response.exists ? ' <b style="color:red">' + response.exists + '</b>' : ''))
                    .fadeIn();
            }
        });
    }

    intelli.titleCache = cache;
};

$(function () {
    $('#field_article_title').keyup(function () {
        $('#field-title-alias').show();
    });

    $('#field_article_title, #input-alias').blur(intelli.fillUrlBox).blur();
});