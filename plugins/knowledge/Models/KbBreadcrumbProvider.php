<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Knowledge\Models;

use Vanilla\Knowledge\Controllers\Pages\KnowledgeBasePage;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Navigation\BreadcrumbProviderInterface;
use Vanilla\Contracts\RecordInterface;

/**
 * Provide capabilities for generating a category breadcrumb.
 */
class KbBreadcrumbProvider implements BreadcrumbProviderInterface {

    /** @var KnowledgeCategoryModel */
    private $kbCategoryModel;

    /** @var KnowledgeBaseModel */
    private $kbModel;

    /** @var int */
    private $knowledgeBaseCount;

    /**
     * DI.
     *
     * @param KnowledgeCategoryModel $kbCategoryModel
     * @param KnowledgeBaseModel $kbModel
     */
    public function __construct(KnowledgeCategoryModel $kbCategoryModel, KnowledgeBaseModel $kbModel) {
        $this->kbCategoryModel = $kbCategoryModel;
        $this->kbModel = $kbModel;
        $this->knowledgeBaseCount = $kbModel->selectActiveKBCount();
    }

    /**
     * @inheritdoc
     */
    public function getForRecord(RecordInterface $record): array {
        if (!$record instanceof KbCategoryRecordType) {
            return [];
        }

        $categoryID = $record->getRecordID();
        $knowledgeBase = $this->kbModel->selectFragmentForCategoryID($categoryID);
        $categories = $this->kbCategoryModel->selectWithAncestors($categoryID);

        $result = [];

        foreach ($categories as $index => $category) {
            $isKbRoot = $category->getParentID() === KnowledgeCategoryModel::ROOT_ID;
            if ($isKbRoot) {
                // We are in knowledge base categoryID. We need to push
                $result[] = $knowledgeBase->asBreadcrumb();
            } else {
                $result[] = $category->asBreadcrumb();
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public static function getValidRecordTypes(): array {
        return [Navigation::RECORD_TYPE_CATEGORY, Navigation::RECORD_TYPE_ARTICLE];
    }
}
