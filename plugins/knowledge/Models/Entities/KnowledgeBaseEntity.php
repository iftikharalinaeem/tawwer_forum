<?php
/**
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models\Entities;

use Vanilla\Knowledge\Models\KnowledgeBaseModel;


/**
 * A knowledge base entity.
 */
class KnowledgeBaseEntity {
    const TABLE = 'knowledgeBase';
    public static $fields = [
        'knowledgeBaseID',
        'name',
        'description',
        'urlCode',
        'icon',
        'sourceLocale',
        'type',
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
    protected $type = KnowledgeBaseModel::TYPE_GUIDE;
    protected $sortArticles = KnowledgeBaseModel::ORDER_MANUAL;
    protected $insertUserID = 0;
    protected $dateInserted = 0;
    protected $updateUserID = 0;
    protected $dateUpdated = 0;
    protected $countArticles = 0;
    protected $countCategories = 0;
    protected $rootCategoryID = 0;

    public function __construct(array $fields = []) {
        foreach ($fields as $k => $v) {
            if (in_array($k, self::$fields)) {
                $this->__set($k, $v);
            }
        }
    }

    public function __get($propName) {
        if (property_exists($this, $propName)) {
            return $this->$propName;
        } else {
            throw new \Exception('Call to undefined property '.$propName.' of '.__CLASS__);
        }
    }

    public function __set($propName, $value): KnowledgeBaseEntity {
        $setter = 'set'.ucfirst($propName);
        if (method_exists($this, $setter)) {
            return $this->$setter($value);
        } else {
            throw new \Exception('Method '.$propName.'() is not supported by '.__CLASS__);
        }
    }
    public function asArray() {
        $res = [];
        foreach (self::$fields as $fieldKey) {
            $res[$fieldKey] = $this->$fieldKey;
        }
        return $res;
    }

    protected function setName(string $name): KnowledgeBaseEntity {
        $this->name = $name;
        return $this;
    }

    protected function setDescription(string $description): KnowledgeBaseEntity {
        $this->description = $description;
        return $this;
    }

    protected function setType(string $type): KnowledgeBaseEntity {
        if (!in_array($type, KnowledgeBaseModel::getAllTypes())) {
            throw new \Exception('Type "'.$type.'"" is not valid knowledge base type.');
        }
        $this->type = $type;
        return $this;
    }

    protected function setSortArticles(string $sortArticles): KnowledgeBaseEntity {
        if (!in_array($sortArticles, KnowledgeBaseModel::getAllSorts())) {
            throw new \Exception('Order type "'.$sortArticles.'"" is not valid knowledge base sort type.');
        }
        $this->sortArticles = $sortArticles;
        return $this;
    }

}
