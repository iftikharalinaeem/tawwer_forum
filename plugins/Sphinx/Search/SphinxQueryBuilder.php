<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Sphinx\Search;

use Vanilla\Adapters\SphinxClient;
use Vanilla\Search\SearchQuery;

/**
 * Class for building applying a sphinx search query.
 */
final class SphinxQueryBuilder {

    const SORT_DATE = 'date';
    const SORT_RELEVANCE = 'relevance';

    const FILTER_OP_OR = 'or';
    const FILTER_OP_AND = 'and';

    /** @var SphinxClient */
    private $sphinxClient;

    /** @var string */
    private $query;

    /** @var string[] The search terms that were split out. */
    private $terms = [];

    /** @var bool */
    private $isFiltered = false;

    /**
     * Constructor.
     *
     * @param SphinxClient $sphinxClient
     */
    public function __construct(SphinxClient $sphinxClient) {
        $this->sphinxClient = $sphinxClient;
    }

    ///
    /// Query Building.
    ///

    /**
     * Apply a query where some text is matched.
     *
     * @param string $text The text to search.
     * @param string $prefix A prefix before the search keywords. Eg. `@name`
     *
     * @return $this
     */
    public function whereText(string $text, string $prefix = ''): SphinxQueryBuilder {
        [$query, $terms] = self::prepareSearchText($text);

        $prefix = ' ' . trim($prefix) . ' ';
        $this->query .= $prefix . $query;
        $this->terms = array_merge($this->terms, $terms);
        return $this;
    }


    /**
     * Apply a sort mode the query.
     *
     * @param string|null $sort One of the SphinxQueryBuilder::SORT_* modes.
     * @param array|null $forTerms Take search terms into account for a better sort. Will default to last extracted search terms.
     *
     * @return $this
     */
    public function setSort(?string $sort = null, ?array $forTerms = null): SphinxQueryBuilder {
        $hasMultipleTerms = count($forTerms ?? $this->terms) > 1;

        if ($sort === null) {
            $this->sphinxClient->setSelect("*, WEIGHT() + IF(dtype=5,2,1)*dateinserted/1000 AS sorter");
            $this->sphinxClient->setSortMode(SphinxClient::SORT_EXTENDED, "sorter DESC");
        } elseif ($sort === self::SORT_DATE || (!$hasMultipleTerms && $sort !== self::SORT_RELEVANCE)) {
            // If there is just one search term then we really want to just sort by date.
            $this->sphinxClient->setSelect('*, (dateinserted + 1) as sort');
            $this->sphinxClient->setSortMode(SphinxClient::SORT_ATTR_DESC, 'sort');
        } else {
            SphinxRanks::applyCustomRankFunction($this->sphinxClient);
        }

        return $this;
    }

    /**
     * Set filter values for numeric attribute
     *
     * @param string $attribute
     * @param array $values Values should be numeric
     * @param bool $exclude Whether or not the values should be excluded.
     * @param string $filterOp One of the SphinxQueryBuilder::FILTER_OP_* constants.
     *
     * @return $this
     */
    public function setFilter(string $attribute, array $values, bool $exclude = false, string $filterOp = self::FILTER_OP_OR): SphinxQueryBuilder {
        if ($filterOp === self::FILTER_OP_AND) {
            foreach ($values as $value) {
                $this->sphinxClient->setFilter($attribute, $value);
            }
        } else {
            $this->sphinxClient->setFilter($attribute, $values, $exclude);
        }
        if (count($values) > 0) {
            $this->isFiltered = true;
        }
        return $this;
    }

    /**
     * Set groupBy and groupFunc attributes
     *
     * @param string $attribute
     * @param string $func
     * @param string $groupSort
     *
     * @return $this
     */
    public function setGroupBy(string $attribute, string $func, string $groupSort = "@group desc"): SphinxQueryBuilder {
        $this->sphinxClient->setGroupBy($attribute, $func, $groupSort);
        return $this;
    }

    /**
     * Set int range filter
     *
     * @param string $attribute
     * @param int $min
     * @param int $max
     * @param bool $exclude
     *
     * @return $this
     */
    public function setFilterRange(string $attribute, int $min, int $max, bool $exclude = false): SphinxQueryBuilder {
        $this->sphinxClient->setFilterRange($attribute, $min, $max, $exclude);
        $this->isFiltered = true;
        return $this;
    }

    /**
     * Set index weights.
     *
     * @param array $weights Associative array of key value pairs: (string)indexName => (int)weight
     *
     * @return SphinxQueryBuilder
     */
    public function setIndexWeights(array $weights): SphinxQueryBuilder {
        $this->sphinxClient->setIndexWeights($weights);
        return $this;
    }

