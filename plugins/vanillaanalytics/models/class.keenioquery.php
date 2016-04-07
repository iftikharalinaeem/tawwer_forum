<?php
/**
 * KeenIOQuery class file.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 * @package vanillaanalytics
 */

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
     * @var string Query analysis type.  One of the ANALYSIS_* constants.
     */
    protected $analysisType;

    /**
     * @var string Target event collection.
     */
    protected $eventCollection;

    /**
     * @link https://keen.io/docs/api/#filters
     * @var array Used to refine the scope of events to be included in an analysis.
     */
    protected $filters = [];

    /**
     * @link https://keen.io/docs/api/#group-by
     * @var string Groups results categorically, by co-occurrence of a specified property.
     */
    protected $groupBy;

    /**
     * @link https://keen.io/docs/api/#interval
     * @var string Groups results into sub-timeframes spanning a specified length of time.
     */
    protected $interval;

    /**
     * @link https://keen.io/docs/api/#count-unique
     * @var string Name of the property to analyze.
     */
    protected $targetProperty;

    /**
     * @var string Specifies a period of time over which to run an analysis.
     */
    protected $timeframe;

    /**
     * @link https://keen.io/docs/api/#timezone
     * @var string Used to ensure we’re getting the data from our local definition of “today”, rather than UTC.
     */
    protected $timezone;

    /**
     * @var string A title for this query.  May be used in charting.
     */
    protected $title = '';

    /**
     * Add a filter to the query.
     *
     * @link https://keen.io/docs/api/#filters
     * @param array $filters
     * @return $this
     */
    public function addFilter(array $filters) {
        $this->filters[] = $filters;
        return $this;
    }

    /**
     * Fetch the current analysis type.
     *
     * @return string
     */
    public function getAnalysisType() {
        return $this->analysisType;
    }

    /**
     * Fetch the current filters.
     *
     * @return array
     */
    public function getFilters() {
        return $this->filters;
    }

    /**
     * Fetch the current "group by" value.
     *
     * @return string
     */
    public function getGroupBy() {
        return $this->groupBy;
    }

    /**
     * Fetch the current interval.
     *
     * @return string
     */
    public function getInterval() {
        return $this->interval;
    }

    /**
     * Fetch the current target property.
     * @return string
     */
    public function getTargetProperty() {
        return $this->targetProperty;
    }

    /**
     * Fetch the current timeframe.
     *
     * @return string
     */
    public function getTimeframe() {
        return $this->timeframe;
    }

    /**
     * Fetch the current timezone.
     *
     * @return string
     */
    public function getTimezone() {
        return $this->timezone;
    }

    /**
     * Fetch the current title.
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Specify data which should be represented when converted to JSON.
     *
     * @return array An array representing how this object should be represented when converted.
     */
    public function jsonSerialize() {
        return [
            'analisysType' => $this->analysisType,
            'eventCollection' => $this->eventCollection,
            'filters' => $this->filters,
            'groupBy' => $this->groupBy,
            'interval' => $this->interval,
            'target_property' => $this->targetProperty,
            'timeframe' => $this->timeframe,
            'timezone' => $this->timezone,
            'title' => $this->title
        ];
    }

    /**
     * Define the analysis type for this query.
     *
     * @link https://keen.io/docs/api/#analyses
     * @param string $analysisType Type of analysis for this query. Should be one of the ANALYSIS_* constants.
     * @return $this
     */
    public function setAnalysisType($analysisType) {
        $this->analysisType = $analysisType;
        return $this;
    }

    /**
     * Define the collection to query against.
     *
     * @link https://keen.io/docs/api/#event-collections
     * @param string $eventCollection Identifier for the collection to analyze.
     * @return $this
     */
    public function setEventCollection($eventCollection) {
        $this->eventCollection = $eventCollection;
        return $this;
    }

    /**
     * Define the grouping parameters for this query.
     *
     * @link https://keen.io/docs/api/#group-by
     * @param string $groupBy A property of the current event collection to group by.
     * @return $this
     */
    public function setGroupBy($groupBy) {
        $this->groupBy = $groupBy;
        return $this;
    }

    /**
     * Define the interval for this query.
     *
     * @link https://keen.io/docs/api/#interval
     * @param string $interval The interval type to use for this analysis (e.g. daily, weekly)
     * @return $this
     */
    public function setInterval($interval) {
        $this->interval = $interval;
        return $this;
    }

    /**
     * Specify the name of the property to analyze.
     *
     * @link https://keen.io/docs/api/#count-unique
     * @param string $targetProperty
     * @return $this
     */
    public function setTargetProperty($targetProperty) {
        $this->targetProperty = $targetProperty;
        return $this;
    }

    /**
     * Define the timezone for this query.
     *
     * @link https://keen.io/docs/api/#timezone
     * @param string $timezone Timezone, whether absolute or relative, for this analysis.
     * @return $this
     */
    public function setTimezone($timezone) {
        $this->timezone = $timezone;
        return $this;
    }

    /**
     * Define an absolute timeframe for this query.
     *
     * @link https://keen.io/docs/api/#absolute-timeframes
     * @param string $start A ISO-8601-formatted date string, representing the beginning of the timeframe.
     * @param string $end A ISO-8601-formatted date string representing the end of the timeframe.
     * @return $this
     */
    public function setTimeframeAbsolute($start, $end) {
        $this->timeframe =[
            'start' => $start,
            'end' => $end
        ];
        return $this;
    }

    /**
     * Define a relative timeframe for this query.
     *
     * @link https://keen.io/docs/api/#relative-timeframes
     * @param string $rel Use "this" to include units to current.  Use "previous" to omit current, incomplete unit.
     * @param int $number Any whole number greater than 0 to indicate number of units.
     * @param string $units Time interval (e.g. “minutes”, “hours”, “days”, “weeks”, “months”, or “years”).
     * @return $this
     */
    public function setTimeframeRelative($rel, $number, $units) {
        $this->timeframe = "{$rel}_{$number}_{$units}";
        return $this;
    }

    /**
     * Define the title of this query.
     *
     * @param string $title New title for this analysis.
     * @return $this
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }
}
