var analyticsToolbar = {

    inited: false,

    minTicks: 2,
    maxTicks: 200,

    /**
     * Grab default date range.
     * @returns {Object}
     */
    getDefaultRange: function() {
        // Grab the date range from a cookie.  If we don't have one, build the default starting from a month ago.
        var dateRange = Cookies.getJSON('va-dateRange');

        var utcIso8601DateRegex = /\d{4}-\d\d-\d\dT(\d\d:){2}\d\d.\d{3}(\+|-)\d\d:\d\d/;

        if (typeof dateRange !== "object" || typeof dateRange.start !== "string" || typeof dateRange.end !== "string"
            || !utcIso8601DateRegex.test(dateRange.start)) {
            dateRange = {
                start: moment().startOf('day').subtract(1, "month").format('YYYY-MM-DDTHH:mm:ss.SSSZ'),
                end: moment().startOf('day').format('YYYY-MM-DDTHH:mm:ss.SSSZ')
            };
        } else {
            dateRange = {
                start: dateRange.start,
                end: dateRange.end
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

                    if (window.analyticsToolbar.inited && valid !== false) {
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
        var maxThreshold = rangeSeconds / analyticsToolbar.maxTicks;
        var minThreshold = rangeSeconds / analyticsToolbar.minTicks;

        // Set bad intervals to disabled.
        $('.js-analytics-interval').each(function() {
            if ($(this).data('seconds') <= maxThreshold || $(this).data('seconds') >= minThreshold) {
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
    // Keen.io has range with start date inclusiv and end date exclusive so,
    // for simplicity, let's use the datepicker like so.

    var defaultRange = analyticsToolbar.getDefaultRange();
    var todayInclusive = moment().startOf('day').add(1, 'day');

    $(".js-date-range").daterangepicker({
        alwaysShowCalendars: true,
        startDate: moment(defaultRange.start),
        endDate: moment(defaultRange.end),
        opens: 'left',
        buttonClasses: "btn",
        applyClass: "btn-primary",
        cancelClass: "btn-secondary",
        maxDate: todayInclusive,
        ranges: {
            'Past 30 Days': [
                moment().startOf('day').subtract(29, 'days'), // Today will be included so go back only 29 days
                todayInclusive
            ],
            'This Month': [
                moment().startOf('month'),
                todayInclusive // Doesn't do us any good to check passed today
            ],
            'Last Month': [
                moment().startOf('month').subtract(1, 'month'),
                moment().startOf('month')
            ],
            'Past Year': [
                moment().startOf('day').subtract(1, 'year'),
                todayInclusive
            ],
            'Year to Date': [
                moment().startOf('year'),
                todayInclusive
            ]
        },
    });

    $(".js-date-range").on('apply.daterangepicker', function (ev, picker) {
        /*
            Fix DST shenanigans.

            Timezone: US/Eastern
            var date1 = moment('2016-11-06T00:00:00.000');
            var date2 = moment('2016-11-07T00:00:00.000')

            // date1.format('Z'); -> -04:00
            // date2.format('Z'); -> -05:00

            console.debug(date1.utc().format('YYYY-MM-DDTHH:mm:ss.SSSZ')); // 2016-11-06T04:00:00.000+00:00
            console.debug(date2.utc().format('YYYY-MM-DDTHH:mm:ss.SSSZ')); // 2016-11-07T05:00:00.000+00:00

            You end up with 1 hour difference and this is messing up intervals real bad
         */
        var unifiedTimeZone;
        if (picker.startDate.format('Z') === picker.endDate.format('Z')) {
            unifiedTimeZone = picker.startDate.format('Z')
        } else {
            unifiedTimeZone = moment('2016-01-01').format('Z'); // Take timezone with no DST
        }

        // Preformat date into valid ISO-8601 dates
        var range = {
            start: picker.startDate.format('YYYY-MM-DDT00:00:00.000')+unifiedTimeZone,
            end: picker.endDate.format('YYYY-MM-DDT00:00:00.000')+unifiedTimeZone
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



    $('.Section-Analytics').on('change', '.js-category-telescope', function() {

        var $self = $(this);
        var newCat = $self.val();
        var depth = $self.data('depth');
        var fetchChildren = true;
        $self.prop('disabled', true);

        while (newCat === '') {
            // we've selected all. Value is parent's category ID.
            newCat = $('.js-category-telescope[data-depth=' + (depth - 1) + ']').val();
            fetchChildren = false;
        }

        if (newCat === undefined) {
            // we're back to the root category, clear the category field to fetch all categories
            newCat = '';
        }

        // Clear every dropdown below the selected filter
        $self.parent().nextAll('.js-category-telescope-wrapper').remove();
        analyticsToolbar.setWidgets('setFilter', ['categoryAncestors.cat01.categoryID', newCat, 'cat01']);

        if (!fetchChildren) {
            $self.prop('disabled', false);
            return;
        }

        var ajaxData = {
            'DeliveryType': 'VIEW',
            'DeliveryMethod': 'JSON',
            'ParentCategoryID': newCat,
            'ParentDepth': depth,
            'TransientKey': gdn.definition('TransientKey')
        };

        $.ajax({
            type: "POST",
            data: ajaxData,
            url: gdn.url('/analytics/getcategorydropdown'),
            dataType: 'html',
            error: function(XMLHttpRequest) {
                console.error(XMLHttpRequest.responseText);
            },
            success: function(data) {
                $self.parents('.js-filter-content').appendTrigger(data);
            },
            complete: function() {
                $self.prop('disabled', false);
            }
        });
    });
});
