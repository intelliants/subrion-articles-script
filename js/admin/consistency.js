$(function () {
    $('.js-repair').on('click', function (e) {
        e.preventDefault();

        var start = 0,
            limit = 20,
            action = $(this).data('action'),
            total = 0,
            progress = 0,
            interval = 3000,
            url = intelli.config.admin_url + '/publishing/categories/read.json',
            timer;

        var $barHolder = $(this).parent().next().find('.js-repair-progress');
        var $bar = $('.progress-bar', $barHolder);
        var $button = $(this);
        var startText = $button.text();

        $barHolder.removeClass('hidden').addClass('active');
        $bar.text('');
        $button.prop('disabled', true);

        $.ajaxSetup({async: false});
        $.post(url, {action: $(this).data('pre')}, function (response) {
            total = response.total;
            timer = setInterval(function () {
                $.post(url, {start: start, limit: limit, action: action}, function () {
                    progress = Math.round(start / total * 100);

                    if (start > total) {
                        clearInterval(timer);
                        $barHolder.removeClass('active');
                        $bar.css('width', '100%');
                        intelli.notifFloatBox({msg: _t('done'), type: 'notif', autohide: true});
                        $button.text(startText).prop('disabled', false);
                    }
                    else {
                        $bar.css('width', progress + '%');
                        $button.text(progress + '%');
                    }
                });

                start += limit;
            }, interval);
        });
        $.ajaxSetup({async: true});
    });
});