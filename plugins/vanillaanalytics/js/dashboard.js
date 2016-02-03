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
                    var chartCanvas = document.createElement('div');
                    chartCanvas.setAttribute('id', 'chart-' + chartCollection + '-' + (parseInt(chartIndex) + 1));
                    chartCanvas.setAttribute('class', 'va-chart');

                    trackerChart = new window[chartCollection](chart);
                    trackerChart.write(chartCanvas);

                    chartContainer.appendChild(chartCanvas);
                }
            }
        }
    }
});
