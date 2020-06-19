<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Sphinx\Search;

use Vanilla\Adapters\SphinxClient;

/**
 * Ranking information for sphinx queries.
 */
class SphinxRanks {

    /**
     * Get score rankings for sphinx.
     *
     * @return array
     */
    public static function getScoreRanking(): array {
        return [
            'items' => [-5, 0, 1, 3, 5, 10, 19],
            'add' => -1,
            'weight' => 1
        ];
    }

    /**
     * Get date rankings for sphinx.
     *
     * @return array
     */
    public static function getDateRanking(): array {
        return [
            'items' => [
                strtotime('-2 years'),
                strtotime('-1 year'),
                strtotime('-6 months'),
                strtotime('-3 months'),
                strtotime('-1 month'),
                strtotime('-1 week'),
                strtotime('-1 day')
            ],
            'add' => -4,
            'weight' => 1
        ];
    }

    /**
     * @return array
     */
    public static function getAllRankingCriteria(): array {
        return [
            'score' => self::getScoreRanking(),
            'dateinserted' => self::getDateRanking(),
        ];
    }

    /**
     * ???
     *
     * @return int
     */
    public static function getMaxScore(): int {
        $maxScore = 0;
        foreach (self::getAllRankingCriteria() as $field => $row) {
            $items = $row['items'];
            $weight = $row['weight'];
            $add = $row['add'];
            $maxScore += $weight * (count($items) + $add);
        }

        return $maxScore;
    }

    /**
     * Apply a custom ranked sort function to the sphinx client.
     *
     * @param SphinxClient $sphinxClient
     */
    public static function applyCustomRankFunction(SphinxClient $sphinxClient) {
        $funcs = [];
        foreach (self::getAllRankingCriteria() as $field => $row) {
            $items = $row['items'];
            $weight = $row['weight'];
            $add = $row['add'];

            $func = "interval($field, " . implode(', ', $items) . ")";
            if ($add > 0) {
                $func = "($func +$add)";
            } elseif ($add < 0) {
                $func = "($func $add)";
            }

            if ($weight != 1) {
                $func .= " * $weight";
            }

            $funcs[] = "$func";
        }

        $maxScore = self::getMaxScore();
        if ($maxScore > 0) {
            $mult = 1 / $maxScore;

            $fullfunc = implode(' + ', $funcs);
            $sort = "(($fullfunc) * $mult + 1) * WEIGHT()";
            trace($sort, 'sort');

            $sphinxClient->setSelect("*, $sort as sort");
            $sphinxClient->setSortMode(SphinxClient::SORT_ATTR_DESC, 'sort');
        }
    }
}
