$(document).ready(function() {
    function setWidgets(method, newValue) {
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
    }

    $(document).on('change', '#Form_cat01', function() {
        var newCat01 = $(this).val();
        setWidgets('setFilter', ['categoryAncestors.cat01.categoryID', newCat01, 'cat01']);
    });


    // Re-render the graphs with new intervals.
    $(document).on('change', '#Form_intervals', function() {
        var newInterval = $(this).val();
        setWidgets('setInterval', [newInterval]);
    });

    $(".js-date-range").daterangepicker({
        datepickerOptions : {
            numberOfMonths : 2
        },
        presetRanges: [
            {
                text: 'Past 30 Days',
                dateStart: function() { return moment().subtract('days', 30); },
                dateEnd: function() { return moment(); }
            },
            {
                text: 'This Month',
                dateStart: function() { return moment().startOf('month'); },
                dateEnd: function() { return moment().endOf('month'); }
            },
            {
                text: 'Last Month',
                dateStart: function() { return moment().subtract('month', 1).startOf('month'); },
                dateEnd: function() { return moment().subtract('month', 1).endOf('month'); }
            },
            {
                text: 'Past Year',
                dateStart: function() { return moment().subtract('year', 1); },
                dateEnd: function() { return moment(); }
            },
            {
                text: 'Year to Date',
                dateStart: function() { return moment().startOf('year'); },
                dateEnd: function() { return moment(); }
            }
        ],
        onChange: function() {
            var range = $(".js-date-range").daterangepicker("getRange");
            setWidgets('setRange', [range]);
        }
    });

});