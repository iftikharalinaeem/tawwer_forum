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
class KnowledgeCategoryRecord implements RecordInterface {

    /** @var int */
    private $knowledgeCategoryID;

    /**
     * Constructor.
     *
     * @param int $knowledgeCategoryID
     */
    public function __construction(int $knowledgeCategoryID) {
        $this->knowledgeCategoryID = $knowledgeCategoryID;
    }

    /**
     * @inheritDoc
     */
    public function getRecordID(): int {
        return $this->recordID;
    }

    /**
     * @inheritDoc
     */
    public function getRecordType(): string {
        return Navigation::RECORD_TYPE_CATEGORY;
    }
}
