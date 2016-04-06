/**
 * keen.io widget object.
 *
 * @class
 * @param {object} config
 */
function KeenIOWidget(config) {

    /**
     *
     * @type {null|function}
     */
    var callback = null;

    /**
     * @type {object}
     */
    var chartConfig = {};

    /**
     *
     * @type {null|Keen}
     */
    var client = null;

    /**
     * @type Array
     */
    var data = [];

    /**
     * @type {null|Keen.Dataviz}
     */
    var dataviz = null;

    /**
     * @type {string}
     */
    var interval = 'daily';

    /**
     * @type {null|HTMLElement}
     */
    var element = null;

    /**
     * @type {Array}
     */
    var query = [];

    /**
     * @type {object}
     */
    var range = {
        end  : null,
        start: null
    };

    /**
     * @type {null|string}
     */
    var type = null;

    /**
     * @type {string}
     */
    var title = '';

    /**
     * Setup the query object we'll need for the chart.
     *
     * @param {object} config
     * @todo Remove hardwired timeframe
     */
    this.addQuery = function(config) {
        if (config instanceof Array) {
            for (var i = 0; i < config.length; i++) {
                this.addQuery(config[i]);
            }
            return;
        } else if (typeof config !== 'object') {
            return;
        }

        var queryParams = {
            eventCollection : config.eventCollection || null,
            filters         : config.filters || [],
            maxAge          : 86400
        };

        if (typeof config.groupBy !== 'undefined') {
            queryParams.groupBy = config.groupBy;
        }

        if (typeof config.target_property !== 'undefined') {
            queryParams.target_property = config.target_property;
        }

        var newQuery = new Keen.Query(
            config.analisysType || null,
            queryParams
        );

        newQuery.title = config.title || null;

        query.push(newQuery);
    };

    /**
     *
     * @returns {null|function}
     */
    this.getCallback = function() {
        return callback;
    };

    /**
     * Get the current instance of the Keen SDK client.  Create a new one, if necessary.
     *
     * @returns {null|Keen}
     */
    this.getClient = function() {
        if (client === null && typeof Keen == 'function') {
            var projectID = gdn.meta['keenio.projectID'] || false;
            var readKey = gdn.meta['keenio.readKey'] || false;

            client = new Keen({
                projectId: projectID,
                readKey: readKey
            });
        }

        return client;
    };

    /**
     * @param {string} key
     * @param {*} defaultValue
     * @returns {*}
     */
    this.getConfig = function(key, defaultValue) {
        if (typeof chartConfig[key] === 'undefined') {
            return defaultValue || null;
        } else {
            return chartConfig[key];
        }
    };

    /**
     * @returns {Array}
     */
    this.getData = function() {
        return data;
    };

    /**
     * @returns {null|Keen.Dataviz}
     */
    this.getDataviz = function() {
        if (dataviz === null) {
            dataviz = new Keen.Dataviz();
            this.loadDatavizConfig(this.getConfig());
        }

        return dataviz;
    };

    /**
     * @returns {null|HTMLElement}
     */
    this.getElement = function() {
        return element;
    };

    this.getInterval = function() {
        return interval;
    };

    /**
     * @returns {object}
     */
    this.getRange = function() {
        return range;
    };

    /**
     * @returns {Array}
     */
    this.getQuery = function() {
        return query;
    };

    /**
     * @param {string} key
     * @param {number} queryIndex
     */
    this.getQueryParam = function(key, index) {
        var queryCollection = this.getQuery();
        index = typeof index === 'undefined' ? 0 : parseInt(index);

        if (typeof queryCollection[index] === 'undefined') {
            throw 'Invalid query index';
        } else {
            var currentQuery = queryCollection[index];
        }

        return currentQuery.get(key);
    };

    /**
     * @returns {string}
     */
    this.getTitle = function() {
        return title;
    };

    /**
     * @returns {null|string}
     */
    this.getType = function() {
        return type;
    };

    /**
     *
     */
    this.resetQuery = function() {
        query = [];
    };

    /**
     *
     * @param {string} newCallback
     * @returns {AnalyticsWidget}
     */
    this.setCallback = function(newCallback) {
        if (typeof this[newCallback] === 'function') {
            callback = this[newCallback];
            return this;
        } else {
            throw 'Invalid value for newCallback';
        }
    };

    /**
     * @param {Array} newData
     * @returns {KeenIOWidget}
     */
    this.setData = function(newData) {
        if (typeof newData !== 'undefined') {
            data = newData;
            return this;
        } else {
            throw 'Invalid newData';
        }
    };

    /**
     * @param {HTMLElement} newElement
     * @returns {KeenIOWidget}
     */
    this.setElement = function(newElement) {
        if (newElement instanceof HTMLElement) {
            element = newElement;
            return this;
        } else {
            throw 'newElement is not an instance of HTMLElement';
        }
    };

    /**
     * @param {string} newInterval
     */
    this.setInterval = function(newInterval) {
        if (typeof newInterval === 'string') {
            interval = newInterval;
            return this;
        } else {
            throw 'newInterval must be a string';
        }
    };

    /**
     * @param {object} newRange
     * @returns {KeenIOWidget}
     */
    this.setRange = function(newRange) {
        if (typeof newRange !== 'object') {
            throw 'Invalid newRange';
        }

        var end = newRange.end || false;
        var start = newRange.start || false;

        if (typeof end !== 'object' || !(end instanceof Date)) {
            range.end = new Date();
            range.end.setHours(0, 0, 0);
        } else {
            range.end = end;
        }

        if (typeof start !== 'object' || !(start instanceof Date)) {
            range.start = new Date();
            range.start.setHours(0, 0, 0);
            range.start.setMonth(range.end.getMonth() - 1);
        } else {
            range.start = start;
        }

        return this;
    };

    /**
     * @param {string} newType
     * @returns {KeenIOWidget}
     */
    this.setType = function(newType) {
        if (typeof newType === 'string') {
            type = newType;
            return this;
        } else {
            throw 'Invalid newType';
        }
    };

    /**
     * @param {string} newTitle
     * @returns {KeenIOWidget}
     */
    this.setTitle = function(newTitle) {
        if (typeof newTitle === 'string') {
            title = newTitle;
            return this;
        } else {
            throw 'Invalid newTitle';
        }
    };

    /**
     * @param {object} newParams
     */
    this.updateQueryParams = function(newParams) {
        if (typeof newParams !== 'object') {
            throw 'Invalid newParams';
        }

        for (var i = 0; i < query.length; i++) {
            query[i].set(newParams);
        }
    };

    this.loadConfig(config);
}

