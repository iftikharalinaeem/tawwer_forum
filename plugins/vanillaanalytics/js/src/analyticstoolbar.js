var analyticsToolbar = {

    inited: false,

    maxTicks: 200,

    /**
     * Grab default date range.
     * @returns {Object}
     */
    getDefaultRange: function() {
        // Grab the date range from a cookie.  If we don't have one, build the default starting from a month ago.
        var dateRange = Cookies.getJSON('va-dateRange');
        if (typeof dateRange !== "object" || typeof dateRange.start !== "string" || typeof dateRange.end !== "string") {
            dateRange = {
                start: moment().subtract("month", 1).toDate(),
                end: moment().toDate()
            };
        } else {
            dateRange = {
                start: new Date(dateRange.start),
                end: new Date(dateRange.end)
            }
        }

        return dateRange;
    },

    setWidgets: function(method, newValue) {
        var panels = [window.analyticsDashboard.getPanel('metrics'), window.analyticsDashboard.getPanel('charts')];

        for (var i in panels) {
            panel = panels[i];

            for (var j in panel.widgets) {
                widget = panel.widgets[j];

                if (typeof(widget[method]) === 'function') {
                    valid = widget[method].apply(widget, newValue);

                    if (window.analyticsDashboard.inited && valid !== false) {
                        widget.render();
                    }
                }
            }
        }
    },

    updateIntervals: function(range) {

        var interval = Cookies.getJSON('va-interval');

        var end = new Date(range['end']);
        var start = new Date(range['start']);
        var rangeSeconds = (end - start)/1000;
        if (!rangeSeconds) {
            rangeSeconds = 86400; // default to one day
        }
        var threshold = rangeSeconds / analyticsToolbar.maxTicks;

        // Set bad intervals to disabled.
        $('.js-analytics-interval').each(function() {
            if ($(this).data('seconds') < threshold) {
                $(this).addClass('disabled');
            } else {
                $(this).removeClass('disabled');
            }
        });

        var $cookiedInterval = $('.js-analytics-interval[data-interval="' + interval + '"]');

        if (interval !== 'undefined' && $cookiedInterval.length > 0 && !$cookiedInterval.hasClass('disabled')) {
            $cookiedInterval.trigger('click');
        } else {
            // Choose the first good interval.
            if ($('.js-analytics-interval.active').hasClass('disabled') || $('.js-analytics-interval.active').length === 0) {
                $('.js-analytics-interval.active').removeClass('active');
                $('.js-analytics-interval').each(function() {
                    if (!$(this).hasClass('disabled')) {
                        $(this).trigger('click');
                        return false;
                    }
                });
            }
        }
    }
};


$(document).on('change', '#Form_cat01', function() {
    var newCat01 = $(this).val();
    analyticsToolbar.setWidgets('setFilter', ['categoryAncestors.cat01.categoryID', newCat01, 'cat01']);
});


// Re-render the graphs with new intervals.
$(document).on('click', '.js-analytics-interval:not(.disabled)', function() {
    // Already active..
    if ($(this).hasClass('active')) {
        return;
    }

    $('.js-analytics-interval').removeClass('active');
    $(this).addClass('active');
    Cookies.set('va-interval', $(this).data('interval'));

    var newInterval = $(this).data('interval');
    analyticsToolbar.setWidgets('setInterval', [newInterval]);
});

$(document).ready(function() {
    var defaultRange = analyticsToolbar.getDefaultRange();

    $(".js-date-range").daterangepicker({
        alwaysShowCalendars: true,
        startDate: defaultRange.start,
        endDate: defaultRange.end,
        opens: 'left',
        buttonClasses: "btn",
        applyClass: "btn-primary",
        cancelClass: "btn-secondary",
        maxDate: moment(),
        ranges: {
            'Past 30 Days': [moment().subtract(30, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'Past Year': [moment().subtract(1, 'year'), moment()],
            'Year to Date': [moment().startOf('year'), moment()]
        }
    });

    $(".js-date-range").on('apply.daterangepicker', function (ev, picker) {
        var range = {
            start: picker.startDate.toDate(),
            end: picker.endDate.toDate()
        };
        Cookies.set('va-dateRange', range);
        analyticsToolbar.updateIntervals(range);
        analyticsToolbar.setWidgets('setRange', [range]);
    });

    $(".js-date-range").on('show.daterangepicker', function (ev, picker) {
        $('.daterangepicker').css('display', 'flex');
    });

    analyticsToolbar.updateIntervals(defaultRange);
    analyticsToolbar.inited = true;

    analyticsToolbar.setWidgets('setRange', [defaultRange]);
});


