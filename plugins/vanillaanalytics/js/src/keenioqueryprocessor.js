KeenIOQueryProcessor = function(config) {

    var process = null;
    var resultQuery = null;

    this.getResult = function(queries) {
        var that = this;
        var processedQueries = {};

        $.each(this.process, function(name, config) {
            var queriesToProcess = [];

            $.each(config.queries, function(index, queryIdentifier) {
                // Raw query index number
                if (Number.isInteger(queryIdentifier)) {
                    queriesToProcess.push(queries[queryIdentifier]);
                // Processed query name
                } else {
                    if (typeof processedQueries[queryIdentifier] === 'undefined') {
                        throw 'Invalid processed query name';
                    }
                    queriesToProcess.push(processedQueries[queryIdentifier]);
                }
            });

            processedQueries[name] = that[config.processor](queriesToProcess);
        });

        return processedQueries[this.resultQuery];
    };

    this.loadConfig(config);
};

KeenIOQueryProcessor.prototype.loadConfig = function(config) {
    if (typeof config !== 'object') {
        throw 'Invalid KeenIOQueryProcessor config';
    }

    if (typeof config.process === 'undefined') {
        throw 'Missing process in KeenIOQueryProcessor config';
    }

    if (typeof config.resultQuery === 'undefined') {
        throw 'Missing resultQuery in KeenIOQueryProcessor config';
    }

    this.process = config.process;
    this.resultQuery = config.resultQuery;
};

KeenIOQueryProcessor.prototype.addMetrics = function(results) {
    if (!Array.isArray(results)) {
        throw 'addMetrics requires an array';
    }

    if (results.length < 2) {
        throw 'addMetrics requires at least two results';
    }

    var mergedMetrics = {};

    $.each(results, function(index, element) {
        if (typeof element.result !== 'number') {
            throw 'addMetrics only works with metrics';
        }

        if (index == 0) {
            mergedMetrics['result'] = element.result;
        } else {
            mergedMetrics['result'] += element.result;
        }
    });


    return mergedMetrics;
};

KeenIOQueryProcessor.prototype.divideMetrics = function(results) {
    if (!Array.isArray(results)) {
        throw 'divideMetrics requires an array';
    }

    if (results.length < 2) {
        throw 'divideMetrics requires at least two results';
    }

    var mergedMetrics = {};

    $.each(results, function(index, element) {
        if (typeof element.result !== 'number') {
            throw 'addMetrics only works with metrics';
        }

        if (index == 0) {
            mergedMetrics['result'] = element.result;
        } else {
            if (element.result != 0) {
                mergedMetrics['result'] /= element.result;
            } else {
                mergedMetrics['result'] = 0;
            }
        }
    });


    return mergedMetrics;
};
