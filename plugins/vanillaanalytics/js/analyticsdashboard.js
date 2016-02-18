/**
 * Create a new analytics dashboard object.
 * @class
 * @param {object} config Configuration details for this dashboard.
 */
function AnalyticsDashboard (config, start, end) {
    /**
     * Unique identifier for this dashboard.
     * @access private
     * @type {number|string}
     * */
    var dashboardID = '';

    /**
     * A collection of panels in this dashboard.
     * @access private
     * @type {object}
     * */
    var panels = {};

    /**
     * A collection of two date objects: start and end.  Used for applying a timeframe to queries.
     * @access private
     * @type {object}
     * */
    var timeframe = {
        end   : new Date(),
        start : new Date()
    };

    /**
     * The title of this dashboard.
     * @access private
     * @type {string}
     * */
    var title = '';

    /**
     * Add a panel to this dashboard.
     * @param {number|string} panelID Unique identifier for this panel.
     * @param {object} config Configuration object for the panel.
     * @returns {boolean|object} The newly added panel on success.  False on failure.
     */
    this.addPanel = function(panelID, config) {
        if (typeof config === 'object') {
            return panels[panelID] = config;
        } else {
            return false;
        }
    };

    /**
     * Fetch the current dashboard's unique identifier.
     * @returns {number|string}
     */
    this.getDashboardID = function() {
        return dashboardID;
    };

    /**
     * Fetch one or all of the dashboard's panels.
     * @param {number|string} [panelID] Unique identifier for the specific panel being retrieved.
     * @returns {boolean|object} Collection of all panels or one specific panel.  False on error.
     */
    this.getPanel = function(panelID) {
        if (typeof panelID === 'undefined') {
            return panels;
        }

        return panels[panelID] || false;
    };

    /**
     * Fetch the currently-configured timeframe details.
     * @returns {object}
     */
    this.getTimeframe = function() {
        return timeframe;
    };

    /**
     * Fetch the currently-configured dashboard title.
     * @returns {string}
     */
    this.getTitle = function() {
        return title;
    };

    /**
     * Empty the current dashboard's panels collection.
     * @param {boolean} [emptyContainers] Clear out the panels' document containers, if present?
     * @returns {object} The current (empty) panel collection.
     */
    this.resetPanels = function(emptyContainers) {
        if (emptyContainers === true) {
            for (var panelID in this.getPanel()) {
                this.emptyPanelContainer(panelID);
            }
        }

        return panels = {};
    };

    /**
     * Update this dashboard's unique identifier.
     * @param {number|string} newDashboardID This dashboard's new identifier.
     * @returns {boolean|number|string} Unique identifier, if properly set.  False on error.
     */
    this.setDashboardID = function(newDashboardID) {
        if (typeof newDashboardID !== 'string' && typeof newDashboardID !== 'number') {
            return false;
        }

        return dashboardID = newDashboardID;
    };

    /**
     * Update the dashboard's timeframe.
     * @param {Date} newStart The timeframe's start date.
     * @param {Date} newEnd The timeframe's end date.
     * @throws Throw an error if either newStart or newEnd are not Date objects.
     * @throws Throw an error if newEnd is before or equal to newStart.
     * @returns {object} The updated timeframe object.
     */
    this.setTimeframe = function(newStart, newEnd) {
        if (typeof newStart !== 'object' || !(newStart instanceof Date)) {
            throw 'Invalid start date';
        }

        if (typeof newEnd !== 'object' || !(newEnd instanceof Date)) {
            throw 'Invalid end date';
        }

        if (newStart >= newEnd) {
            throw 'Invalid date range. Start equals or surpasses end.';
        }

        timeframe.start = newStart;
        timeframe.end   = newEnd;

        return timeframe;
    };

    /**
     * Set the dashboard's title.
     * @param {string} newTitle Updated title for this dashboard.
     * @returns {boolean|string} New title on success.  False on failure.
     */
    this.setTitle = function(newTitle) {
        if (typeof newTitle !== 'string') {
            return false;
        }

        return title = newTitle;
    };

    // Default timeframe from previous month, until current day.
    timeframe.start.setHours(0, 0, 0);
    timeframe.start.setMonth(timeframe.end.getMonth() - 1);

    // Attempt to use incoming dates, if available.
    if (start instanceof Date && end instanceof Date) {
        this.setTimeframe(start, end);
    }

    // Parse the config object for dashboard properties.
    this.loadConfig(config);
}

/**
 * Empty the contents of the HTML element related to the specified panel.
 * @param {number|string} panelID Unique identifier for the panel to empty.
 * @returns {boolean} False if element does not exist.  Otherwise true.
 */
