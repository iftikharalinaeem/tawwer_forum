<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Garden\Web\Exception\ClientException;
use Gdn_Session;
use Vanilla\Database\Operation;

/**
 * A model for managing article revisions.
 */
class ArticleRevisionModel extends \Vanilla\Models\PipelineModel {
    const STATUS_TRANSLATION_UP_TO_DATE = "up-to-date";
    const STATUS_TRANSLATION_OUT_TO_DATE = "out-of-date";
    const STATUS_TRANSLATION_NOT_TRANSLATED = "not-translated";

    const DEFAULT_LIMIT = 100;

    private $session;

    /**
     * ArticleModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct('articleRevision');
        $this->session = $session;

        $dateProcessor = new \Vanilla\Database\Operation\CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted"])
            ->setUpdateFields([]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new \Vanilla\Database\Operation\CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID"])
            ->setUpdateFields([]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Update the published revision on an article.
     *
     * @param int $articleRevisionID
     * @throws \Exception If a general database error is encountered while updating the rows.
     * @throws \Garden\Schema\ValidationException If row field updates fail validating against the schema.
     * @throws \Vanilla\Exception\Database\NoResultsException If the revision's article could not be found.
     */
    public function publish(int $articleRevisionID, int $updateUserID = null, string $mode = Operation::MODE_DEFAULT) {
        $row = $this->selectSingle(["articleRevisionID" => $articleRevisionID]);
        $articleID = $row["articleID"];
        $locale = $row["locale"];

        // Remove the "published" flag from the currently-published revision.
        $this->update(
            ["status" => null, "insertUserID" => $updateUserID],
            ["articleID" => $articleID, "status" => "published", "locale" => $locale],
            $mode
        );
        // Publish this revision.
        $this->update(
            ["status" => "published", "insertUserID" => $updateUserID],
            ["articleRevisionID" => $articleRevisionID],
            $mode
        );
    }

    /**
     * Get revisions count for the article
     *
     * @param int $articleID
     * @return int
     */
    public function getRevisionsCount(int $articleID): int {
        $sqlDriver = $this->sql();

        $sqlDriver->select('articleRevisionID', 'count', 'revisionsCount');

        $result = $sqlDriver->getWhere($this->getTable(), ["articleID" => $articleID])->resultArray();

        return current($result)["revisionsCount"];
    }

    /**
     * @return array
     */
    public static function getTranslationStatuses(): array {
        return [
            self::STATUS_TRANSLATION_NOT_TRANSLATED,
            self::STATUS_TRANSLATION_OUT_TO_DATE,
            self::STATUS_TRANSLATION_UP_TO_DATE
        ];
    }
}
