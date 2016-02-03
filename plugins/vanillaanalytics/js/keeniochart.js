/**
 * keen.io chart object.
 * @param {object} rawConfig Configuration object containing chart details.
 */
function KeenIOChart(rawConfig) {
    this.client      = null;
    this.config      = rawConfig || {};
    this.chartConfig = this.config.chart || {};
    this.query       = [];

    this.addQuery(this.config.query || {});
}

/**
 * Setup the query object we'll need for the chart.
 * @param {object} config A DOM element where the chart should be written.
 */
KeenIOChart.prototype.addQuery = function(config ) {
    if (config instanceof Array) {
        for (i in config) {
            this.addQuery(config[i]);
        }
        return;
    } else if (typeof config !== 'object') {
        return;
    }

    /**
     * If we're including multiple queries in this chart, we need to make sure their interval and timeframe are
     * in sync with one another.  To do this, we take the first query's values and use them for all subsequent
     * queries, just to be sure.
     */
    var interval, timeframe;
    if (this.query.length > 0 && typeof this.query[0] !== 'undefined') {
        var alphaQuery = this.query[0];

        interval  = alphaQuery.interval || null;
        timeframe = alphaQuery.timeframe || null;
    } else {
        interval  = config.interval || null;
        timeframe = config.timeframe || null;
    }

    var query = new Keen.Query(
        config.analisysType || null,
        {
            eventCollection : config.eventCollection || null,
            filters         : config.filters || [],
            interval        : interval,
            timeframe       : timeframe
        }
    );
    query.title = config.title || null;

    this.query.push(query);
};

/**
 * Get the current instance of the Keen SDK client.  Create a new one, if necessary.
 * @returns {null|Keen}
 */
KeenIOChart.prototype.getClient = function() {
    if (!this.client && typeof Keen == 'function') {
        var projectID = gdn.meta['keenio.projectID'] || false;
        var readKey = gdn.meta['keenio.readKey'] || false;

        this.client = new Keen({
            projectId: projectID,
            readKey: readKey
        });
    }

    return this.client;
};

/**
 * Output a chart into the target container element.
 * @param {object} container A DOM element where the chart should be written.
 */
KeenIOChart.prototype.write = function(container) {
    if (typeof container !== 'object') {
        return;
    }

    var client = this.getClient();

    if (typeof client === 'object') {
        var chart = new Keen.Dataviz();
        var categories = [];

        for (c in this.query) {
            categories.push(this.query[c].title);
        }

        chart.el(container)
            .library('c3')
            .chartType(this.chartConfig.type || null)
            .chartOptions(this.chartConfig.options || null)
            .prepare();

        client.run(this.query, function(error, response) {

            if (error) {
                return;
            }

            var data   = [];

            if (!(response instanceof Array)) {
                response = [response];
            }

            var alphaResult = response[0].result;

            for (i in alphaResult) {
                var value = [];

                value.push({
                    category : categories[0],
                    result   : alphaResult[i]['value']
                });

                data.push({
                    timeframe: alphaResult[i]['timeframe'],
                    value: value
                });
            }

            chart.parseRawData({ result: data }).render();
        });
    }
};
