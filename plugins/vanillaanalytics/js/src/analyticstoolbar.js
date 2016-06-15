var analyticsToolbar = {

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

                    if (valid !== false) {
                        widget.render();
                    }
                }
            }
        }
    },

    updateIntervals: function(range) {
        var end = new Date(range['end']);
        var start = new Date(range['start']);
        var rangeSeconds = (end - start)/1000;
        if (!rangeSeconds) {
            rangeSeconds = 86400; // default to one day
        }
        var threshold = rangeSeconds / analyticsToolbar.maxTicks;

        // Set bad intervals to disabled.
        $('#Form_intervals > option').each(function() {
            if ($(this).attr('data-seconds') < threshold) {
                $(this).attr('disabled', 'disabled');
            } else {
                $(this).removeAttr('disabled');
            }
        });

        // Choose the first good interval.
        if ($('#Form_intervals option:selected').attr('disabled') === 'disabled') {
            $('#Form_intervals > option').each(function() {
                if (!$(this).attr('disabled')) {
                    $('#Form_intervals').val($(this).val()).trigger('change');
                    return false;
                }
            });
        }
    }
};


$(document).on('change', '#Form_cat01', function() {
    var newCat01 = $(this).val();
    analyticsToolbar.setWidgets('setFilter', ['categoryAncestors.cat01.categoryID', newCat01, 'cat01']);
});


// Re-render the graphs with new intervals.
$(document).on('change', '#Form_intervals', function() {
    var newInterval = $(this).val();
    analyticsToolbar.setWidgets('setInterval', [newInterval]);
});

$(document).on('ready', function() {

    $(".js-date-range").daterangepicker({
        datepickerOptions: {
            numberOfMonths: 2
        },
        presetRanges: [
            {
                text: 'Past 30 Days',
                dateStart: function () {
                    return moment().subtract('days', 30);
                },
                dateEnd: function () {
                    return moment();
                }
            },
            {
                text: 'This Month',
                dateStart: function () {
                    return moment().startOf('month');
                },
                dateEnd: function () {
                    return moment().endOf('month');
                }
            },
            {
                text: 'Last Month',
                dateStart: function () {
                    return moment().subtract('month', 1).startOf('month');
                },
                dateEnd: function () {
                    return moment().subtract('month', 1).endOf('month');
                }
            },
            {
                text: 'Past Year',
                dateStart: function () {
                    return moment().subtract('year', 1);
                },
                dateEnd: function () {
                    return moment();
                }
            },
            {
                text: 'Year to Date',
                dateStart: function () {
                    return moment().startOf('year');
                },
                dateEnd: function () {
                    return moment();
                }
            }
        ],
        onChange: function () {
            var range = $(".js-date-range").daterangepicker("getRange");
            Cookies.set('va-dateRange', range);
            analyticsToolbar.updateIntervals(range);
            analyticsToolbar.setWidgets('setRange', [range]);
        }
    });

    $(".js-date-range").daterangepicker("setRange", analyticsToolbar.getDefaultRange());
});
