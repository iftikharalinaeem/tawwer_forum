$(document).ready(function() {
    var analyticsDashboard = gdn.meta.analyticsDashboard || false;

    if (typeof analyticsDashboard !== 'object' || typeof analyticsDashboard.panels !== 'object') {
        return;
    }

    var panels = analyticsDashboard.panels;

    if (typeof rangeEnd !== 'object' || !(rangeEnd instanceof Date)) {
        var rangeEnd = new Date();
        rangeEnd.setHours(0, 0, 0);
    }
    if (typeof rangeStart !== 'object' || !(rangeStart instanceof Date)) {
        var rangeStart = new Date();
        rangeStart.setHours(0, 0, 0);
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
                    var widgetContainer = document.createElement('div');
                    widgetContainer.setAttribute('id', 'analytics_widget_' + widgetID);
                    widgetContainer.setAttribute('class', 'analytics-widget analytics-widget-' + type);

                    if (type !== 'metric') {
                        var widgetTitle = document.createElement('h4');
                        widgetTitle.setAttribute('class', 'title')
                        widgetTitle.innerHTML = title;
                        widgetContainer.appendChild(widgetTitle);
                    }


                    var widgetBody = document.createElement('div');
                    widgetBody.setAttribute('class', 'body');
                    widgetContainer.appendChild(widgetBody);


                    var trackerChart = new window[handler](rangeStart, rangeEnd, data, type, title);
                    trackerChart.write(widgetBody);

                    panelContainer.appendChild(widgetContainer);
                }
            } else {
                console.log('Failed to locate handler: ' + handler);
            }
        });
    }
});
