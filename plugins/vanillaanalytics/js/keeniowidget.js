/**
 * keen.io widget object.
 * @param {object} rawConfig Configuration object containing chart details.
 */
function KeenIOWidget(rangeStart, rangeEnd, config, type, title) {
    this.client      = null;
    this.config      = config || {};
    this.chartConfig = this.config.chart || {};
    this.query       = [];

    if (typeof rangeEnd !== 'object' || !(rangeEnd instanceof Date)) {
        this.rangeEnd = new Date();
        this.rangeEnd.setHours(0, 0, 0);
    } else {
        this.rangeEnd = rangeEnd;
    }

    if (typeof rangeStart !== 'object' || !(rangeStart instanceof Date)) {
        this.rangeStart = new Date();
        this.rangeStart.setHours(0, 0, 0);
        this.rangeStart.setMonth(rangeEnd.getMonth() - 1);
    } else {
        this.rangeStart = rangeStart;
    }

    this.addQuery(this.config.query || {});
}

/**
 * Setup the query object we'll need for the chart.
 * @param {object} config A DOM element where the chart should be written.
 */
KeenIOWidget.prototype.addQuery = function(config ) {
    if (config instanceof Array) {
        for (i in config) {
            this.addQuery(config[i]);
        }
        return;
    } else if (typeof config !== 'object') {
        return;
    }

    var timeframe = {
        start : this.rangeStart.toISOString(),
        end   : this.rangeEnd.toISOString()
    };

    var interval;
    if (this.query.length > 0 && typeof this.query[0] !== 'undefined') {
        var alphaQuery       = this.query[0];
        var alphaQueryParams = alphaQuery.params || {};

        interval  = alphaQueryParams.interval || null;
    } else {
        interval  = config.interval || null;
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
KeenIOWidget.prototype.getClient = function() {
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
KeenIOWidget.prototype.write = function(container) {
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
            .chartType(this.chartConfig.type || '')
            .chartOptions(this.chartConfig.options || {})
            .prepare();

        // Run our queries and chart the results.
        client.run(this.query, function(error, analyses) {
            // Normalize our result to always be an array.
            if (!(analyses instanceof Array)) {
                analyses = [analyses];
            }

            var alphaAnalysis = analyses[0].result;
            var data = [];

            // Use the first analysis to guide the charting of the other analyses, if available.
            alphaAnalysis.forEach(function(stepValue, step) {
                var value = [];

                // Grab the individual values and labels for this charting step.
                analyses.forEach(function(analysis, analysisIndex) {
                    value.push({
                        category: categories[analysisIndex],
                        result: analysis.result[step]['value']
                    });
                });

                // Build onto our data object.
                data.push({ // format the data so it can be charted
                    timeframe: stepValue['timeframe'],
                    value: value
                });
            });

            // Hand it all off to our Keen.Dataviz object to takeover.
            chart.parseRawData({ result: data }).render();
        });
    }
};
