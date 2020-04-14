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
use Vanilla\Site\SiteSectionModel;

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

    /** @var SiteSectionModel $siteSectionModel */
    private $siteSectionModel;

    /**
     * DI.
     *
     * @param KnowledgeCategoryModel $kbCategoryModel
     * @param KnowledgeBaseModel $kbModel
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(
        KnowledgeCategoryModel $kbCategoryModel,
        KnowledgeBaseModel $kbModel,
        SiteSectionModel $siteSectionModel
    ) {
        $this->kbCategoryModel = $kbCategoryModel;
        $this->kbModel = $kbModel;
        $this->knowledgeBaseCount = $kbModel->selectActiveKBCount();
        $this->siteSectionModel = $siteSectionModel;
    }

    /**
     * @inheritdoc
     */
    public function getForRecord(RecordInterface $record, string $locale = null): array {
        if (!$record instanceof KbCategoryRecordType) {
            return [];
        }

        $categoryID = $record->getRecordID();
        $knowledgeBase = $this->kbModel->selectFragmentForCategoryID($categoryID, $locale);

        $result = [];

        if ($knowledgeBase->getViewType() === KnowledgeBaseModel::TYPE_GUIDE) {
            $result[] = $knowledgeBase->asBreadcrumb();
        } else {
            $categories = $this->kbCategoryModel->selectWithAncestors($categoryID, $locale);
            foreach ($categories as $index => $category) {
                if ($category->getParentID() === KnowledgeCategoryModel::ROOT_ID) {
                    $result[] = $knowledgeBase->asBreadcrumb();
                } else {
                    $result[] = $category->asBreadcrumb();
                }
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public static function getValidRecordTypes(): array {
        return [KnowledgeNavigationModel::RECORD_TYPE_CATEGORY, KnowledgeNavigationModel::RECORD_TYPE_ARTICLE];
    }
}
