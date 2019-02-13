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

    /** Reaction 'helpful' po. */
    const HELPFUL_POSITIVE = 1;

    /** Deleted status value. */
    const HELPFUL_NEGATIVE = 0;

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
            ->groupBy([
                'ownerType',
                'reactionType',
                'recordType',
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
     * Return all possible statuses for article record/item
     *
     * @return array
     */
    public static function getHelpfulReactions(): array {
        return [
            "yes",
            "no"
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
