/**
 * Create a new analytics widget object.
 * @class
 * @param {object} config
 */
function AnalyticsWidget(config) {

    /**
     *
     * @type {boolean}
     */
    var bookmarked = false;

    /**
     *
     * @type {Array}
     */
    var data = [];

    /**
     * DOM elements for this widget.
     * @access private
     * @type {null|object}
     */
    var elements = null;

    /**
     *
     * @type {null|object}
     */
    var handler = null;

    /**
     *
     * @type {Array}
     */
    var supports = [];

    /**
     *
     * @type {string}
     */
    var title = '';

    /**
     *
     * @type {string}
     */
    var type = 'chart';

    /**
     *
     * @type {null|number}
     */
    var widgetID = null;

    /**
     *
     * @type {null|object}
     */
    var timeframe = {
        end   : new Date(),
        start : new Date()
    };

    /**
     *
     * @type {boolean}
     */
    var disabled = false;

    /**
     *
     *
     */
    this.isDisabled = function() {
        return disabled
    }

        /**
     *
     *
     */
    this.setDisabled = function(disable) {
        disabled = disable;
        return this;
    }

    /**
     *
     * @param {Array|string} eventProperty
     */
    this.addSupport = function(eventProperty) {
        if (eventProperty !== 'undefined') {
            if (Array.isArray(eventProperty)) {
                for (var i = 0; i < eventProperty.length; i++) {
                    this.addSupport(eventProperty[i]);
                }
            } else if (typeof eventProperty === 'string') {
                supports.push(eventProperty);
            }
        }
    };

    /**
     * Create the widget's elements in the DOM.
     */
    this.createElements = function(removeExisting) {
        removeExisting = typeof removeExisting === 'undefined' ? true : !!removeExisting;

        if (removeExisting) {
            var existingElements = this.getElements();
            if (existingElements !== null) {
                existingElements.container.parentNode.removeChild(oldElements.container);
            }
        }

        // Setup the document elements we'll be using.
        var newElements = {
            body      : document.createElement('div'),
            header    : document.createElement('div'),
            bookmark  : document.createElement('a'),
            options   : document.createElement('div'),
            container : document.createElement('li'),
            title     : null
        };

        newElements.container.setAttribute('id', 'analytics_widget_' + widgetID);
        newElements.container.setAttribute('class', 'analytics-widget analytics-widget-' + type);
        newElements.header.setAttribute('class', 'analytics-widget-header');
        newElements.options.setAttribute('class', 'analytics-widget-options');

        if (this.isBookmarked()) {
            newElements.bookmark.setAttribute('class', 'Hijack bookmark bookmarked');
        } else {
            newElements.bookmark.setAttribute('class', 'Hijack bookmark');
        }
        newElements.bookmark.setAttribute('href', gdn.url('/analytics/bookmarkwidget/' + this.getWidgetID()));
        newElements.bookmark.innerHTML = dashboardSymbol('pin');

        // Metrics are a special case where a title is redundant.  Otherwise, we need a title element.
        if (this.getType() !== 'metric') {
            newElements.title = document.createElement('h3');
            newElements.title.setAttribute('class', 'title');
            newElements.title.innerHTML = this.getTitle();
            newElements.header.appendChild(newElements.title);
        }

        newElements.options.appendChild(newElements.bookmark);
        newElements.header.appendChild(newElements.options);
        newElements.container.appendChild(newElements.header);

        newElements.body.setAttribute('class', 'body');

        newElements.container.appendChild(newElements.body);

        elements = newElements;
    };

    this.getData = function() {
        return data;
    };

    /**
     * Retrieve this widget's DOM element.
     * @param {string} [child]
     * @returns {null|object}
     */
    this.getElements = function(child) {
        if (elements === null) {
            this.createElements(false);
        }

        if (typeof child === 'string') {
            if (typeof elements[child] !== 'undefined') {
                return elements[child];
            } else {
                throw 'Invalid widget child element specified';
            }
        } else {
            return elements;
        }
    };

    this.getHandler = function() {
        return handler;
    };

    this.getInterval = function () {
        return data['query']['interval'];
    };

    this.getTitle = function() {
        return title;
    };

    this.getTimeframe = function() {
        return timeframe;
    };

    this.getType = function() {
        return type;
    };

    this.getWidgetID = function() {
        return widgetID;
    };

    this.isBookmarked = function() {
        return bookmarked;
    };

    this.setBookmarked = function(newBookmarked) {
        if (typeof newBookmarked === 'boolean') {
            bookmarked = newBookmarked;
        }
    };

    this.setData = function(newData) {
        if (typeof newData === 'object') {
            data = newData;
        }
    };

    this.setFilter = function(name, value, support) {
        if (support && !this.supports(support)) {
            this.setDisabled(!!value);
            return true;
        } else {
            this.setDisabled(false);
        }

        var updateQueryFilter = function(widget, query, name, value, support) {
            var filters = query['filters'] = query['filters'] || [];
            var filter = undefined;

            for (var i in filters) {
                if (filters[i]['property_name'] === name) {
                    if (!value) {
                        filters.splice(i, 1);
                    }
                    filter = filters[i];
                    break;
                }
            }
            if (!value) {
                widget.drillGroupBy(support, true);
                return true;
            }
            if (filter === undefined) {
                filters.push({
                    'operator': 'eq',
                    'property_name': name,
                    'property_value': value
                });
            } else {
                filter.property_value = value;
            }
            widget.drillGroupBy(support);
        }

        var widget = this;
        if (Array.isArray(data['query'])) {
            $.each(data['query'], function() {
                updateQueryFilter(widget, this, name, value, support);
            })
        } else {
            updateQueryFilter(widget, data['query'], name, value, support);
        }

    };

    this.drillGroupBy = function(name, undo) {
        if (data['query'] === undefined || !data['query']['groupBy']) {
            return;
        }

        var find, replace;

        if (undo) {
            find = name.replace('01', '02');
            replace = name
        } else {
            find = name;
            replace = name.replace('01', '02');
        }

        var groupBy = this.getHandler().getQueryParam('groupBy');
        var fixedGroupBy;
        if (Array.isArray(groupBy)) {
            fixedGroupBy = [];
            $.each(groupBy, function(index, element) {
                fixedGroupBy.push(element.replace(find, replace));
            });
        } else {
            fixedGroupBy = groupBy.replace(find, replace)
        }
        this.getHandler().updateQueryParams({
            groupBy: fixedGroupBy
        });
    };

    this.setHandler = function(newHandler) {
        if (typeof newHandler === 'string' && typeof window[newHandler] === 'function') {
            var widgetData = this.getData();
            var config = {
                chartConfig: widgetData.chart,
                query      : widgetData.query,
                range      : this.getTimeframe(),
                title      : this.getTitle(),
                type       : this.getType()
            };

            if (typeof widgetData.queryProcessor !== 'undefined') {
                config['queryProcessor'] = widgetData.queryProcessor;
            }
            if (typeof widgetData.callback !== 'undefined') {
                config['callback'] = widgetData.callback;
            }

            handler = new window[newHandler](config);
        } else if (typeof newHandler === 'object') {
            handler = newHandler;
        } else {
            throw 'Invalid value for newHandler';
        }
    };

    this.setInterval = function(newInterval) {
        this.getHandler().setInterval(newInterval);
        return true;
    };

    this.setRange = function(newRange) {
        this.getHandler().setRange(newRange);
        return true;
    };

    this.setTitle = function(newTitle) {
        if (typeof newTitle === 'string') {
            title = newTitle;
        }
    };

    this.setType = function(newType) {
        if (typeof newType === 'string') {
            type = newType;
        }
    };

    this.setWidgetID = function(newWidgetID) {
        if (typeof newWidgetID === 'string') {
            widgetID = newWidgetID;
        }
    };

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
     *
     * @param {string} eventProperty
     * @return {boolean}
     */
    this.supports = function(eventProperty) {
        return (supports.indexOf(eventProperty) !== -1);
    };

    this.loadConfig(config);
}

