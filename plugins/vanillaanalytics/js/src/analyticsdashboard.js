/**
 * Create a new analytics dashboard object.
 * @class
 * @param {object} config Configuration details for this dashboard.
 * @param {object} [start] A Date object representing the start of the date range.
 * @param {object} [end] A Date object representing the end of the date range.
 * @param {number} [initialCategoryID] A category's unique ID to limit the results to.
 */
function AnalyticsDashboard (config, start, end, initialCategoryID) {

    /**
     * Category ID for this dashboard.
     * @access private
     * @type {null}
     */
    var categoryID = null;

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
     * Is this a user's personal dashboard?
     * @access private
     * @type {boolean}
     */
    var personal = false;

    /**
     * A collection of two date objects: start and end.  Used for applying a timeframe to queries.
     * @access private
     * @type {object}
     * */
    var timeframe = {
        end   : null,
        start : null
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
            var widgets;

            if (typeof config.widgets !== 'undefined' && Array.isArray(config.widgets)) {
                widgets = [];
                for (var i = 0; i < config.widgets.length; i++) {
                    config.widgets[i].timeframe = this.getTimeframe();
                    widgets.push(new AnalyticsWidget(config.widgets[i]));
                }
                delete config.widgets;
            } else {
                widgets = [];
            }

            config.widgets = widgets;
            return panels[panelID] = config;
        } else {
            return false;
        }
    };

    /**
     * Fetch the category ID for this dashboard.
     * @returns {null}
     */
    this.getCategoryID = function() {
        return categoryID;
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
        if (timeframe.start === null || timeframe.end === null) {
            return analyticsToolbar.getDefaultRange();
        }

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
     * Fetch whether or not this is a user's personal dashboard.
     * @returns {boolean}
     */
    this.isPersonal = function() {
        return personal;
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
     * Update this dashboard's category ID.
     * @param {null|number} newCategoryID New category ID.
     * @returns {boolean|null|number}
     */
    this.setCategoryID = function(newCategoryID) {
        if (typeof newCategoryID === 'undefined') {
            return false;
        }

        this.categoryID = newCategoryID === null ? null : parseInt(newCategoryID);
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
     * Set the flag determining whether or not this is a user's personal dashboard.
     * @param {boolean} newPersonal
     * @returns {boolean}
     */
    this.setPersonal = function(newPersonal) {
        if (typeof newPersonal !== 'boolean') {
            return false;
        }

        return personal = newPersonal;
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

    // Attempt to use incoming dates, if available.
    if (start instanceof Date && end instanceof Date) {
        this.setTimeframe(start, end);
    }

    if (typeof initialCategoryID !== 'undefined') {
        this.setCategoryID(initialCategoryID);
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
 * @throws Throw an error when an invalid config is provided.
 */
AnalyticsDashboard.prototype.loadConfig = function(config) {
    if (typeof config !== 'object') {
        throw 'Invalid dashboard config';
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

    if (typeof config.personal !== 'undefined') {
        this.setPersonal(config.personal);
    }

    if (typeof config.title == 'string') {
        this.setTitle(config.title);
    }
};

/**
 * Remove a widget, by ID, from the dashboard.
 *
 * @param {string} widgetID
 */
AnalyticsDashboard.prototype.removeWidget = function(widgetID) {
    var panels = this.getPanel();
    for (var panelID in this.getPanel()) {
        var panelContainerID = 'analytics_panel_' + panelID;
        var panelContainer = document.getElementById(panelContainerID);

        if (typeof panelContainer !== 'object') {
            return;
        }

        var widgets = panels[panelID].widgets;
        if (widgets.length === 0) {
            continue;
        }

        for (var i = 0; i < widgets.length; i++) {
            if (widgets[i].getWidgetID() === widgetID) {
                var widgetContainer = widgets[i].getElements('container');
                if (widgetContainer instanceof HTMLElement) {
                    panelContainer.removeChild(widgetContainer);
                }
                widgets.splice(i, 1);
            }
        }
    }
};

/**
 *
 */
AnalyticsDashboard.prototype.setupSorting = function() {
    if (typeof $.fn.sortable === 'undefined') {
        return;
    }

    $(".analytics-panel-charts .analytics-widget-options").append('<span class="analytics-widget-move">');

    $(".analytics-panel-charts").sortable({
        handle: ".analytics-widget-move",
        update: this.sortUpdate.bind(this)
    });

    $(".analytics-panel-charts").disableSelection();

    $(".analytics-panel-metrics").sortable({
        handle: ".body",
        update: this.sortUpdate.bind(this)
    });

    $(".analytics-panel-metrics").disableSelection();
};

AnalyticsDashboard.prototype.sortUpdate = function(e, ui) {
    var elements = $(e.target).sortable("toArray");
    var widgets  = {};

    var widgetID;
    for (var i = 0; i < elements.length; i++) {
        widgetID = AnalyticsWidget.getIDFromAttribute(elements[i]);
        if (widgetID) {
            widgets[widgetID] = (i + 1);
        }
    }

    $.post(
        gdn.url("/settings/analytics/dashboardsort/" + this.getDashboardID()),
        {
            TransientKey: gdn.definition("TransientKey"),
            Widgets     : widgets
        }
    );
};

/**
 * Write the dashboard's contents to the current page.
 */
AnalyticsDashboard.prototype.writeDashboard = function() {
    for (var panelID in this.getPanel()) {
        this.emptyPanelContainer(panelID);
        this.writePanel(panelID);
    }

    if (this.isPersonal()) {
        this.setupSorting();
    }
};

/**
 * Write a panel's contents to an existing HTML element on the page.
 * @param {number|string} panelID The unique identifier for the panel to be written.
 * @throws Throw an error when unable to retrieve the specified panel configuration.
 * @throws Throw an error if the panel's document element cannot be found.
 * @throws Throw an error if the panel has no widgets array.
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
        panel.widgets[i].render();
        panelContainer.appendChild(panel.widgets[i].getElements('container'));
    }
};
