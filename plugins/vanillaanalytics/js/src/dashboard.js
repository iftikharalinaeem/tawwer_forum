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
                    d = Math.floor(d[0].value)
                    return '<div id="tooltip" class="module-triangle-bottom">' + d + '</div>'
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
