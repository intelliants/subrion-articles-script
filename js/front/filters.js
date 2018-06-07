$(function () {
    var $cSelect = $('#js-a-c'),
        $scSelect = $('#js-a-sc');

    $cSelect.on('change', function (e) {
        var value = $(this).val();

        $scSelect.val(0).prop('disabled', true).find('option:not(:first)').remove();

        if (value != '') {
            $.getJSON(intelli.config.packages.publishing.url + 'publishing/read.json', {id: value}, function (response) {
                if (response && response.length > 0) {
                    var id = $scSelect.data('value');
                    $.each(response, function (index, item) {
                        var $option = $('<option>').val(item.id).text(item.title);
                        if (id == item.id) {
                            $option.attr('selected', true);
                        }
                        $scSelect.append($option);
                    });

                    $scSelect.prop('disabled', false);
console.log(id);
                    if (id) {
                        $scSelect.trigger('change');
                    }
                }
            });
        }
        else {
            $scSelect.prop('disabled', true);
        }
    });

    if (intelli.pageName == 'search' && $scSelect.data('value')) {
        $cSelect.trigger('change');
    }

    $scSelect.on('change', function () {
        intelli.search.run();
    });
});