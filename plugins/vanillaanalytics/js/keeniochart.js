/**
 * keen.io chart object.
 * @param {object} rawConfig Configuration object containing chart details.
 */
function KeenIOChart(rawConfig) {
    this.client      = null;
    this.config      = rawConfig || {};
    this.chartConfig = rawConfig.chart || {};
    this.query       = null;

    this.setQuery(rawConfig.query || {});
}

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
 * Setup the query object we'll need for the chart.
 * @param {object} config A DOM element where the chart should be written.
 */
KeenIOChart.prototype.setQuery = function(config ) {
    if (typeof config !== 'object') {
        config = {};
    }

    this.query = new Keen.Query(
        config.analisysType || null,
        {
            eventCollection : config.eventCollection || null,
            filters         : config.filters || [],
            interval        : config.interval || null,
            timeframe       : config.timeframe || null
        }
    );
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

    client.draw(
        this.query,
        container,
        this.chartConfig
    );
};
