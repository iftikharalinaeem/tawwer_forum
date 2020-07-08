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
trait SphinxQueryTrait {

    /** @var string */
    private $query = '';

    /** @var string[] The search terms that were split out. */
    private $terms = [];

    /** @var bool */
    private $isFiltered = false;

    /**
     * Apply the sphinx client.
     */
    abstract protected function getSphinxClient(): SphinxClient;

    ///
    /// Query Building.
    ///

    /**
     * Apply a query where some text is matched.
     *
     * @param string $text The text to search.
     * @param string[] $fieldNames A prefix before the search keywords. Eg. `name`
     *
     * @return $this
     */
    public function whereText(string $text, array $fieldNames = []) {
        [$query, $terms] = self::prepareSearchText($text);

        $fieldNames = empty($fieldNames) ? '*' : '(' . implode(",", $fieldNames) . ')';
        $this->query = " @$fieldNames ($query)";
        $this->terms = array_merge($this->terms, $terms);
        return $this;
    }

    /**
     * Apply a sort mode the query.
     *
     * @param string|null $sort One of the SphinxQueryBuilder::SORT_* modes.
     * @param string|null $field
     *
     * @return $this
     */
    public function setSort(?string $sort = null, ?string $field = null) {
        if ($field) {
            $this->getSphinxClient()->setSortMode($sort ?? SphinxClient::SORT_ATTR_DESC, $field);
        } elseif ($sort === SphinxQueryConstants::SORT_DATE) {
            // If there is just one search term then we really want to just sort by date.
            $this->getSphinxClient()->setSelect('*, (dateinserted + 1) as sort');
            $this->getSphinxClient()->setSortMode(SphinxClient::SORT_ATTR_DESC, 'sort');
        } else {
            SphinxRanks::applyCustomRankFunction($this->getSphinxClient());
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
    public function setFilter(
        string $attribute,
        array $values,
        bool $exclude = false,
        string $filterOp = SphinxSearchQuery::FILTER_OP_OR
    ) {
        if ($attribute === 'type') {
            // this case handled by SphinxSearchDriver itself with method applyDtypes()
            return $this;
        }
        $allNumbers = true;
        foreach ($values as $value) {
            if (is_numeric($value)) {
                continue;
            } else {
                $allNumbers = false;
                break;
            }
        }

        $sphinxMethod = 'setFilter';

        if (!$allNumbers) {
            if (count($values) === 1) {
                $values = $values[0];
                $sphinxMethod = 'setFilterString';
            } else {
                $countString = count($values) === 0 ? 'none' : 'multiple';
                throw new SphinxSearchException("Sphinx string filters may only filter exactly 1 value, $countString were passed.");
            }
        }

        if ($filterOp === SphinxSearchQuery::FILTER_OP_AND) {
            foreach ($values as $value) {
                $this->getSphinxClient()->{$sphinxMethod}($attribute, $value);
            }
        } else {
            $this->getSphinxClient()->{$sphinxMethod}($attribute, $values, $exclude);
        }
        $this->isFiltered = true;
        return $this;
    }

    /**
     * Set string attribute to filter
     *
     * @param string $attribute
     * @param string $value
     * @param bool $exclude
     */
    public function setFilterString(string $attribute, string $value, bool $exclude = false) {
        $this->getSphinxClient()->setFilterString($attribute, $value);
    }


    /**
     * Set groupBy and groupFunc attributes
     *
     * @param string $attribute
     * @param int $func
     * @param string $groupSort
     *
     * @return $this
     */
    public function setGroupBy(string $attribute, int $func, string $groupSort = "@group desc") {
        $this->getSphinxClient()->setGroupBy($attribute, $func, $groupSort);
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
    public function setFilterRange(string $attribute, int $min, int $max, bool $exclude = false) {
        $this->getSphinxClient()->setFilterRange($attribute, $min, $max, $exclude);
        $this->isFiltered = true;
        return $this;
    }

    /**
     * Apply a date range over a query.
     *
     * @param string $attribute The field to apply the filter over.
     * @param \DateTimeInterface|null $startDate The start date or null.
     * @param \DateTimeInterface|null $endDate The end date or null.
     */
    public function applyDateRange(string $attribute, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate) {
        if (!$startDate) {
            $startDate = (new \DateTime())->setDate(1970, 1, 1)->setTime(0, 0, 0);
        }

        if (!$endDate) {
            $endDate = (new \DateTime())->setDate(2100, 12, 31)->setTime(0, 0, 0);
        }

        $this->setFilterRange($attribute, $startDate->getTimestamp(), $endDate->getTimestamp());
    }

    /**
     * Set index weights.
     *
     * @param array $weights Associative array of key value pairs: (string)indexName => (int)weight
     *
     * @return SphinxQueryTrait
     */
    public function setIndexWeights(array $weights) {
        $this->getSphinxClient()->setIndexWeights($weights);
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
    public function setRankingMode(int $ranker, string $rankExpr = "") {
        $this->getSphinxClient()->setRankingMode($ranker, $rankExpr);
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
