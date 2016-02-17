/**
 * @class
 * @param {object} config Configuration details for this dashboard.
 */
function AnalyticsDashboard (config, start, end) {
    this.dashboardID = '';
    this.panels      = {};
    this.timeframe   = {
        end   : new Date(),
        start : new Date()
    };
    this.title       = '';

    if (start instanceof Date && end instanceof Date) {
        this.setTimeframe(start, end);
    } else {
        // Setup the default timespan: one month ago, up to today.
        this.timeframe.start.setHours(0, 0, 0);
        this.timeframe.start.setMonth(this.timeframe.end.getMonth() - 1);
    }

    this.loadConfig(config);
}

AnalyticsDashboard.prototype.addPanel = function(panelID, config) {
    if (typeof config === 'object') {
        this.panels[panelID] = config;
        return true;
    } else {
        return false;
    }
};

AnalyticsDashboard.prototype.clearPanel = function(panelID) {
    var elementID = 'analytics_panel_' + panelID;
    var element = document.getElementById(elementID);

    if (typeof element !== 'object') {
        return false;
    }

    if (element.hasChildNodes()) {
        for (i = 0; i < element.childNodes.length; i++) {
            element.removeChild(element.childNodes[i]);
        }
    }

    return true;
};

AnalyticsDashboard.prototype.getPanel = function(panelID) {
    if (typeof panelID === 'undefined') {
        return this.panels;
    }

    return this.panels[panelID] || false;
};

AnalyticsDashboard.prototype.loadConfig = function(config) {
    if (typeof config !== 'object') {
        return;
    }

    if (typeof config.dashboardID !== 'undefined') {
        this.dashboardID = config.dashboardID;
    }

    if (typeof config.panels === 'object') {
        this.panels = {};
        var configPanels = Object.getOwnPropertyNames(config.panels);
        var panelID;
        for (i = 0; i < configPanels.length; i++) {
            panelID = configPanels[i];
            this.addPanel(panelID, config.panels[panelID]);
        }
    }

    if (typeof config.title == 'string') {
        this.title = config.title;
    }
};

AnalyticsDashboard.prototype.setTimeframe = function(start, end) {
    if (typeof start !== 'object' || !(start instanceof Date)) {
        throw 'Invalid start date';
    }

    if (typeof end !== 'object' || !(end instanceof Date)) {
        throw 'Invalid end date';
    }

    if (start >= end) {
        throw 'Invalid date range. Start equals or surpasses end.';
    }

    this.range.start = start;
    this.range.end   = end;
};

AnalyticsDashboard.prototype.writeDashboard = function() {
    for (var panelID in this.getPanel()) {
        this.clearPanel(panelID);
        this.writePanel(panelID);
    }
};

AnalyticsDashboard.prototype.writePanel = function(panelID) {
    var panel = this.getPanel(panelID);

    if (panel === false) {
        throw 'Invalid panel: ' + panelID;
    }

    var panelContainerID = 'analytics_panel_' + panelID;
    var panelContainer = document.getElementById(panelContainerID);

    if (typeof panelContainer !== 'object') {
        throw 'Unable to find container: #' + panelContainerID;
    }

    if (typeof panel.widgets === 'undefined' || !Array.isArray(panel.widgets)) {
        throw 'Panel does not contain a widget array.';
    }

    for (i = 0; i < panel.widgets.length; i++) {
        this.writeWidget(panel.widgets[i], panelContainer);
    }
};

AnalyticsDashboard.prototype.writeWidget = function(widgetConfig, container) {
    if (typeof widgetConfig !== 'object') {
        throw 'Invalid widget config';
    }

    if (typeof container !== 'object') {
        throw 'Invalid widget container.'
    }

    var data     = widgetConfig.data || [];
    var handler  = widgetConfig.handler || false;
    var title    = widgetConfig.title || '';
    var type     = widgetConfig.type || false;
    var widgetID = widgetConfig.widgetID || false;

    if (typeof window[handler] === 'function') {
        if (handler || widgetID || type) {
            var widgetContainer = document.createElement('div');
            widgetContainer.setAttribute('id', 'analytics_widget_' + widgetID);
            widgetContainer.setAttribute('class', 'analytics-widget analytics-widget-' + type);

            if (type !== 'metric') {
                var widgetTitle = document.createElement('h4');
                widgetTitle.setAttribute('class', 'title');
                widgetTitle.innerHTML = title;
                widgetContainer.appendChild(widgetTitle);
            }

            var widgetBody = document.createElement('div');
            var trackerChart = new window[handler](this.timeframe.start, this.timeframe.end, data, type, title);
            widgetBody.setAttribute('class', 'body');
            trackerChart.writeContents(widgetBody);
            widgetContainer.appendChild(widgetBody);

            container.appendChild(widgetContainer);
        }
    } else {
        throw 'Failed to locate handler: ' + handler;
    }
};
