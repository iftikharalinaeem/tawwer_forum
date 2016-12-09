/**
 * keen.io Analyses processor.
 *
 * @class
 * @param {object} config
 */
KeenIOAnalysesProcessor = function(config) {

    /**
     *
     * @type {object}
     */
    this.processInstructions = {};

    /**
     *
     * @type {null|string}
     */
    this.finalAnalysis = null;

    /**
     * Process the analyses using the provided instructions.
     *
     * Validators: You can apply validators on the processed analysis.
     *      As soon as a validator fails the whole process will return false.
     *
     * "instructions": {
     *     "new-analysis-name": {
     *         "analyses": [0, 1], // Take analyses 0 and 1
     *         "processor": "addResults", // Give them to addResults
     *         "validators": {
     *             "validatorName": [
     *                 ['arg1', 'arg2'], // Call "validatorName" with 'arg1' and 'arg2' as its arguments
     *                 ['arg1v2', 'arg2v2']  // Call "validatorName" with 'arg1v2' and 'arg2v2' as its arguments
     *             ]
     *         }
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

        var success = true;
        $.each(this.processInstructions, function(queryName, properties) {
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

            processedAnalyses[queryName] = that[properties.processor](analysesToProcess);
            if (typeof properties.title !== 'undefined') {
                processedAnalyses[queryName]['title'] = properties.title;
            }

            if (typeof properties.validators !== 'undefined') {
                $.each(properties.validators, function(callbackName, callbacksArgs) {
                    if (typeof that[callbackName] !== 'function') {
                        throw 'Invalid validation callback "'+callbackName+'"';
                    }
                    $.each(callbacksArgs, function(i, args) {
                        var validationArgs = [processedAnalyses[queryName]];
                        if (Array.isArray(args)) {
                            validationArgs = validationArgs.concat(args);
                        }
                        success = that[callbackName].apply(that, validationArgs);
                        // Break the loop as soon as there is a failure.
                        return success;
                    });

                    return success;
                });
            }
            return success;
        });

        return success ? processedAnalyses[this.finalAnalysis] : false;
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
 * Walk through an analysis object and call "Callback" with the current property's name and value.
 * To be used mostly internally by the validator functions.
 *
 * @param analysis
 * @param callback
 */
KeenIOAnalysesProcessor.prototype.walkAnalysis = function(analysis, callback) {
    var that = this;

    // Self executing function
    ~function walk(propertyName, propertyValue, callback) {
        // We will stop the execution as soon as the callback return false.
        var continueRecursion = true;
        if (propertyName !== null) {
            continueRecursion = callback.call(that, propertyName, propertyValue);
        }
        // Make sure that the value is something that we can iterate on.
        if (continueRecursion !== false && Array.isArray(propertyValue) || $.isPlainObject(propertyValue)) {
            $.each(propertyValue, function(property, value) {
                continueRecursion = walk(property, value, callback);
                return continueRecursion !== false;
            });
        }
        return continueRecursion !== false;
    }(null, (analysis.result || analysis), callback);
}

/**
 * Check the result(s) property of an analysis to make sure that we have something else than 0.
 *
 * @param analysis
 * @returns {boolean}
 */
KeenIOAnalysesProcessor.prototype.validateResultsNotEmptyish = function(analysis) {
    var emptyish = true;

    this.walkAnalysis(analysis, function/*checkResultEmptyness*/(propertyName, propertyValue) {
        if (propertyName !== 'result') {
            return;
        }

        if (typeof propertyValue === 'number' && propertyValue !== 0) {
            emptyish = false;
        } else {
            throw 'Unhandled case!';
        }
        return emptyish;
    });

    return !emptyish;
}

/**
 * Check that an analysis contains propertyName with propertyValue.
 *
 * @param analysis
 * @param propertyName
 * @param propertyValue
 * @returns {boolean}
 */
KeenIOAnalysesProcessor.prototype.validatePropertyValueExisting = function(analysis, propertyName, propertyValue) {
    var existing = false;

    this.walkAnalysis(analysis, function/*checkProperyValueExisting*/(propName, propValue) {
        if (propName === propertyName && propValue === propertyValue) {
            existing = true;
        }
        return !existing;
    });

    return existing;
}

/**
 * Do nothing and return the analyses
 *
 * @param {array} analyses
 * @return {object}
 */
KeenIOAnalysesProcessor.prototype.noop = function(analyses) {
    return analyses;
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

/**
 * Merge results together
 *
 * @param {array} analyses
 * @return {object}
 */
KeenIOAnalysesProcessor.prototype.mergeResults = function(analyses) {
    if (!Array.isArray(analyses) || analyses.length < 2) {
        throw 'mergeResults requires an array of results';
    }

    var mergedAnalyses = {
        'result': []
    };

    $.each(analyses, function(index, element) {
        mergedAnalyses.result.push(element)
    });

    return mergedAnalyses;
};
