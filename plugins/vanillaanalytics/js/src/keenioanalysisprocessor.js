/**
 * keen.io Analyses processor.
 *
 * @class
 * @param {object} config
 */
KeenIOAnalysesProcessor = function(config) {

    /**
     *
     * @type {null|object}
     */
    var processInstructions = null;

    /**
     *
     * @type {null|string}
     */
    var finalAnalysis = null;

    /**
     * Process the analyses using the provided instructions
     *
     * "instructions": {
     *     "new-analysis-name": {
     *         "analyses": [0, 1], // Take analyses 0 and 1
     *         "processor": "addResults" // Give them to addResults
     *     },
     *     "new-analysis-name2": {
     *         "analyses": ["new-analysis-name", 2], // Take processed "new-analysis-name" and raw analysis 2
     *         "processor": "divideResults" // Give them to addResults
     *     }
     *  ],
     *
     * @param {array} analyses
     * @returns {object}
     */
    this.process = function(analyses) {
        var that = this;
        var processedAnalyses = {};

        $.each(this.processInstructions, function(name, properties) {
            var analysesToProcess = [];

            $.each(properties.analyses, function(index, analysisIdentifier) {
                // Raw analysis index number
                if (Number.isInteger(analysisIdentifier)) {
                    analysesToProcess.push(analyses[analysisIdentifier]);
                // Processed query name
                } else {
                    if (typeof processedAnalyses[analysisIdentifier] === 'undefined') {
                        throw 'Invalid processed query name';
                    }
                    analysesToProcess.push(processedAnalyses[analysisIdentifier]);
                }
            });

            processedAnalyses[name] = that[properties.processor](analysesToProcess);
            if (typeof properties.title !== 'undefined') {
                processedAnalyses[name]['title'] = properties.title;
            }
        });

        return processedAnalyses[this.finalAnalysis];
    };

    this.loadConfig(config);
};

/**
 *
 * @param {object} config
 */
KeenIOAnalysesProcessor.prototype.loadConfig = function(config) {
    if (typeof config !== 'object') {
        throw 'Invalid KeenIOAnalysesProcessor config';
    }

    if (typeof config.instructions === 'undefined') {
        throw 'Missing process in KeenIOAnalysesProcessor config';
    }

    if (typeof config.finalAnalysis === 'undefined') {
        throw 'Missing finalAnalysis in KeenIOAnalysesProcessor config';
    }

    this.processInstructions = config.instructions;
    this.finalAnalysis = config.finalAnalysis;
};

/**
 * Add results together
 *
 * @param {array} analyses
 * @return {object}
 */
KeenIOAnalysesProcessor.prototype.addResults = function(analyses) {
    if (!Array.isArray(analyses) || analyses.length < 2) {
        throw 'addResults requires an array of results';
    }

    var resultType = 'singleValue';
    if (Array.isArray(analyses[0].result)) {
        resultType = 'multipleValue';
    }

    var mergedResults = analyses.shift();

    $.each(analyses, function(index, element) {
        if (resultType === 'singleValue') {
            mergedResults['result'] += element.result;
        } else {
            $.each(element.result, function(index, result) {
                mergedResults['result'][index]['value'] /= result.value;
            });
        }
    });

    return mergedResults;
};

/**
 * Divide the first result by the other results
 *
 * @param {array} analyses
 * @return {object}
 */
KeenIOAnalysesProcessor.prototype.divideResults = function(analyses) {
    if (!Array.isArray(analyses) || analyses.length < 2) {
        throw 'divideResults requires an array of results';
    }

    var resultType = 'singleValue';
    if (Array.isArray(analyses[0].result)) {
        resultType = 'multipleValue';
    }

    var mergedResults = analyses.shift();

    $.each(analyses, function(index, element) {
        if (resultType === 'singleValue') {
            if (element.result != 0) {
                mergedResults['result'] /= element.result;
            } else {
                mergedResults['result'] = 0;
            }
        } else {
            $.each(element.result, function(index, result) {
                if (result.value != 0) {
                    mergedResults['result'][index]['value'] /= result.value;
                } else {
                    mergedResults['result'][index]['value'] = 0;
                }
            });
        }
    });

    return mergedResults;
};
