<?php

/**
 * Build an analysis query object for keen.io.
 */
class KeenIOQuery implements JsonSerializable {

    /**
     * @link https://keen.io/docs/api/#count
     */
    const ANALYSIS_COUNT = 'count';

    /**
     * @link https://keen.io/docs/api/#count-unique
     */
    const ANALYSIS_COUNT_UNIQUE = 'count_unique';

    /**
     * @link https://keen.io/docs/api/#minimum
     */
    const ANALYSIS_MINIMUM = 'minimum';

    /**
     * @link https://keen.io/docs/api/#maximum
     */
    const ANALYSIS_MAXIMUM = 'maximum';

    /**
     * @link https://keen.io/docs/api/#sum
     */
    const ANALYSIS_SUM = 'sum';

    /**
     * @link https://keen.io/docs/api/#average
     */
    const ANALYSIS_AVERAGE = 'average';

    /**
     * @link https://keen.io/docs/api/#median
     */
    const ANALYSIS_MEDIAN = 'median';

    /**
     * @link https://keen.io/docs/api/#percentile
     */
    const ANALYSIS_PERCENTILE = 'percentile';

    /**
     * @link https://keen.io/docs/api/#select-unique
     */
    const ANALYSIS_SELECT_UNIQUE = 'select_unique';

    /**
     * @var string
     */
    protected $analysisType;

    /**
     * @var string
     */
    protected $eventCollection;

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * @var string
     */
    protected $groupBy;

    /**
     * @var string
     */
    protected $interval;

    /**
     * @var string
     */
    protected $timeframe;

    /**
     * @var string
     */
    protected $timezone;

    /**
     * @var string
     */
    protected $title = '';

    /**
     * @link https://keen.io/docs/api/#filters
     * @param array $filters
     * @return $this
     */
    public function addFilter(array $filters) {
        $this->filters[] = $filters;
        return $this;
    }

    /**
     * @return string
     */
    public function getAnalysisType() {
        return $this->analysisType;
    }

    /**
     * @return array
     */
    public function getFilters() {
        return $this->filters;
    }

    /**
     * @return string
     */
    public function getGroupBy() {
        return $this->groupBy;
    }

    /**
     * @return string
     */
    public function getInterval() {
        return $this->interval;
    }

    /**
     * @return string
     */
    public function getTimeframe() {
        return $this->timeframe;
    }

    /**
     * @return string
     */
    public function getTimezone() {
        return $this->timezone;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize() {
        return [
            'analisysType'    => $this->analysisType,
            'eventCollection' => $this->eventCollection,
            'filters'         => $this->filters,
            'groupBy'         => $this->groupBy,
            'interval'        => $this->interval,
            'timeframe'       => $this->timeframe,
            'timezone'        => $this->timezone,
            'title'           => $this->title
        ];
    }

    /**
     * @link https://keen.io/docs/api/#analyses
     * @param string $analysisType
     * @return $this
     */
    public function setAnalysisType($analysisType) {
        $this->analysisType = $analysisType;
        return $this;
    }

    /**
     * @link https://keen.io/docs/api/#event-collections
     * @param string $eventCollection
     * @return $this
     */
    public function setEventCollection($eventCollection) {
        $this->eventCollection = $eventCollection;
        return $this;
    }

    /**
     * @link https://keen.io/docs/api/#group-by
     * @param string $groupBy
     * @return $this
     */
    public function setGroupBy($groupBy) {
        $this->groupBy = $groupBy;
        return $this;
    }

    /**
     * @link https://keen.io/docs/api/#interval
     * @param string $interval
     * @return $this
     */
    public function setInterval($interval) {
        $this->interval = $interval;
        return $this;
    }

    /**
     * @link https://keen.io/docs/api/#timeframe
     * @param string $timeframe
     * @return $this
     */
    public function setTimeframe($timeframe) {
        $this->timeframe = $timeframe;
        return $this;
    }

    /**
     * @link https://keen.io/docs/api/#timezone
     * @param string $timezone
     * @return $this
     */
    public function setTimezone($timezone) {
        $this->timezone = $timezone;
        return $this;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }
}
