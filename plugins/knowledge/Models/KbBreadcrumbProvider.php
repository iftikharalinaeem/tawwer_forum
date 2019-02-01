<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Knowledge\Models;

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

    /**
     *DI.
     *
     * @param KnowledgeCategoryModel $kbCategoryModel
     * @param KnowledgeBaseModel $kbModel
     */
    public function __construct(KnowledgeCategoryModel $kbCategoryModel, KnowledgeBaseModel $kbModel) {
        $this->kbCategoryModel = $kbCategoryModel;
        $this->kbModel = $kbModel;
    }

    /**
     * @inheritdoc
     */
    public function getForRecord(RecordInterface $record): array {
        $categoryCrumbs = $this->kbCategoryModel->buildBreadcrumbs($record->getRecordID());

        return $categoryCrumbs;
    }

    /**
     * @inheritdoc
     */
    public static function getRecordType(): string {
        return Navigation::RECORD_TYPE_CATEGORY;
    }
}
