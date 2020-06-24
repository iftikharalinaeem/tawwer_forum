<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\ServerException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Data;

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
     * @throws ClientException if the query config isn't valid.
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
            $result = $this->query->exec();
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
        $this->permission('Analytics.Data.View');

        $boards = $this->widgetsByType('leaderboard');

        $in = $this->schema([
            'board:s' => [
                'description' => 'The user leaderboard to query.',
                'enum' => array_keys($boards)
            ],
            'limit:i' => [
                'description' => 'The number of rows to return.',
                'default' => AnalyticsLeaderboard::DEFAULT_SIZE,
                'minimum' => 1,
                'maximum' => AnalyticsLeaderboard::MAX_SIZE
            ],
            'start:dt' => 'Start of the time frame.',
            'end:dt' => 'End of the time frame.',
        ], 'in')->setDescription('Retrieve data for a leaderboard.');
        $out = $this->schema([
            ':a' => $this->schema([
                'id:i' => 'ID of the record.',
                'position:i' => 'Current leaderboard position.',
                'positionChange:s' => [
                    'description' => 'Progression status of the record.',
                    'enum' => ['Fall', 'New', 'Rise', 'Same']
                ],
                'previous:i|n' => 'Previous position of the record',
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
        $leaderboard->setSize($query['limit']);
        $leaderboardQuery = $config['query'];
        $leaderboard->setQuery($leaderboardQuery);
        $leaderboard->setPreviousQuery(clone $leaderboardQuery);
        $rows = $leaderboard->lookupData(
            $query['start']->getTimestamp(),
            $query['end']->getTimestamp()
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
     * Get the result of an analytics query.
     *
     * @param array $body The body of the request.
     * @return Data
     */
    public function post_query(array $body) {
        $this->permission('Analytics.Data.View');

        $in = $this->schema([
            'type:s' => [
                'description' => 'Type of analysis to perform.',
                'enum' => ['count', 'count_unique', 'maximum', 'median', 'sum']
            ],
            'collection:s' => [
                'description' => 'Event collection.',
                'enum' => $this->collections
            ],
            'start:dt' => 'Start of the time frame.',
            'end:dt' => 'End of the time frame.',
            'property:s?' => 'An event property to perform the analysis on. Required for count_unique, maximum, median and sum query types.',
            'filters:a?' => [
                'prop:s' => 'The property name.',
                'op:s' => [
                    'default' => 'eq',
                    'description' => 'The comparison operation for the filter.',
                    'enum' => ['eq', 'ne', 'gt', 'gte', 'lt', 'lte', 'in']
                ],
                'val' => 'The target value for comparison.'
            ],
            'interval:s?' => [
                'description' => 'Result interval.',
                'enum' => ['hourly', 'daily', 'weekly', 'monthly']
            ],
            'group:s?' => 'An event property to group results.'
        ], 'in')
            ->addValidator('', $this->queryValidator())
            ->setDescription('Get the result of an analytics query.');
        $out = $this->queryResponseSchema($body);

        $query = $in->validate($body);
        $events = $this->execQuery($query);

        $result = $out->validate($events);
        return new Data($result, 200);
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
     * Return a garden-schema-compatible validator for a query request.
     *
     * @return callable
     */
    private function queryValidator() {
        $result = function($value, ValidationField $field) {
            $type = array_key_exists('type', $value) ? $value['type'] : null;
            $property = array_key_exists('property', $value) ? $value['property'] : null;

            switch ($type) {
                case 'count_unique':
                case 'maximum':
                case 'median':
                case 'sum':
                    if (empty($property)) {
                        $field->getValidation()->addError(
                            'property',
                            'missingField',
                            [
                                'messageCode' => "{field} required for {queryType} queries",
                                'queryType' => $type
                            ]
                        );
                    }
                    break;
            }
        };

        return $result;
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
