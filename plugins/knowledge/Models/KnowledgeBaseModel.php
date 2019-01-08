<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Garden\Schema\Schema;
use Gdn_Session;

/**
 * A model for managing knowledge bases.
 */
class KnowledgeBaseModel extends \Vanilla\Models\PipelineModel {
    const TYPE_GUIDE = 'guide';
    const TYPE_HELP = 'help';

    const ORDER_MANUAL = 'manual';
    const ORDER_NAME = 'name';
    const ORDER_DATE_ASC = 'dateInserted';
    const ORDER_DATE_DESC = 'dateInsertedDesc';


    /** @var Gdn_Session */
    private $session;

    /**
     * KnowledgeBaseModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct("knowledgeBase");
        $this->session = $session;

        $dateProcessor = new \Vanilla\Database\Operation\CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])
            ->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new \Vanilla\Database\Operation\CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])
            ->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Generate a URL to the provided knowledge base row.
     *
     * @param array $knowledgeBase An knowledge base row.
     * @param bool $withDomain
     * @return string
     * @throws \Exception If the row does not contain a valid ID or name.
     */
    public function url(array $knowledgeBase, bool $withDomain = true): string {
        $urlCode = $knowledgeBase["urlCode"] ?? null;

        if (!$urlCode) {
            throw new \Exception('Invalid knowledge-base row.');
        }

        $slug = \Gdn_Format::url($urlCode);
        $result = \Gdn::request()->url("/kb/" . $slug, $withDomain);
        return $result;
    }

    /**
     * Get list of all knowledge base types
     *
     * @return array
     */
    public static function getAllTypes(): array {
        return [
            self::TYPE_GUIDE,
            self::TYPE_HELP
        ];
    }

    /**
     * Gat list of all knowledge base options for article order
     *
     * @return array
     */
    public static function getAllSorts(): array {
        return [
            self::ORDER_MANUAL,
            self::ORDER_NAME,
            self::ORDER_DATE_ASC,
            self::ORDER_DATE_DESC,
        ];
    }
}
