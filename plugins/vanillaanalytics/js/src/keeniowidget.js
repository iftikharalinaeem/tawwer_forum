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
     *
     * @type {null|object}
     */
    var analysesProcessor = null;

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
            maxAge          : 86400
        };

        if (typeof config.filters !== "undefined" && Array.isArray(config.filters) && config.filters.length > 0) {
            queryParams.filters = config.filters;
        }

        if (typeof config.groupBy !== 'undefined' && config.groupBy !== null) {
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
     *
     * @returns {KeenIOAnalysesProcessor}
     */
    this.getAnalysesProcessor = function() {
        return this.analysesProcessor;
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
        if (typeof this[newCallback] !== 'function') {
            throw 'Invalid value for newCallback';
        }

        callback = this[newCallback];
        return this;
    };

    /**
     *
     * @param {KeenIOAnalysesProcessor} analysesProcessor
     * @returns {KeenIOWidget}
     */
    this.setAnalysesProcessor = function(analysesProcessor) {
        this.analysesProcessor = analysesProcessor;
        return this;
    };

    this.setChartConfig= function(newChartConfig) {
        if (typeof newChartConfig !== 'undefined') {
            chartConfig = newChartConfig;
            return this;
        } else {
            throw 'Invalid newChartConfig';
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

        var defaultRange = analyticsToolbar.getDefaultRange();
        var end = newRange.end || false;
        var start = newRange.start || false;

        if (typeof end !== 'object' || !(end instanceof Date)) {
            range.end = defaultRange.end;
        } else {
            range.end = end;
        }

        if (typeof start !== 'object' || !(start instanceof Date)) {
            range.start = defaultRange.start;
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

KeenIOWidget.prototype.getMetricMarkup = function() {
    var markup = "<div class=\"metric-value\">{data}</div><div class=\"metric-title\">{title}</div>";
    var data = this.getData();
    var title = this.getTitle();

    if (typeof data === "number") {
        data = Number(data).toLocaleString();
    }

    markup = markup.replace("{data}", data);
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

    if (typeof config.callback !== 'undefined') {
        this.setCallback(config.callback);
    }

    if (typeof config.queryProcessor !== 'undefined') {
        this.setAnalysesProcessor(new KeenIOAnalysesProcessor(config.queryProcessor));
    }
};

/**
 * @param {object} [config]
 */
KeenIOWidget.prototype.loadDatavizConfig = function (config) {
    var dataviz = this.getDataviz();
    var chartOptions = this.getConfig('options', {});
    var labelMapping = this.getConfig('labelMapping');

    dataviz.library('c3');
    // Move this into defaultOptions after https://github.com/keen/keen-js/issues/420 is fixed.
    c3.chart.internal.fn.additionalConfig = {
        axis_y_tick_format: function (n) { return (n % 1 === 0) ? n : parseFloat(n).toFixed(2); }
    };

    if (this.getType() == 'metric') {
        dataviz.height(this.getConfig('height', 85));
    } else {
        var chartType = this.getConfig('chartType', 'area');
        dataviz.chartType(chartType);

        var defaultOptions = {};
        switch(chartType) {
            case 'bar':
            case 'area':
            case 'line':
                defaultOptions = {
                    axis: {
                        x: {
                            type: 'timeseries',
                            tick: {
                                count: 5,
                                format: '%Y-%m-%d'
                            }
                        },
                    },
                    grid: {
                        x: {
                            show: true
                        },
                        y: {
                            show: true
                        }
                    },
                    tooltip_contents: function (d, defaultTitleFormat, defaultValueFormat, color) {
                        var text = '<div class="popover popover-analytics">' +
                            '<div class="title">' + new Date(d[0].x).toLocaleDateString("en-US") + '</div>' +
                            '<div class="body">';
                        for (var i = 0; i < d.length; i++) {
                            if (text.length === 0) {
                            }

                            if (!(d[i] && (d[i].value || d[i].value === 0))) {
                                continue;
                            }

                            text += "<div class='flex popover-row popover-name-" + d[i].id + "'>";
                            text += "<div class='name'>" + d[i].name + "</div>";
                            text += "<div class='value'><span style='color:" + color(d[i].id) + "'>"
                                        + defaultValueFormat(d[i].value, d[i].ratio, d[i].id, d[i].index)
                                    + "</span></div>";
                            text += "</div>";
                        }
                        return text + '</div></div>';
                    }
                };

                if (chartType === 'bar') {
                    delete defaultOptions.axis.x.tick.count;
                }
                break;
            case 'pie':
                defaultOptions = {
                    tooltip_contents: function (d, defaultTitleFormat, defaultValueFormat, color) {
                        return '<div class="popover popover-analytics">' +
                                '<div class="title">' + d[0].name + '</div>' +
                                '<div class="body">' + defaultValueFormat(d[0].value, d[0].ratio, d[0].id, d[0].index) + ' (' + d[0].value + ')</div>' +
                            '</div>';
                    }
                };
                break;
            default:
                throw 'Chart type '+chartType+' options not handled!';
        }

        chartOptions = $.extend(true, defaultOptions, chartOptions);
    }
    dataviz.chartOptions(chartOptions);
    dataviz.dateFormat('%Y-%m-%d');
    dataviz.labelMapping(labelMapping);
};

/**
 *
 * @param {Number} value
 * @return {Array}
 */
KeenIOWidget.prototype.formatPercent = function(value) {
    return ((value * 100).toFixed(1))+'%';
};

/**
 * @param {number} totalSeconds
 * @return {string}
 */
KeenIOWidget.prototype.formatSeconds = function (totalSeconds) {
    if (typeof totalSeconds !== "number") {
        return "-";
    }

    var hours   = Math.floor(totalSeconds / 3600);
    var minutes = Math.floor((totalSeconds - (hours * 3600)) / 60);
    var seconds = Math.floor(totalSeconds - (hours * 3600) - (minutes * 60));

    var result = "";
    if (hours > 0) {
        result += hours.toString() + "h";
        result += minutes.toString() + "m";
    } else {
        result += minutes.toString() + "m";
        result += seconds.toString() + "s";
    }

    return result;
};

/**
 *
 */
KeenIOWidget.prototype.renderBody = function() {
    var dataviz = this.getDataviz();
    var element = dataviz.el();

    if (typeof element === 'object' && element instanceof HTMLElement) {
        $(element).parent().removeClass('data-loading');

        switch (this.getType()) {
            case 'metric':
                if (!this.getData()) {
                    this.setData('N/A');
                }
                element.innerHTML = this.getMetricMarkup();
                $(element).trigger('contentLoad');
                break;
            default:
                if (this.getData()) {
                    dataviz.parseRawData({result: this.getData()});
                }

                var labels = dataviz.labels();

                if (labels.length > 1) {
                    dataviz.stacked(true);
                } else {
                    var index = $(element).parent(".analytics-widget").index();
                    var colors = dataviz.colors();
                    dataviz.colors([colors[(index) % colors.length]]);
                }

                for (var x = 0; x < labels.length; x++) {
                    if (labels[x] === "") {
                        labels[x] = "(None)";
                    }
                }
                dataviz.labels(labels);

                // Workaround until https://github.com/keen/keen-js/issues/420 is fixed.
                var chartOptions = dataviz.chartOptions();
                var oldTooltipContents = c3.chart.internal.fn.additionalConfig.tooltip_contents;
                if (typeof chartOptions.tooltip_contents !== 'undefined') {
                    c3.chart.internal.fn.additionalConfig.tooltip_contents = chartOptions.tooltip_contents;
                }

                if (dataviz.view._rendered) {
                    dataviz.update();
                } else {
                    dataviz.render();
                }

                // Restore original tooltip_contents
                c3.chart.internal.fn.additionalConfig.tooltip_contents = oldTooltipContents;
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
        // TODO: We should probably check the query instead of the chart type.
        if ($.inArray(this.getConfig('chartType', 'area'), ['area', 'line', 'bar']) !== -1) {
            updateParams.interval = this.getInterval();
        }
    }

    this.updateQueryParams(updateParams);

    var queries  = this.getQuery();
    if (!!queries.length) {
        // Execute filters' property_callback and assign the result to property_value
        $.each(queries, function(i, query) {
            if (typeof query.params.filters !== 'undefined' && Array.isArray(query.params.filters) && !!query.params.filters.length) {
                $.each(query.params.filters, function(j, filter) {
                    $.each(filter, function(property, value) {
                        if (property === 'property_callback') {
                            if (typeof KeenIOFilterCallback[value] !== 'function') {
                                throw 'Invalid filter callback KeenIOFilterCallback.'+value;
                            }
                            delete queries[i].params.filters[j][property];
                            queries[i].params.filters[j]['property_value'] = KeenIOFilterCallback[value](widget);
                        }
                    });
                });
            }
        });
    }

    client.run(queries, function(error, analyses) {
        if (error === null) {
            var result = widget.getQueryResult(analyses, queries);

            if (result !== null || window.analyticsDashboard.isPersonal()) {
                $(widget.getDataviz().el()).parent().show();
                widget.setData(result);
                if (typeof callback === 'function') {
                    var boundCallback = callback.bind(widget);
                    boundCallback();
                }
            } else {
                $(widget.getDataviz().el()).parent().hide();
            }
        }
    });
};

/**
 * @param {object} analyses
 * @param {Array|object} query
 * @return Array|null
 */
KeenIOWidget.prototype.getQueryResult = function(analyses, query) {
    var callback = this.getCallback();
    var queryResult = null;

    if (typeof analyses !== 'undefined' && typeof query !== 'undefined') {

        if (Array.isArray(analyses)) {
            // Add query title to analysis
            $.each(analyses, function(index, analysis) {
                var title = null;

                if (query[index].title !== 'undefined') {
                    title = query[index].title;
                }

                analysis.title = title;
            });
        } else if (typeof analyses === 'object') {
            var title = null;

            if (query[0].title !== 'undefined') {
                title = query[0].title;
            }

            analyses.title = title;
            analyses = [analyses];
        }

        if (this.analysesProcessor) {
            analyses = this.analysesProcessor.process(analyses);
        }

        if (analyses !== false) {
            var analyse = null;
            if (Array.isArray(analyses) && !!analyses.length) { // Multiple analyses
                if (analyses.length > 1) {
                    throw 'Multiple analyses detected. Use an AnalysesProcessor to merge them';
                }
                analyse = analyses[0];
            } else if (typeof analyses === 'object') {
                analyse = analyses;
            }

            queryResult = analyse.result;

            if (callback) {
                queryResult = callback(queryResult);
            }
        }
    }

    return queryResult;
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

        if (dataviz.view._prepared === false) {
            dataviz.prepare();
        }

        $(container).parent().addClass("data-loading");

        switch (this.getType()) {
            case 'leaderboard':
                var widgetID = AnalyticsWidget.getIDFromAttribute($(container).parent().attr('id'));
                var range = this.getRange();
                var parameters = {
                    Start: range.start,
                    End: range.end
                };
                var url = gdn.url("/analytics/leaderboard/" + widgetID);
                AnalyticsWidget.popin(container, url, parameters);
                break;
            default:
                this.runQuery(this.renderBody);
        }
    }
};
