<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cloud\ElasticSearch\Driver;

use Garden\Schema\Schema;
use Vanilla\Search\SearchQuery;

/**
 * Elasticsearch version of a search query.
 */
class ElasticSearchQuery extends SearchQuery {

    /** @var array $fields List of document fields to return */
    private $fields;

    /** @var array $filters List of filters */
    private $filters;

    /** @var array $filterRange List of filters with range value */
    private $filterRange;

    /** @var array $must List of must match fields (AND)*/
    private $must;

    /** @var array $should List of should match fields (OR)*/
    private $should;

    /** @var array $recordKeyMap Map of recordID to get by doc Type */
    private $recordKeyMap;

    /**
     * ElasticSearchQuery constructor.
     *
     * @param array $searchTypes
     * @param array $queryData
     */
    public function __construct(array $searchTypes, array $queryData) {
        parent::__construct($searchTypes, $queryData);
    }

    /**
     * Implement abstract method
     *
     * @param string $text
     * @param array $fieldNames
     * @return $this
     */
    public function whereText(string $text, array $fieldNames = ['name', 'body']): self {
        if (count($fieldNames) > 1) {
            foreach ($fieldNames as $field) {
                $this->should[$field] = $text;
            }
        } else {
            foreach ($fieldNames as $field) {
                $this->must[$field] = $text;
            }
        }
        return $this;
    }

    /**
     * Implement abstract method
     *
     * @param string $attribute
     * @param array $values
     * @param bool $exclude
     * @param string $filterOp
     * @return $this
     */
    public function setFilter(
        string $attribute,
        array $values,
        bool $exclude = false,
        string $filterOp = SearchQuery::FILTER_OP_OR
    ): self {
        $term = lcfirst($attribute);
        $this->filters[$term] = array_unique(array_merge($values, $this->filters[$term] ?? []));
        return $this;
    }

    /**
     * Note: this implementation only valid for date ranges!
     * $min and $max will be transformed into date time string formatted to pass to elasticserach query payload
     *
     * @param string $attribute
     * @param int $min
     * @param int $max
     * @param bool $exclude
     * @return $this|SearchQuery
     */
    public function setFilterRange(string $attribute, int $min, int $max, bool $exclude = false) {
        $this->filterRange[$attribute] = [date(DATE_ATOM, $min), date(DATE_ATOM, $max)];
        return $this;
    }

    /**
     * Get query parameter
     *
     * @param string $param
     * @param null $default
     * @return mixed|null
     */
    public function get(string $param, $default = null) {
        return $this->getQueryParameter($param, $default);
    }

    /**
     * Add fields to fetch from elastic service
     *
     * @param array $fields
     */
    public function addFields(array $fields) {
        foreach ($fields as $field) {
            $this->fields[$field] = true;
        }
    }

    /**
     * Get field list
     *
     * @return array
     */
    public function getFields(): array {
        return array_keys($this->fields);
    }

    /**
     * Prepare payload for elastisearch microservice
     *
     * @return array
     */
    public function getPayload(): array {
        $payload = [];
        if (!empty($this->fields)) {
            $payload['_source'] = $this->getFields();
        }

        if (!empty($this->must)) {
            foreach ($this->must as $field => $pattern) {
                $payload['query']['bool']['must'][] = ['match' => [$field => $pattern]];
            }
        }

        if (!empty($this->should)) {
            foreach ($this->should as $field => $pattern) {
                $should['bool']['should'][] = ['match' => [$field => $pattern]];
            }
            $payload['query']['bool']['must'][] = $should;
        }

        if (!empty($this->filters)) {
            foreach ($this->filters as $term => $values) {
                $payload['query']['bool']['must'][] = ['terms' => [$term => $values]];
            }
        }

        if (!empty($this->filterRange)) {
            foreach ($this->filterRange as $term => $range) {
                $payload['query']['bool']['must'][] = [
                    'range' => [
                        $term => [
                            "gte" => $range[0],
                            "lte" => $range[1]
                        ]
                    ]
                ];
            }
        }

        $limit = $this->get('limit') ?? 10;
        $offset = ($this->get('page', 1) - 1) * $limit;
        $payload["from"] = $offset;
        $payload["size"] = $limit;

        return $payload;
    }

    /**
     * Add required field values when missed (recordID, type)
     *
     * @param array $hit
     * @return array
     */
    public function prepareResultItem(array $hit): array {
        $record = $hit['_source'];
        if (!isset($record['recordID'])) {
            $record['recordID'] = $hit['_id'];
        };

        if (!isset($record['type'])) {
            $record['type'] = $this->getRecordType($hit['_index']);
        };
        return $record;
    }

    /**
     * Detect possible record type by elasticsearch index name
     *
     * @param string $indexName
     * @return string
     */
    private function getRecordType(string $indexName): string {
        preg_match('/{{idx\:(.*)}}/', $indexName, $match);
        return end($match);
    }
}
