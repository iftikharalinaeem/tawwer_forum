$(document).ready(function() {
    var dashboardConfig = gdn.meta.analyticsDashboard || false;

    if (typeof dashboardConfig !== 'object') {
        return;
    }

    if (typeof c3 === "object") {
        c3.chart.internal.fn.additionalConfig = {
            axis_x_tick_count: 5,
            grid_x_show: true,
            grid_y_show: true,
            axis_x_tick_format: (function(date) { return this.formatDate(date) }).bind(this),
            axis_x_tick_type: "timeseries",
            axis_y_tick_format: (function (d) {if (d % 1 !== 0) {return '';} return d;}),
            tooltip_contents: function (d, defaultTitleFormat, defaultValueFormat, color) {
                var titleFormat = defaultTitleFormat,
                    nameFormat = function (name) { return name; },
                    valueFormat = defaultValueFormat,
                    text, i, title, value, name, bgcolor;

                // one value, no title necessary
                if (d.length === 1) {
                    value = valueFormat(d[0].value, d[0].ratio, d[0].id, d[0].index);
                    return '<div class="popover popover-analytics-single popover-analytics popover-name-" + d[0].id + ">' + value + '</div>';
                }

                var text = '<div class="popover popover-analytics">';
                for (i = 0; i < d.length; i++) {
                    if (text.length === 0) {}

                    if (! (d[i] && (d[i].value || d[i].value === 0))) { continue; }

                    name = nameFormat(d[i].name);
                    value = valueFormat(d[i].value, d[i].ratio, d[i].id, d[i].index);
                    bgcolor = color(d[i].id);

                    text += "<div class='flex popover-row popover-name-" + d[i].id + "'>";
                    text += "<div class='name'>" + name + "</div>";
                    text += "<div class='value'><span style='color:" + bgcolor + "'>" + value + "</span></div>";
                    text += "</div>";
                }
                return text + '</div>';
            }
        };
    }

    var dateRange = analyticsToolbar.getDefaultRange();
    var dashboard = new AnalyticsDashboard(dashboardConfig, dateRange.start, dateRange.end);
    dashboard.writeDashboard();

    window.analyticsDashboard = dashboard;
});

function removeAnalyticsWidget() {
    if (typeof window.analyticsDashboard !== 'object' || !(window.analyticsDashboard instanceof AnalyticsDashboard)) {
        return;
    }

    var dashboard = window.analyticsDashboard;

    if (dashboard.isPersonal() === false) {
        return;
    }

    if ($(this).size() > 0) {
        var element = $(this).get(0);

        if (element instanceof HTMLElement) {
            var elementID = element.id;
            var idParts = /analytics_widget_([a-z0-9\-]+)/i.exec(elementID);
            if (Array.isArray(idParts)) {
                var widgetID = idParts[1];
                dashboard.removeWidget(widgetID);
            }
        }
    }
}