AnalyticsDashboard.prototype.emptyPanelContainer = function(panelID) {
    // We expect an element to exist with the format of "analytics_panel_[panelID]".
    var elementID = 'analytics_panel_' + panelID;
    var element = document.getElementById(elementID);

    if (typeof element !== 'object') {
        return false;
    }

    // If we have child nodes, iterate through them and remove them from our container.
    if (element.hasChildNodes()) {
        for (i = 0; i < element.childNodes.length; i++) {
            element.removeChild(element.childNodes[i]);
        }
    }

    return true;
};

/**
 * Read the incoming config object and update our dashboard with its values.
 * @param {object} config A properly-formatted object containing our dashboard settings.
 */
AnalyticsDashboard.prototype.loadConfig = function(config) {
    if (typeof config !== 'object') {
        return;
    }

    if (typeof config.dashboardID !== 'undefined') {
        this.setDashboardID(config.dashboardID);
    }

    if (typeof config.panels === 'object') {
        var configPanels = Object.getOwnPropertyNames(config.panels);
        var panelID;

        // It is assumed we are overwriting, not appending, the existing panel collection.
        this.resetPanels();

        for (var i = 0; i < configPanels.length; i++) {
            panelID = configPanels[i];
            this.addPanel(panelID, config.panels[panelID]);
        }
    }

    if (typeof config.title == 'string') {
        this.setTitle(config.title);
    }
};

/**
 * Write the dashboard's contents to the current page.
 */
AnalyticsDashboard.prototype.writeDashboard = function() {
    for (var panelID in this.getPanel()) {
        this.emptyPanelContainer(panelID);
        this.writePanel(panelID);
    }
};

/**
 * Write a panel's contents to an existing HTML element on the page.
 * @param {number|string} panelID The unique identifier for the panel to be written.
 */
AnalyticsDashboard.prototype.writePanel = function(panelID) {
    var panel = this.getPanel(panelID);

    if (panel === false) {
        throw 'Invalid panel: ' + panelID;
    }

    // We aren't creating a new panel, so it has to already exist on the page.
    var panelContainerID = 'analytics_panel_' + panelID;
    var panelContainer = document.getElementById(panelContainerID);

    if (typeof panelContainer !== 'object') {
        throw 'Unable to find container: #' + panelContainerID;
    }

    if (typeof panel.widgets === 'undefined' || !Array.isArray(panel.widgets)) {
        throw 'Panel does not contain a widget array.';
    }

    for (var i = 0; i < panel.widgets.length; i++) {
        this.writeWidget(panel.widgets[i], panelContainer);
    }
};

/**
 * Output a widget's contents to the specified container.
 * @param {object} widgetConfig The configuration values for the widget.
 * @param {object} container An object representing an HTML element where the widget will be written.
 */
AnalyticsDashboard.prototype.writeWidget = function(widgetConfig, container) {
    if (typeof widgetConfig !== 'object') {
        throw 'Invalid widget config';
    }

    if (typeof container !== 'object') {
        throw 'Invalid widget container.'
    }

    // Extract all of the necessary information from widgetConfig.
    var data = widgetConfig.data || [];
    var handler = widgetConfig.handler || false;
    var title = widgetConfig.title || '';
    var type = widgetConfig.type || false;
    var widgetID = widgetConfig.widgetID || false;
    var dashboardTimeframe = this.getTimeframe();

    // We need a class available to handle the widget.  Verify we have one available on the page.
    if (typeof window[handler] === 'function') {
        // These three pieces of information are vital.  Do not attempt to output without them.
        if (handler || widgetID || type) {
            // Setup an instance of our widget object.
            var trackerWidget = new window[handler](dashboardTimeframe.start, dashboardTimeframe.end, data, type, title);

            // Setup the document elements we'll be using.
            var widget = {
                body:      document.createElement('div'),
                container: document.createElement('div'),
                title:     document.createElement('h4')
            };

            widget.container.setAttribute('id', 'analytics_widget_' + widgetID);
            widget.container.setAttribute('class', 'analytics-widget analytics-widget-' + type);

            // Metrics are a special case where a title is redundant.  Otherwise, we need a title element.
            if (type !== 'metric') {
                widget.title.setAttribute('class', 'title');
                widget.title.innerHTML = title;
                widget.container.appendChild(widget.title);
            }

            widget.body.setAttribute('class', 'body');
            trackerWidget.writeContents(widget.body);
            widget.container.appendChild(widget.body);

            container.appendChild(widget.container);
        }
    } else {
        throw 'Failed to locate handler: ' + handler;
    }
};
