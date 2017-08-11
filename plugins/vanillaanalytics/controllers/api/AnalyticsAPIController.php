<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\ServerException;

/**
 * API Controller for `/analytics` endpoints.
 */
class AnalyticsApiController extends AbstractApiController {

    /** @var array */
    private $collections = ['page', 'point', 'post', 'post_modify', 'qna', 'reaction', 'registration', 'session'];

    /** @var KeenIOQuery */
    private $query;

    /**
     * AnalyticsApiController constructor.
     *
     * @param KeenIOQuery $query
     */
    public function __construct(KeenIOQuery $query) {
        $this->query = $query;
    }

    /**
     * Perform an analytics query.
     *
     * @param array $query Details of the analytics query.
     * @throws ServerException if there was an error performing the analytics query request.
     * @return array
     */
    protected function execQuery(array $query) {
        /** @var DateTimeImmutable $start */
        $start = $query['start'];
        /** @var DateTimeImmutable $end */
        $end = $query['end'];

        // Required fields.
        $this->query->setAnalysisType($query['type']);
        $this->query->setEventCollection($query['collection']);
        $this->query->setTimeframeAbsolute(
            $start->format('c'),
            $end->format('c')
        );

        // Optional fields.
        $property = array_key_exists('property', $query) ? $query['property'] : null;
        $this->query->setTargetProperty($property);
        $interval = array_key_exists('interval', $query) ? $query['interval'] : null;
        $this->query->setInterval($interval);
        $group = array_key_exists('group', $query) ? $query['group'] : null;
        $this->query->setGroupBy($group);

        // Optional filters.
        $filters = array_key_exists('filters', $query) ? $this->translateFilters($query['filters']) : null;
        if ($filters !== null) {
            foreach ($filters as $filter) {
                $this->query->addFilter($filter);
            }
        } else {
            // If there are no filters, make sure nothing is lingering.
            $this->query->resetFilters();
        }

        try {
            $result = $this->query->exec(true, true);
        } catch (Exception $e) {
            // If there was a problem, let the user know what's up.
            throw new ServerException(
                $e->getMessage(),
                $e->getCode()
            );
        }

        return $result;
    }

    /**
     * Get data for a leaderboard.
     *
     * @param array $query
     * @return array
     */
    public function get_leaderboard(array $query) {
        $this->permission('Garden.Settings.Manage');

        $boards = $this->widgetsByType('leaderboard');

        $in = $this->schema([
            'board:s' => [
                'description' => 'The user leaderboard to query.',
                'enum' => array_keys($boards)
            ],
            'limit:i' => [
                'description' => 'The number of rows to return.',
                'default' => 1,
                'minimum' => 1,
                'maximum' => AnalyticsLeaderboard::MAX_SIZE
            ],
            'start:dt' => 'Start of the time frame.',
            'end:dt' => 'End of the time frame.',
        ], 'in');
        $out = $this->schema([
            ':a' => $this->schema([
                'id:i' => 'ID of the record.',
                'position:i' => 'Current leaderboard position.',
                'positionChange:s' => [
                    'description' => 'Progression status of the record.',
                    'enum' => ['Fall', 'New', 'Rise', 'Same']
                ],
                'previous:i|s' => 'Previous position of the record',
                'url:s' => 'Full URL to the record.',
                'title:s|n' => 'Title for the row.',
                'count:i' => 'Associated total for this row.'
            ], 'Leaderboard')
        ], 'out');

        $query = $in->validate($query);

        // Poll the analytics tracker for leaderboard data.
        /** @var AnalyticsWidget $config */
        $config = $boards[$query['board']]->getData();
        $leaderboard = new AnalyticsLeaderboard();
        $leaderboard->setSize($query['size']);
        $leaderboardQuery = $config['query'];
        $leaderboard->setQuery($leaderboardQuery);
        $leaderboard->setPreviousQuery(clone $leaderboardQuery);
        $rows = $leaderboard->lookupData(
            strtotime($query['start']),
            strtotime($query['end'])
        );

        // Prepare the data for output.
        $result = [];
        foreach ($rows as &$row) {
            $this->prepareLeaderboardRow($row['LeaderRecord']);
            $result[] = $row['LeaderRecord'];
        }
        $result = $out->validate($result);

        return $result;
    }

