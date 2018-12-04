<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models\Entities;

use Vanilla\Knowledge\Models\KnowledgeBaseModel;

/**
 * A knowledge base entity.
 */
class KnowledgeBaseEntity extends Entity {
    const TABLE = 'knowledgeBase';
    const FIELDS = [
        'knowledgeBaseID',
        'name',
        'description',
        'urlCode',
        'icon',
        'sourceLocale',
        'viewType',
        'sortArticles',
        'insertUserID',
        'dateInserted',
        'updateUserID',
        'dateUpdated',
        'countArticles',
        'countCategories',
        'rootCategoryID',
    ];

    protected $knowledgeBaseID = 0; //int
    protected $name = '';
    protected $description = '';
    protected $urlCode = '';
    protected $icon = '';
    protected $sourceLocale = '';
    protected $viewType = KnowledgeBaseModel::TYPE_GUIDE;
    protected $sortArticles = KnowledgeBaseModel::ORDER_MANUAL;
    protected $insertUserID = 0;
    protected $dateInserted = 0;
    protected $updateUserID = 0;
    protected $dateUpdated = 0;
    protected $countArticles = 0;
    protected $countCategories = 0;
    protected $rootCategoryID = 0;

    /**
     * Get list entity properties
     *
     * @inheritdoc
     *
     * @return array
     */
    protected function getEntityFields(): array {
        return self::FIELDS;
    }

    /**
     * Name setter
     *
     * @param string $name
     * @return KnowledgeBaseEntity
     */
    protected function setName(string $name): KnowledgeBaseEntity {
        $this->name = $name;
        return $this;
    }

    /**
     * Icon url setter
     *
     * @param string $icon
     * @return KnowledgeBaseEntity
     */
    protected function setIcon(string $icon): KnowledgeBaseEntity {
        $this->icon = $icon;
        return $this;
    }

    /**
     * SourceLocale setter
     *
     * @param string $locale
     * @return KnowledgeBaseEntity
     */
    protected function setSourceLocale(string $locale): KnowledgeBaseEntity {
        $this->sourceLocale = $locale;
        return $this;
    }

    /**
     * Description setter
     *
     * @param string $description
     * @return KnowledgeBaseEntity
     */
    protected function setDescription(string $description): KnowledgeBaseEntity {
        $this->description = $description;
        return $this;
    }

    /**
     * Type setter
     *
     * @param string $type
     * @return KnowledgeBaseEntity
     * @throws \Exception Exception is thrown when type is not valid.
     */
    protected function setViewType(string $type): KnowledgeBaseEntity {
        if (!in_array($type, KnowledgeBaseModel::getAllTypes())) {
            throw new \Exception('Type "'.$type.'"" is not valid knowledge base type.');
        }
        $this->viewType = $type;
        return $this;
    }

    /**
     * SortArticles setter
     *
     * @param string $sortArticles
     * @return KnowledgeBaseEntity
     * @throws \Exception Exception is thrown when sort option is not valid.
     */
    protected function setSortArticles(string $sortArticles): KnowledgeBaseEntity {
        if (!in_array($sortArticles, KnowledgeBaseModel::getAllSorts())) {
            throw new \Exception('Order type "'.$sortArticles.'"" is not valid knowledge base sort type.');
        }
        $this->sortArticles = $sortArticles;
        return $this;
    }
}
