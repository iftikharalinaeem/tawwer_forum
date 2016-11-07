/**
 * keen.io Filter callback.
 *
 * @class
 * @param {object} config
 */
KeenIOFilterCallback = {
    /**
     * Get the widget's selected timeframe's start timestamp
     *
     * @param widget
     * @returns {number}
     */
    timeframeStartTimestamp: function(widget) {
        return (new Date(widget.getRange()['start']).getTime() / 1000);
    }
};
