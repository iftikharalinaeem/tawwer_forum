$(document).ready(function() {
    var analyticsCharts = gdn.meta.analyticsCharts || false;
    var chartContainer = document.getElementById('charts') || false;

    if (typeof analyticsCharts !== 'object' || typeof chartContainer !== 'object') {
        return;
    }

    var chartCollection, chartIndex, trackerChart;

    for (chartCollection in analyticsCharts) {
        if (typeof window[chartCollection] === 'function') {
            for (chartIndex in analyticsCharts[chartCollection]) {
                chart = analyticsCharts[chartCollection][chartIndex];
                if (typeof chart === 'object') {
                    trackerChart = new window[chartCollection](chart);
                    trackerChart.write(chartContainer);
                }
            }
        }
    }
});
