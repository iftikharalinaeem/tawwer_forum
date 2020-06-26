<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

 namespace Vanilla\Knowledge\Models;

 use Vanilla\Contracts\RecordInterface;

 /**
  * An instance of a knowledge category.
  */
class KbCategoryRecordType implements RecordInterface {

    /** @var int */
    private $knowledgeCategoryID;

    /**
     * Constructor.
     *
     * @param int $knowledgeCategoryID
     */
    public function __construct(int $knowledgeCategoryID) {
        $this->knowledgeCategoryID = $knowledgeCategoryID;
    }

    /**
     * @inheritDoc
     */
    public function getRecordID(): int {
        return $this->knowledgeCategoryID;
    }

    /**
     * @inheritDoc
     */
    public function getRecordType(): string {
        return KnowledgeNavigationModel::RECORD_TYPE_CATEGORY;
    }
}