/**
 * @param {string} idAttribute
 * @return {bool|string}
 */
AnalyticsWidget.getIDFromAttribute = function(idAttribute) {
    var idParts = /analytics_widget_([a-z0-9\-]+)/i.exec(idAttribute);

    if (Array.isArray(idParts)) {
        return idParts[1];
    } else {
        return false;
    }
};

AnalyticsWidget.prototype.loadConfig = function(config) {
    if (typeof config !== 'object') {
        throw 'Invalid dashboard config';
    }

    if (typeof config.bookmarked !== 'undefined') {
        this.setBookmarked(config.bookmarked);
    }

    if (typeof config.data !== 'undefined') {
        this.setData(config.data);
    }

    if (typeof config.supports !== 'undefined') {
        this.addSupport(config.supports);
    }

    if (typeof config.title !== 'undefined') {
        this.setTitle(config.title);
    }

    if (typeof config.type !== 'undefined') {
        this.setType(config.type);
    }

    if (typeof config.widgetID !== 'undefined') {
        this.setWidgetID(config.widgetID);
    }

    if (typeof config.timeframe !== 'undefined') {
        if (typeof config.timeframe.start === 'object' && typeof config.timeframe.end === 'object') {
            this.setTimeframe(config.timeframe.start, config.timeframe.end);
        }
    }

    if (typeof config.handler !== 'undefined') {
        this.setHandler(config.handler);
    }
};

AnalyticsWidget.popin = function(element, url, parameters) {
    var $elem = $(element);
    var data = $.extend({ DeliveryType: 'VIEW' }, parameters);
    $.ajax({
        url: url,
        data: data,
        success: function(response) {
            $elem.html($.parseHTML(response + "")).trigger('contentLoad');
            $elem.parent().show();
        },
        error: function() {
            $elem.parent().hide()
        },
        complete: function() {
            $elem.parent().removeClass('data-loading');
        }
    });
};

/**
 * Output a widget's contents to the specified container.
 * @throws Throw an error if unable to find a compatible handler for this widget.
 */
AnalyticsWidget.prototype.render = function() {
    var container = this.getElements('body');

    if (this.isDisabled()) {
        $(container).parent().hide();
        return;
    }

    $(container).parent().show();

    // We need a class available to handle the widget.  Verify we have one available on the page.
    var handler = this.getHandler();

    if (handler !== null) {
        handler.writeContents(container);
    } else {
        throw 'No data handler configured for widget';
    }
};