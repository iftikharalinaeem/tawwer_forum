$(document).ready(function() {
    var dashboardConfig = gdn.meta.analyticsDashboard || false;

    if (typeof dashboardConfig !== 'object') {
        return;
    }

    // Move this into KeenIOWidget loadDatavizConfig after https://github.com/keen/keen-js/issues/420 is fixed.
    if (typeof c3 === "object") {
        c3.chart.internal.fn.additionalConfig = {
            axis_y_tick_format: (function (d) {if (d % 1 !== 0) {return '';} return d;})
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
