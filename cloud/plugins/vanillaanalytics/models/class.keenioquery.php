<?php
/**
 * KeenIOQuery class file.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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
     * @var KeenIOClient Instance of keen.io API client.
     */
    protected $client;

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
     * @link https://keen.io/docs/api/#limiting-the-number-of-groups-returned
     * @var int Limit the total number of events returned in a result.
     */
    protected $limit;

    /**
     * @link https://keen.io/docs/api/#order-by
     * @var array One or more properties to order the result by.
     */
    protected $orderBy = [];

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
     * KeenIOQuery constructor.
     */
    public function __construct() {
        $this->client = new KeenIOClient(
            'https://api.keen.io/{version}/',
            [
                'orgID' => c('VanillaAnalytics.KeenIO.OrgID'),
                'orgKey' => c('VanillaAnalytics.KeenIO.OrgKey'),
                'projectID' => c('VanillaAnalytics.KeenIO.ProjectID'),
                'readKey' => c('VanillaAnalytics.KeenIO.ReadKey'),
                'writeKey' => c('VanillaAnalytics.KeenIO.WriteKey')
            ]
        );
    }

    /**
     * Add a filter to the query.
     *
     * You can also define property_callback instead of property_value to call one of the predefined callbacks.
     * See KeenIOFilterCallback.
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
     * Add an order_by config to the query.
     *
     * @param string $property Name of the target property.
     * @param string $direction Order direction (ASC or DESC).
     * @return array
     */
    public function addOrderBy($property, $direction = 'ASC') {
        $validDirections = ['ASC', 'DESC'];
        if (!in_array($direction, $validDirections)) {
            throw new InvalidArgumentException("Invalid order_by direction: {$direction}");
        }

        $this->orderBy[] = [
            'property_name' => $property,
            'direction' => $direction
        ];

        return $this->orderBy;
    }

    /**
     * Execute this query and return the result.
     *
     * @throws Gdn_UserException if we haven't configured a type.
     * @throws Gdn_UserException if we haven't configured a collection.
     * @return object|array|bool
     */
    public function exec() {
        if (empty($this->analysisType)) {
            throw new Gdn_UserException('Analysis type not configured.');
        }
        if (empty($this->eventCollection)) {
            throw new Gdn_UserException('Event collection not configured.');
        }

        $data = [
            'maxAge' => 300
        ];
        $projectID = $this->client->getProjectID();
        $analysisType = $this->analysisType;

        if ($this->eventCollection) {
            $data['event_collection'] = $this->eventCollection;
        }
        if ($this->filters) {
            $data['filters'] = $this->filters;
        }
        if ($this->groupBy) {
            $data['group_by'] = $this->groupBy;
        }
        if ($this->interval) {
            $data['interval'] = $this->interval;
        }
        if ($this->limit) {
            $data['limit'] = $this->limit;
        }
        if ($this->orderBy) {
            $data['order_by'] = $this->orderBy;
        }
        if ($this->targetProperty) {
            $data['target_property'] = $this->targetProperty;
        }
        if ($this->timeframe) {
            $data['timeframe'] = $this->timeframe;
        }
        if ($this->timezone) {
            $data['timezone'] = $this->timezone;
        }

        try {
            $response = $this->client->command(
                "projects/{$projectID}/queries/{$analysisType}",
                $data,
                KeenIOClient::COMMAND_READ,
                KeenIOClient::REQUEST_POST
            );
        } catch (Exception $e) {
            $response = false;
        }

        return $response ?: false;
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
     * Get the current limit configured for the query.
     *
     * @return int|null
     */
    public function getLimit() {
        return $this->limit;
    }

    /**
     * Get the current order_by configuration.
     *
     * @return array
     */
    public function getOrderBy() {
        return $this->orderBy;
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
            'order_by' => $this->orderBy,
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
     * Reset any configured filters.
     */
    public function resetFilters() {
        $this->filters = [];
    }

    /**
     * Reset the order-by config.
     */
    public function resetOrderBy() {
        $this->orderBy = [];
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
     * @param string|array $groupBy A (or an array of) property of the current event collection to group by
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
     * Configure a limit on the total number of results returned by this query.
     *
     * @param int $limit
     * @return $this
     */
    public function setLimit($limit) {
        $limit = filter_var($limit, FILTER_VALIDATE_INT);
        if ($limit === false) {
            throw new InvalidArgumentException("Invalid limit value: {$limit}");
        }
        $this->limit = intval($limit);
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
        $this->timeframe = [
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