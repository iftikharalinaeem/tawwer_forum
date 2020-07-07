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
    public function whereText(string $text, array $fieldNames = []): self {
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

    public function addRecordKeyMap(array $types) {
        foreach ($types as $type => $recordKey) {
            $this->recordKeyMap[$type] = $recordKey;
        }
    }

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

    private function getRecordType(string $indexName): string {
        preg_match('/{{idx\:(.*)}}/', $indexName, $match);
        return end($match);
    }
}
