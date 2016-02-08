<?php

/**
 * A data container for graphical representation in the browser (e.g. charts, metrics).
 */
class AnalyticsWidget {

    /**
     * @var string Unique identifier, per dashboard, for this widget.
     */
    public $widgetID;

    /**
     * @var array A collection of data to drive the widget.
     */
    protected $data = [];

    /**
     * @var string Name of the JavaScript object to handle the data.
     */
    protected $handler;

    /**
     * @var string Type of this widget: chart or metric.
     */
    protected $type;

    /**
     * AnalyticsWidget constructor.
     * @param bool $widgetID
     * @param array $data
     * @param bool $handler
     */
    public function __construct($widgetID = false, $data = [], $handler = false) {
        if ($widgetID) {
            $this->setID($widgetID);
        }
    }

    /**
     * Retrieve the data for this widget.
     *
     * @return array
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Grab the name of the handler for this widget.
     *
     * @return string
     */
    public function getHandler() {
        return $this->handler;
    }

    /**
     * Fetch this widget's type.
     *
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Set this widget's data.
     *
     * @param array $data A collection of data to be passed to the JavaScript handler.
     * @return $this
     */
    public function setData(array $data) {
        $this->data = $data;
        return $this;
    }

    /**
     * Assign the JavaScript handler that will be used for this widget.
     *
     * @param string $handler Name of the JavaScript object to render this widget.
     * @return $this
     */
    public function setHandler($handler) {
        $this->handler = $handler;
        return $this;
    }

    /**
     * Set the unique identifier, per dashboard, for this widget.
     *
     * @param string $widgetID This widget's unique identifier.
     * @return $this
     */
    public function setID($widgetID) {
        $this->widgetID = $widgetID;
        return $this;
    }

    /**
     * Set the type of this widget.
     *
     * @param string $type A widget type: chart or metric.
     * @return $this
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }
}
