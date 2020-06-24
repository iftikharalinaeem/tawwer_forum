<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Gdn_Session;
use Vanilla\Database\Operation;
use Vanilla\Exception\Database\NoResultsException;

/**
 * A model for managing articles.
 */
class ArticleReactionModel extends \Vanilla\Models\PipelineModel {
    const OWNER_TYPE = 'kb';
    const RECORD_TYPE = 'article';
    const TYPE_HELPFUL = 'helpful';
    const COUNT_ALL = 0;
    const COUNT_POSITIVE = 1;
    const COUNT_NEGATIVE = 2;
    const COUNT_ZERO = 3;

    const EMPTY_RESULT = [
        'positiveCount' => 0,
        'negativeCount' => 0,
        'neutralCount' => 0,
        'allCount' => 0
    ];

    /** Reaction 'helpful' po. */
    const HELPFUL_POSITIVE = 1;

    /** Deleted status value. */
    const HELPFUL_NEGATIVE = 0;

    const YES = "yes";
    const NO = "no";

    /**
     * ArticleReactionModel constructor.
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct("articleReaction");
    }

    /**
     * Update reaction counts
     *
     * @param int $articleID
     * @param string $reactionType
     *
     * @return array Array of all counts updated
     *
     * @throws ClientException If no reactions found for particular articleID and reactionType then throws an exception.
     */
    public function updateReactionCount(int $articleID, string $reactionType = self::TYPE_HELPFUL): array {
        $sql = $this->sql()
            ->from('reaction r')
            ->select('recordID articleID')
            ->select('IF(`reactionValue` > 0, 1, 0)', 'SUM', 'positiveCount')
            ->select('IF(`reactionValue` < 0, 1, 0)', 'SUM', 'negativeCount')
            ->select('IF(`reactionValue` = 0, 1, 0)', 'SUM', 'neutralCount')
            ->select('*', 'COUNT', 'allCount')
            ->where([
                'ownerType' => self::OWNER_TYPE,
                'reactionType' => $reactionType,
                'recordType' => self::RECORD_TYPE,
                'recordID' => $articleID
            ])
            ->join('reactionOwner ro', 'r.reactionOwnerID = ro.reactionOwnerID')
            ->groupBy([
                'r.reactionOwnerID',
                'recordID'
            ]);
        $res = $sql->get()->nextRow(DATASET_TYPE_ARRAY);

        if (is_array($res)) {
            $exists = $this->get(['articleID' => $articleID, 'reactionType' => $reactionType]);
            if (empty($exists)) {
                $this->insert($res);
            } else {
                $this->update($res, ['articleID' => $articleID, 'reactionType' => $reactionType]);
            }
        } else {
            throw new ClientException('No reactions found for article '.$articleID);
        }
        return $res;
    }

    /**
     * Get reaction counts
     *
     * @param int $articleID
     * @param string $reactionType
     *
     * @return array Array of all counts updated
     *
     * @throws ClientException If no reactions found for particular articleID and reactionType then throws an exception.
     */
    public function getReactionCount(int $articleID, string $reactionType = self::TYPE_HELPFUL): array {
        $reactionParams = ['articleID' => $articleID, 'reactionType' => $reactionType];
        $res = $this->get($reactionParams);
        if (empty($res)) {
            $res = array_merge($reactionParams, self::EMPTY_RESULT);
        } else {
            $res = $res[0];
        }
        return $res;
    }

    /**
     * Get number of reactions of specific type for user
     *
     * @param string $reactionType
     * @param int $articleID
     * @param int $userID
     * @return int
     */
    public function userReactionCount(string $reactionType, int $articleID, int $userID): int {
        $sql = $this->sql()
            ->from('reaction r')
            ->select('reactionID', 'COUNT', 'count')
            ->where([
                'ownerType' => self::OWNER_TYPE,
                'reactionType' => $reactionType,
                'recordType' => self::RECORD_TYPE,
                'recordID' => $articleID,
                'insertUserID' => $userID
            ])
            ->groupBy([
                'insertUserID'
            ]);
        $res = $sql->get()->nextRow(DATASET_TYPE_ARRAY);

        return is_array($res) ? $res['count'] : 0;
    }

    /**
     * Check if user already reacted on article.
     *
     * @param string $reactionType
     * @param int $articleID
     * @param int $userID
     * @return string|null
     */
    public function getUserReaction(string $reactionType, int $articleID, int $userID): ?string {
        $sql = $this->sql()
            ->from('reaction r')
            ->select('reactionValue')
            ->where([
                'ownerType' => self::OWNER_TYPE,
                'reactionType' => $reactionType,
                'recordType' => self::RECORD_TYPE,
                'recordID' => $articleID,
                'r.insertUserID' => $userID
            ])
            ->join('reactionOwner ro', 'r.reactionOwnerID = ro.reactionOwnerID')
            ->limit(1);
        $res = $sql->get()->nextRow(DATASET_TYPE_ARRAY);

        $result = $res['reactionValue'] ?? null;

        if ($result === self::HELPFUL_NEGATIVE) {
            return self::NO;
        } elseif ($result === self::HELPFUL_POSITIVE) {
            return self::YES;
        }

        return null;
    }

    /**
     * Check if reaction matching foreignID exists.
     *
     * @param string $foreignID
     * @return string|null
     */
    public function getReactionByForeignID(string $foreignID): ?string {
        $sql = $this->sql()
            ->from('reaction r')
            ->select('reactionValue')
            ->where([
                'foreignID' => $foreignID,
            ])
            ->join('reactionOwner ro', 'r.reactionOwnerID = ro.reactionOwnerID')
            ->limit(1);
        $res = $sql->get()->nextRow(DATASET_TYPE_ARRAY);
        $result = $res['reactionValue'] ?? null;

        if ($result === self::HELPFUL_NEGATIVE) {
            return self::NO;
        } elseif ($result === self::HELPFUL_POSITIVE) {
            return self::YES;
        }
        return null;
    }

    /**
     * Return all possible statuses for article record/item
     *
     * @return array
     */
    public static function getHelpfulReactions(): array {
        return [
            self::NO,
            self::YES,
            null
        ];
    }

    /**
     * Return default ReactionModel fields to insert
     *
     * @param int $recordID
     * @param string $reactionType
     * @param int $reactionValue
     *
     * @return array
     */
    public static function getReactionFields(int $recordID, string $reactionType, int $reactionValue): array {
        return [
            'ownerType' => self::OWNER_TYPE,
            'recordType' => self::RECORD_TYPE,
            'recordID' => $recordID,
            'reactionType' => $reactionType,
            'reactionValue' => $reactionValue,
        ];
    }

    /**
     * Get all reactions available for articleReactions model.
     *
     * @return array
     */
    public static function getReactionTypes(): array {
        return [
            self::TYPE_HELPFUL
        ];
    }
}
