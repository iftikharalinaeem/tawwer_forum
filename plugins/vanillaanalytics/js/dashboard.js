$(document).ready(function() {
    var analyticsDashboard = gdn.meta.analyticsDashboard || false;

    if (typeof analyticsDashboard !== 'object' || typeof analyticsDashboard.panels !== 'object') {
        return;
    }

    var panels = analyticsDashboard.panels;

    if (typeof rangeEnd !== 'object' || !(rangeEnd instanceof Date)) {
        var rangeEnd = new Date();
    }
    if (typeof rangeStart !== 'object' || !(rangeStart instanceof Date)) {
        var rangeStart = new Date();
        rangeStart.setMonth(rangeEnd.getMonth() - 1);
    }

    for (var panelID in panels) {
        var panelContainer = document.getElementById('analytics_panel_' + panelID);
        var widgets = panels[panelID].widgets || false;

        if (typeof panelContainer !== 'object' || !Array.isArray(widgets)) {
            continue;
        }

        widgets.forEach(function(currentValue, index, array) {
            var data     = currentValue.data || [];
            var handler  = currentValue.handler || false;
            var title    = currentValue.title || '';
            var type     = currentValue.type || false;
            var widgetID = currentValue.widgetID || false;

            if (typeof window[handler] === 'function') {
                if (handler || widgetID || type) {
                    var chartCanvas = document.createElement('div');
                    chartCanvas.setAttribute('id', 'analytics_widget_' + widgetID);
                    chartCanvas.setAttribute('class', 'analytics-widget analytics-widget-' + type);

                    var trackerChart = new window[handler](rangeStart, rangeEnd, data, type, title);
                    trackerChart.write(chartCanvas);

                    panelContainer.appendChild(chartCanvas);
                }
            } else {
                console.log('Failed to locate handler: ' + handler);
            }
        });
    }
});