/**
 *
 * @param {Array} result
 * @return {Array}
 */
KeenIOWidget.prototype.divideResult = function(result) {
    var revisedResult = [];

    if (!Array.isArray(result)) {
        throw 'divideResult requires an array';
    }

    if (result[0].value.length !== 2) {
        throw 'divideResult requires exactly two results';
    }

    for (var i = 0; i < result.length; i++) {
        var value;

        if (result[i].value[1].result > 0) {
            value = parseFloat((result[i].value[0].result / result[i].value[1].result).toFixed(2));
        } else {
            value = 0;
        }

        revisedResult.push({
            timeframe: result[i].timeframe,
            value    : value
        });
    }

    return revisedResult;
};

KeenIOWidget.prototype.getMetricMarkup = function() {
    var markup = "<span class=\"metric-value\">{data}</span><span class=\"metric-title\">{title}</span>";
    var data = this.getData();
    var title = this.getTitle();

    markup = markup.replace("{data}", Number(data).toLocaleString());
    markup = markup.replace("{title}", this.getTitle());

    return markup;
};

/**
 * @param {object} config
 */
KeenIOWidget.prototype.loadConfig = function(config) {
    if (typeof config !== 'object') {
        throw 'Invalid config';
    }

    if (typeof config.chartConfig !== 'undefined') {
        this.setChartConfig(config.chartConfig);
    }

    if (typeof config.query !== 'undefined') {
        this.resetQuery();
        this.addQuery(config.query);
    }

    if (typeof config.range !== 'undefined') {
        this.setRange(config.range);
    }

    if (typeof config.title !== 'undefined') {
        this.setTitle(config.title);
    }

    if (typeof config.type !== 'undefined') {
        this.setType(config.type);
    }
};

/**
 * @param {object} [config]
 */
