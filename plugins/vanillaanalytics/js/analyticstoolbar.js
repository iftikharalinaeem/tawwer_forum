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
});