    /**
     * Query tracked events from a collection.
     *
     * @param array $query The request query.
     * @return array
     */
    public function get_query(array $query) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->schema([
            'type:s' => [
                'description' => 'Type of analysis to perform.',
                'enum' => ['count', 'sum', 'maximum', 'count_unique', 'median']
            ],
            'collection:s' => [
                'description' => 'Event collection.',
                'enum' => $this->collections
            ],
            'start:dt' => 'Start of the time frame.',
            'end:dt' => 'End of the time frame.',
            'property:s?' => 'An event property to perform the analysis on.',
            'filters:s?' => 'Event property filters.',
            'interval:s?' => [
                'description' => 'Result interval.',
                'enum' => ['hourly', 'daily', 'weekly', 'monthly']
            ],
            'group:s?' => 'An event property to group results.'
        ], 'in')
            ->setDescription('Query tracked events.')
            ->addValidator('filters', [$this, 'validateFilters']);

        $out = $this->queryResponseSchema($query);

        $query = $in->validate($query);
        $events = $this->execQuery($query);

        $result = $out->validate($events);
        return $result;
    }

    /**
     * Prepare a record to be displayed on the leaderboard.
     *
     * @param array $row A record to be displayed on a leaderboard.
     */
    public function prepareLeaderboardRow(array &$row) {
        if (array_key_exists('Url', $row)) {
            $row['Url'] = url($row['Url'], true);
        }
    }

    /**
     * Based on an analytics query, determine the proper output schema.
     *
     * @param array $query An analytics query.
     * @return Schema
     */
    protected function queryResponseSchema(array $query, $type = 'out') {
        $hasInterval = array_key_exists('interval', $query);
        $group = array_key_exists('group', $query) ? $query['group'] : null;

        // Depending on the type of parameters provided to a query, the result can be structured differently.
        if ($hasInterval) {
            // All interval rows include a timeframe object.
            $row = [
                'timeframe:o' => [
                    'start:dt',
                    'end:dt'
                ]
            ];
            if ($group) {
                // If grouping on a property, the value is further broken down to an array of objects.
                $row['value:a'] = [
                    'result:i',
                    $group
                ];
            } else {
                // Without a group, it's a simple value.
                $row[] = 'value:i';
            }
            $fields = ['result:a' => $row];
        } elseif ($group) {
            // Gropuing without an interval makes for an array of objects.
            $fields = [
                'result:a' => [
                    'result:i',
                    $group
                ]
            ];
        } else {
            // The allowed operations only return integers.
            $fields = ['result:i'];
        }

        $schema = $this->schema($fields, $type);
        return $schema;
    }

    /**
     * Ensure filters array is ready for consumption by the analytics service API.
     *
     * @param array $filters An array of filters to prepare for use.
     * @return array
     */
    protected function translateFilters(array $filters = []) {
        foreach ($filters as &$filter) {
            // Translate array keys to ensure valid parameters for the service's API.
            foreach ($filter as $key => $val) {
                $newKey = null;
                switch ($key) {
                    case 'prop':
                        $newKey = 'property_name';
                        break;
                    case 'op':
                        $newKey = 'operator';
                        break;
                    case 'val':
                        $newKey = 'property_value';
                        break;
                }

                if ($newKey) {
                    $filter[$newKey] = $filter[$key];
                    unset($filter[$key]);
                }
            }
        }
        return $filters;
    }

    /**
     * Validate the filter parameter of an incoming request.
     *
     * @param string|array $value The value to test.
     * @param ValidationField $field The validation field object, passed down by the schema class.
     * @return array A validated value
     */
    public function validateFilters(&$value, ValidationField $field) {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        // Short parameter names save us some bits in the request. Keen's parameters are longer (e.g. property_name).
        $in = $this->schema([
            ':a' => [
                'prop:s',
                'op:s' => [
                    'enum' => [ 'eq', 'nq', 'gt', 'gte', 'lt', 'lte', 'in']
                ],
                'val'
            ]
        ]);

        // If validation fails, an exception is thrown that will bubble up.
        $result = $in->validate($value);
        return $result;
    }

    /**
     * Get widgets of a certain type from the configured tracker.
     *
     * @param string $type The type of widgets to retrieve.
     * @param bool $idOnly Only return widget IDs?
     * @return array
     */
    protected function widgetsByType($type, $idOnly = false) {
        $result = [];

        $widgets = AnalyticsTracker::getInstance()->getDefaultWidgets();
        foreach ($widgets as $id => $config) {
            /** @var AnalyticsWidget $config */
            if ($config->getType() === $type) {
                $result[$id] = $config;
            }
        }

        if (count($result) > 0 && $idOnly) {
            $result = array_keys($result);
        }

        return $result;
    }
}