KeenIOWidget.prototype.loadDatavizConfig = function (config) {
    var dataviz = this.getDataviz();

    /*
    var defaultColors = dataviz.colors();
    var counter = defaultColors.length;

    console.log($("#analytics_panel_charts .analytics-widget").length);

    var index, temp;
    while (counter > 0) {
        index = Math.floor(Math.random() * counter);
        temp  = defaultColors[--counter];

        defaultColors[counter] = defaultColors[index];
        defaultColors[index]   = temp;
    }
    */

    dataviz.library('c3');
    dataviz.chartType(this.getConfig('type', 'area'));
    dataviz.chartOptions(this.getConfig('options', {}));
};

/**
 *
 */
KeenIOWidget.prototype.renderBody = function() {
    var dataviz = this.getDataviz();
    var element = dataviz.el();
    var stackedCharts = ['area', 'line', 'spline'];

    if (typeof element === 'object' && element instanceof HTMLElement) {
        $(element).parent().removeClass("data-loading");

        switch (this.getType()) {
            case 'metric':
                element.innerHTML = this.getMetricMarkup();
                break;
            default:
                dataviz.parseRawData({result: this.getData()});

                if (dataviz.labels().length > 1) {
                    dataviz.stacked(true);
                } else {
                    var index = $(element).parent(".analytics-widget").index();
                    var colors = dataviz.colors();
                    dataviz.colors([colors[(index) % colors.length]]);
                }

                if (dataviz.view._rendered) {
                    dataviz.update();
                } else {
                    dataviz.render();
                }
        }
    } else {
        throw 'No valid dataviz element';
    }
};

/**
 * @param {function} [callback]
 */
KeenIOWidget.prototype.runQuery = function(callback) {
    var client = this.getClient();
    var widget = this;

    var updateParams = {
        timeframe: this.getRange()
    };

    if (this.getType() !== 'metric') {
        updateParams.interval = this.getInterval();
    }

    this.updateQueryParams(updateParams);

    var query  = this.getQuery();

    client.run(query, function(error, analyses) {
        if (error === null) {
            var result = widget.getQueryResult(analyses, query);

            widget.setData(result);

            if (typeof callback === 'function') {
                var boundCallback = callback.bind(widget);
                boundCallback();
            }
        }
    });
};

/**
 * @param {object} analyses
 * @param {Array|object} query
 * @return Array
 */
KeenIOWidget.prototype.getQueryResult = function(analyses, query) {
    var callback = this.getCallback();
    var result = [];

    if (typeof analyses !== 'undefined' && typeof query !== 'undefined') {
        if (Array.isArray(analyses) && analyses.length > 0) {
            var primaryResult = analyses.shift().result;

            if (analyses.length >= 1) {
                for (var i = 0; i < primaryResult.length; i++) {
                    var intervalValues = [];
                    intervalValues.push({
                        category: query[0].title,
                        result  : primaryResult[i].value
                    });

                    for (var x = 0; x < analyses.length; x++) {
                        // Compensate for popping primaryResult off the top.
                        var offsetComp = (x + 1);
                        intervalValues.push({
                            category: query[offsetComp].title,
                            result  : analyses[x].result[i].value
                        });
                    }

                    result.push({
                        timeframe: primaryResult[i].timeframe,
                        value    : intervalValues
                    });
                }
            } else {
                result = primaryResult;
                result.category = query[0].title;
            }
        } else if (typeof analyses === 'object') {
            if (typeof analyses.result !== 'undefined') {
                if (Array.isArray(analyses.result)) {
                    result = analyses.result;
                    result.category = query[0].title;
                } else {
                    result = analyses.result;
                }
            }
        }
    }

    if (callback) {
        result = callback(result);
    }

    return result;
};

/**
 * Write the markup for the widget to the provided container.
 * @param {object} container
 * @param {boolean} [forceNewElement]
 * @return string
 */
KeenIOWidget.prototype.writeContents = function(container, forceNewElement) {
    var client  = this.getClient();
    var dataviz = this.getDataviz();

    forceNewElement = typeof forceNewElement === 'undefined' ? false : !!forceNewElement;

    if (typeof client === 'object') {
        var element = dataviz.el();

        if (forceNewElement || (typeof element !== 'object' && !(element instanceof HTMLElement))) {
            dataviz.el(container);
        }

        if (this.getType() == 'metric') {
            dataviz.height(100);
        }

        if (dataviz.view._prepared === false) {
            dataviz.prepare();
        }

        $(container).parent().addClass("data-loading");
        this.runQuery(this.renderBody);
    }
};
