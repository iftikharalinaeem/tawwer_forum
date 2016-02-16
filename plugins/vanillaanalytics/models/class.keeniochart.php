<?php

/**
 * A representation of a chart configuration.
 */
class KeenIOChart implements JsonSerializable {

    const TYPE_AREA = 'area';

    const TYPE_BAR = 'bar';

    const TYPE_LINE = 'line';

    const TYPE_GAUGE = 'gauge';

    const TYPE_DONUT = 'donut';

    const TYPE_PIE = 'pie';

    const TYPE_SPLINE = 'spline';

    const TYPE_STEP = 'step';

    const TYPE_AREA_SPLINE = 'area-spline';

    const TYPE_AREA_STEP = 'area-step';

    /**
     * @var array Options to be passed along to the JavaScript charting library.
     */
    protected $options = [];

    /**
     * @var array A collection of queries to base the current chart off.
     */
    protected $queries = [];

    /**
     * @var string The title of the current chart.
     */
    protected $title;

    /**
     * @var string The type of chart (e.g. line).  One of the TYPE_* constants.
     */
    protected $type;

    /**
     * Add a query to the analyses powering the chart.
     *
     * @param KeenIOQuery $query A new query to add to the chart.
     * @return $this
     */
    public function addQuery(KeenIOQuery $query) {
        $this->queries[] = $query;
        return $this;
    }

    public function getOptions() {
        return $this->options;
    }

    public function getQueries() {
        return $this->queries;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getType() {
        return $this->type;
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize() {
        return [
            'chart' => [
                'options'   => $this->getOptions(),
                'title'     => $this->getTitle(),
                'type'      => $this->getType()
            ],
            'query' => $this->getQueries()
        ];
    }

    /**
     * Set the options to be passed to the JavaScript charting library.
     *
     * @param array $options A collection of configuration options.
     * @return $this
     */
    public function setOptions(array $options) {
        $this->options = $options;
        return $this;
    }

    /**
     * Set a new title.
     *
     * @param string $title The new title for this chart.
     * @return $this
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * Set the chart's type.
     *
     * @param string $type The new chart type.  Should be one of the TYPE_* constants.
     * @return $this
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }
}
