$(document).ready(function() {
    analyticsCharts = gdn.meta.analyticsCharts || false;
    chartContainer = document.getElementById('charts') || false;

    if (!analyticsCharts || !chartContainer) {
        return;
    }

    document.getElementById('charts').innerHTML = '<pre>' + JSON.stringify(analyticsCharts, null, 4) + '</pre>';
});
