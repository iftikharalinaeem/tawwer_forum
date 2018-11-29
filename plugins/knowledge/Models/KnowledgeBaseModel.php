<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Garden\Schema\Schema;
use Gdn_Session;

/**
 * A model for managing knowledge categories.
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
     * KnowledgeCategoryModel constructor.
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

    public static function getAllTypes() {
        return [
            self::TYPE_GUIDE,
            self::TYPE_HELP
        ];
    }

    public static function getAllSorts() {
        return [
            self::ORDER_MANUAL,
            self::ORDER_NAME,
            self::ORDER_DATE_ASC,
            self::ORDER_DATE_DESC,
        ];
    }


}
