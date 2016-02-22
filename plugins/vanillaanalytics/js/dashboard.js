$(document).ready(function() {
    var dashboardConfig = gdn.meta.analyticsDashboard || false;

    if (typeof dashboardConfig !== 'object') {
        return;
    }

    var analyticsDashboard = new AnalyticsDashboard(dashboardConfig);
    analyticsDashboard.writeDashboard();
});
