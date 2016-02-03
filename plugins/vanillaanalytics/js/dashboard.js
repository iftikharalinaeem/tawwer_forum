$(document).ready(function() {
    var analyticsCharts = gdn.meta.analyticsCharts || false;
    var chartContainer = document.getElementById('charts') || false;

    if (typeof analyticsCharts !== 'object' || typeof chartContainer !== 'object') {
        return;
    }

    var tracker, chart, chartIndex, trackerChart;

    for (tracker in analyticsCharts) {
        var trackerChartClass = tracker + 'Chart';
        trackerChartClass = trackerChartClass.toUpperCase();

        if (typeof window[trackerChartClass] === 'function') {
            for (chartIndex in analyticsCharts[tracker]) {
                chart = analyticsCharts[tracker][chartIndex];
                if (typeof chart === 'object') {
                    trackerChart = new window[trackerChartClass](chart);
                    trackerChart.write(chartContainer);
                }
            }
        }
    }
});
