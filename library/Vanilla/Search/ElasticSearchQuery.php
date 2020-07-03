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

    public function getIndexes(): array {
        return [
            "comment",
            "discussion"
        ];
    }

    public function getPayload(): array {
        return [
                "query"=> [
                    "query_string" => [
                        "query" => "discussion"
                    ]
                ],
                "from" => 2,
                "size" => 2
        ];
    }
}