    /**
     * Set ranking mode.
     * Options: One of the SphinxClient::RANK_* constants
     *
     * @param int $ranker
     * @param string $rankExpr
     *
     * @return $this
     */
    public function setRankingMode(int $ranker, string $rankExpr = ""): SphinxQueryBuilder {
        $this->sphinxClient->setRankingMode($ranker, $rankExpr);
        return $this;
    }

    /**
     * @return string
     */
    public function getQuery(): string {
        return $this->query;
    }

    /**
     * @return string[]
     */
    public function getTerms(): array {
        return $this->terms;
    }

    /**
     * @return bool
     */
    public function isFiltered(): bool {
        return $this->isFiltered;
    }

    ///
    /// Static Utilities.
    ///

    /**
     * Constructor for use with method chaining.
     *
     * @param SphinxClient $sphinxClient
     *
     * @return SphinxQueryBuilder
     */
    public static function fromClient(SphinxClient $sphinxClient): SphinxQueryBuilder {
        return new SphinxQueryBuilder($sphinxClient);
    }

    /**
     * Prepare string values for sphinx query builder.
     * Duplicate for now. Should be moved in the future.
     *
     * @param string $str
     *
     * @return string
     */
    public static function escapeString(string $str): string {
        return SphinxClient::escapeString($str);
    }

    /**
     * Split
     *
     * @param string $searchText
     *
     * @return array [string $query, array $searchTerms]
     */
    public static function prepareSearchText(string $searchText): array {
        $searchText = preg_replace('`\s`', ' ', $searchText);
        $tokens = preg_split('`([\s"+=-])`', $searchText, -1, PREG_SPLIT_DELIM_CAPTURE);
        $tokens = array_filter($tokens);
        $inquote = false;
        $inword = false;

        $queries = [];
        $terms = [];
        $query = ['', '', ''];

        //
        // TODO: This does not actually get used anywhere.
        //
        $hasops = false; // whether or not search has operators

        foreach ($tokens as $c) {
            // Figure out where to push the token.
            switch ($c) {
                case '+':
                case '-':
                case '=':
                    if ($inquote || $inword) {
                        $query[1] .= $c;
                    } elseif (!$query[0]) {
                        $query[0] .= $c;
                    } else {
                        $query[1] .= $c;
                    }
                    $hasops = true;
                    break;
                case '"':
                    if ($inquote) {
                        $query[2] = $c;
                        $inquote = false;
                        $inword = false;
                    } else {
                        $query[0] .= $c;
                        $inquote = true;
                    }
                    $hasops = true;
                    break;
                case ' ':
                    if ($inquote) {
                        $query[1] .= $c;
                    } else {
                        $inword = false;
                    }
                    break;
                default:
                    $query[1] .= $c;
                    $inword = true;
                    break;
            }

            // Now split the query into terms and move on.
            if ($query[2] || ($query[1] && !$inquote && !$inword)) {
                $queries[] = $query[0] . self::escapeString($query[1]) . $query[2];
                $terms[] = $query[1];
                $query = ['', '', ''];
            }
        }
        // Account for someone missing their last quote.
        if ($inquote && $query[1]) {
            $queries[] = $query[0] . self::escapeString($query[1]) . '"';
            $terms[] = $query[1];
        } elseif ($inword && $query[1]) {
            $queries[] = $query[0] . self::escapeString($query[1]);
            $terms[] = $query[1];
        }

        // Now we need to convert the queries into sphinx syntax.
        $firstmod = false; // whether first term had a modifier.
        $finalqueries = [];
        $quorums = [];

        foreach ($queries as $i => $query) {
            $c = substr($query, 0, 1);
            if ($c == '+') {
                $finalqueries[] = substr($query, 1);
                $firstmod = $i == 0;
            } elseif ($c == '-' || $c == '=') {
                $finalqueries[] = $c . substr($query, 1);
                $firstmod = $i == 0;
            } elseif ($c == '"') {
                if (!$firstmod && count($finalqueries) > 0) {
                    $query = '| ' . $query;
                }
                $finalqueries[] = $query;
            } else {
                // Collect this term into a list for the quorum operator.
                $quorums[] = $query;
            }
        }

        // Calculate the quorum.
        if (count($quorums) <= 2) {
            $quorum = implode(' ', $quorums);
        } else {
            $quorum = '"' . implode(' ', $quorums) . '"/' . round(count($quorums) * .6); // must have at least 60% of search terms
        }

        $finalquery = implode(' ', $finalqueries) . ' ' . $quorum;

        return [trim($finalquery), array_unique($terms)];
    }
}
