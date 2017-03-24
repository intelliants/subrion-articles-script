$(document).ready(function () {
    intelli.articles = {};

    $('#jcal-target').jCal(
        {
            callback: function (day, days) {
                window.location.href = intelli.config.packages.publishing.url + 'date/' + day.getFullYear() + '/' + (day.getMonth() + 1) + '/' + day.getDate() + '/';
                return true;
            },
            change: function (day) {
                var month = day.getMonth();
                var year = day.getFullYear();
                if (typeof intelli.articles[year + '_' + month] == 'undefined') {
                    $.ajax({
                        async: false,
                        url: intelli.config.packages.publishing.url + 'calendar.json',
                        data: {m: month + 1, y: year},
                        success: function (response) {
                            intelli.articles[year + '_' + month] = response.data;
                        }
                    });
                }
            },
            day: new Date(),
            dCheck: function (day) {
                var key = day.getFullYear() + '_' + day.getMonth();
                if (typeof intelli.articles[key] != 'undefined') {
                    var selectedDay = day.getDate();
                    if (intelli.articles[key][selectedDay]) {
                        var htmlOutput = '<ul>';
                        for (var i in intelli.articles[key][selectedDay]) {
                            htmlOutput += '<li>' + intelli.articles[key][selectedDay][i] + '</li>';
                        }
                        htmlOutput += '</ul>'

                        return htmlOutput;
                    }
                }

                return false;
            },
            height: 80,
            monthSelect: true,
            width: 85
        });
});