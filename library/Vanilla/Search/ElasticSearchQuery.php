<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Schema\Schema;

/**
 * Elasticsearch version of a search query.
 */
class ElasticSearchQuery extends SearchQuery {
    /** @var array $indexes List of elasticsearch indexes */
    private $indexes;

    /** @var array $fields List of document fields to return */
    private $fields;

    /** @var string $queryString Query string to search */
    private $queryString;

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

    public function addIndex(string $index) {
        $this->indexes[$index] = true;
    }

    public function getIndexes(): array {
        return array_keys($this->indexes);
    }

    public function addFields(array $fields) {
        foreach ($fields as $field) {
            $this->fields[$field] = true;
        }
    }

    public function getFields(): array {
        return array_keys($this->fields);
    }

    public function setQueryString(string $keywords) {
        $this->queryString = $keywords;
    }

    public function getPayload(): array {
        $payload = [];
        if (!empty($this->fields)) {
            $payload['_source'] = $this->getFields();
        }

        if ($this->queryString !== null) {
            $payload["query_string"] = [
                "query" => $this->queryString
            ];
        }

        $payload["from"] = 2;
        $payload["size"] = 2;

        return $payload;
    }
}
