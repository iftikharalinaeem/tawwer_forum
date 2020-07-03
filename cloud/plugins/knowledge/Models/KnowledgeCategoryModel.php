<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Gdn_Session;
use Vanilla\Database\Operation;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Models\FullRecordCacheModel;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Site\TranslationModel;
use Vanilla\Contracts\Site\TranslationProviderInterface;

/**
 * A model for managing knowledge categories.
 */
class KnowledgeCategoryModel extends FullRecordCacheModel {
    const RECORD_TYPE = 'knowledgeCategory';

    const RECORD_ID_FIELD = 'knowledgeCategoryID';

    /** Maximum articles allowed in a guide KB root-level category. */
    const ROOT_LIMIT_GUIDE_ARTICLES_RECURSIVE = 1000;

    /** Maximum child categories allowed in a KB root-level category. */
    const ROOT_LIMIT_CATEGORIES_RECURSIVE = 500;

    /** @var int Root-level category ID. */
    const ROOT_ID = -1;

    const SORT_INCREMNENT = true;

    const SORT_DECREMENT = false;

    const SORT_TYPE_ARTICLE = 'article';

    const SORT_TYPE_CATEGORY = 'knowledgeCategory';

    /** @var KnowledgeBaseModel */
    private $knowledgeBaseModel;

    /** @var Gdn_Session */
    private $session;

    /** @var SiteSectionModel $siteSectionModel */
    private $siteSectionModel;

    /** @var TranslationProviderInterface */
    private $translation;

    /**
     * KnowledgeCategoryModel constructor.
     *
     * @param Gdn_Session $session
     * @param KnowledgeBaseModel $knowledgeBaseModel
     * @param SiteSectionModel $siteSectionModel
     * @param TranslationModel $translationModel
     * @param \Gdn_Cache $cache
     */
    public function __construct(
        Gdn_Session $session,
        KnowledgeBaseModel $knowledgeBaseModel,
        SiteSectionModel $siteSectionModel,
        TranslationModel $translationModel,
        \Gdn_Cache $cache
    ) {
        parent::__construct("knowledgeCategory", $cache);

        $this->knowledgeBaseModel = $knowledgeBaseModel;
        $this->session = $session;
        $this->siteSectionModel = $siteSectionModel;
        $this->translation = $translationModel->getContentTranslationProvider();

        $dateProcessor = new \Vanilla\Database\Operation\CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])
            ->setUpdateFields(["dateUpdated"])
        ;
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new \Vanilla\Database\Operation\CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])
            ->setUpdateFields(["updateUserID"])
        ;
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Configure a Garden Schema instance for write operations by the model.
     *
     * @param Schema $schema Schema representing the resource's database table.
     * @return Schema Currently configured write schema.
     */
    protected function configureWriteSchema(Schema $schema): Schema {
        $schema = parent::configureWriteSchema($schema);
        $schema->addValidator("parentID", [$this, "validateParentID"]);

        return $schema;
    }

    /**
     * Given a knowledge base ID, get all categories therein. Does not include the root category.
     *
     * @param int $knowledgeBaseID
     * @return int
     */
    private function getTotalInKnowledgeBase(int $knowledgeBaseID): int {
        $result = $this->sql()
            ->select("knowledgeCategoryID", "count", "total")
            ->from($this->getTable())
            ->where([
                "knowledgeBaseID" => $knowledgeBaseID,
                "parentID <>" => self::ROOT_ID, // Don't include the knowledge base root as part of the categories in a knowledge base.
            ])
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY)
        ;

        return $result["total"] ?? 0;
    }

    /**
     * Given a category ID, get its root-level parent in the knowledge base.
     *
     * @param int $knowledgeCategoryID
     * @return array
     */
    private function selectRootCategory(int $knowledgeCategoryID): array {
        $category = $this->selectSingle(['knowledgeCategoryID' => $knowledgeCategoryID]);

        if ($category["parentID"] !== self::ROOT_ID) {
            try {
                $category = $this->selectSingle([
                    "knowledgeBaseID" => $category['knowledgeBaseID'],
                    "parentID" => self::ROOT_ID,
                ]);
            } catch (NoResultsException $e) {
                throw new NoResultsException("Root category not found.");
            }
        }

        return $category;
    }

    /**
     * Given a category ID, get the row and the rows of all its ancestors in order.
     *
     * @param int $categoryID
     * @param string $locale
     * @return KbCategoryFragment[]
     *
     * @throws \Garden\Schema\ValidationException If a queried row fails to validate against its output schema.
     * @throws \Vanilla\Exception\Database\NoResultsException If the target category or its ancestors cannot be found.
     */
    public function selectWithAncestors(int $categoryID, string $locale = null): array {
        $result = [];

        do {
            $row = $this->selectSingleFragment($categoryID, $locale);
            array_unshift($result, $row);
            $categoryID = $row->getParentID();
        } while ($categoryID > 0);

        return $result;
    }

    /**
     * Select a KbCategoryFragment for a given id.
     *
     * @param int $categoryID Conditions for the select query.
     * @param string $locale
     *
     * @return KbCategoryFragment
     *
     * @throws ValidationException If the data from the DB was corrupted.
     * @throws NoResultsException If no record was found for the given ID.
     */
    public function selectSingleFragment(int $categoryID, string $locale = null): KbCategoryFragment {
        $rows = $this->modelCache->getCachedOrHydrate(
            [
                'isFragment' => true,
                'categoryID' => $categoryID,
            ],
            function () use ($categoryID) {
                return $this->createSql()
                    ->select('knowledgeCategoryID, knowledgeBaseID, parentID, sort, name')
                    ->getWhere($this->getTable(), ['knowledgeCategoryID' => $categoryID], null, null, 1)
                    ->resultArray();
            }
        );

        if (empty($rows)) {
            throw new NoResultsException("Could not find category fragment for id $categoryID");
        }
        $result = reset($rows);
        if ($this->translation && !empty($locale)) {
            $result = $this->translation->translateProperties(
                $locale,
                'kb',
                self::RECORD_TYPE,
                self::RECORD_ID_FIELD,
                [$result],
                ['name']
            )[0];
        }
        $result['locale'] = $locale ?? $this->knowledgeBaseModel->selectSingle(
            ['knowledgeBaseID' => $result['knowledgeBaseID']]
        )['sourceLocale'];

        // Normalize the fragment.
        $result['url'] = $this->url($result);

        return new KbCategoryFragment($result);
    }

    /**
     * Update existing knowledge categories.
     *
     * @inheritdoc
     */
    public function update(array $set, array $where, $options = []): bool {
        if (array_key_exists("parentID", $set) && array_key_exists("knowledgeCategoryID", $where)) {
            if ($set["parentID"] === $where["knowledgeCategoryID"]) {
                throw new \Garden\Web\Exception\ClientException("Cannot set the parent of a knowledge category to itself.");
            }
        }

        return parent::update($set, $where, $options);
    }

    /**
     * Get a collection for a knowledge base.
     *
     * @param int $knowledgeBaseID
     * @return KnowledgeCategoryCollection
     */
    public function getCollectionForKB(int $knowledgeBaseID): KnowledgeCategoryCollection {
        $categories = $this->get(
            [ "knowledgeBaseID" => $knowledgeBaseID ],
            [
                'orderFields' => 'sort',
                'orderDirection' => 'asc'
            ]
        );
        return new KnowledgeCategoryCollection($categories);
    }

    /**
     * Given a category ID, verify its knowledge base has not met or exceeded its limit of articles, based on the root-level category.
     *
     * @param int $knowledgeCategoryID
     * @param \Garden\Schema\ValidationField $validationField
     * @return bool
     * @throws \Garden\Schema\ValidationException If the selected row fails output validation.
     */
    public function validateKBArticlesLimit(int $knowledgeCategoryID, \Garden\Schema\ValidationField $validationField): bool {
        try {
            $category = $this->selectRootCategory($knowledgeCategoryID);
            $knowledgeBase = $this->knowledgeBaseModel->selectSingle(["rootCategoryID" => $category["knowledgeCategoryID"]]);
        } catch (NoResultsException $e) {
            // Couldn't find the KB or root category. Maybe bad data. Unable to gather enough relevant data to perform validation.
            return true;
        }

        // Guides are currently the only KB type with a limit, due to the desire to keep the size of the navigation tree low.
        // Use the root category's recursive article count as a hint for the total number of articles in its knowledge base.
        if ($knowledgeBase["viewType"] === KnowledgeBaseModel::TYPE_GUIDE
            && $category["articleCountRecursive"] >= self::ROOT_LIMIT_GUIDE_ARTICLES_RECURSIVE
        ) {
            $validationField->getValidation()->addError(
                $validationField->getName(),
                "The article maximum has been reached for this knowledge base."
            );

            return false;
        }

        return true;
    }

    /**
     * Given a category ID, verify its knowledge base has not met or exceeded its limit of categories.
     *
     * @param int $knowledgeCategoryID
     * @param \Garden\Schema\ValidationField $validationField
     * @return bool
     * @throws \Garden\Schema\ValidationException If the selected row fails output validation.
     */
    public function validateKBCategoriesLimit(int $knowledgeCategoryID, \Garden\Schema\ValidationField $validationField): bool {
        try {
            $category = $this->selectSingleFragment($knowledgeCategoryID);
            $knowledgeBase = $this->knowledgeBaseModel->selectSingle(["knowledgeBaseID" => $category->knowledgeCategoryID]);
        } catch (NoResultsException $e) {
            // Couldn't find the category. Maybe bad data. Unable to gather enough relevant data to perform validation.
            return true;
        }

        if ($knowledgeBase["countCategories"] >= self::ROOT_LIMIT_CATEGORIES_RECURSIVE) {
            $validationField->getValidation()->addError(
                $validationField->getName(),
                "The category maximum has been reached for this knowledge base."
            );

            return false;
        }

        return true;
    }

    /**
     * Validate the value of parentID when updating a row. Compatible with \Garden\Schema\Schema.
     *
     * @param int $parentID
     * @param \Garden\Schema\ValidationField $validationField
     * @return bool
     * @throws \Garden\Schema\ValidationException If the selected row fails output validation.
     */
    public function validateParentID(int $parentID, \Garden\Schema\ValidationField $validationField): bool {
        if ($parentID !== self::ROOT_ID) {
            try {
                $this->selectSingle(["knowledgeCategoryID" => $parentID]);
            } catch (NoResultsException $e) {
                $validationField->getValidation()->addError(
                    $validationField->getName(),
                    "Parent category does not exist."
                );

                return false;
            }
        }

        return true;
    }

    /**
     * Generate a URL to the provided knowledge category.
     *
     * @param array $knowledgeCategory
     * @param bool $withDomain
     * @return string
     * @throws \Exception If the row does not contain a valid ID or name.
     */
    public function url(array $knowledgeCategory, bool $withDomain = true): string {
        $name = $knowledgeCategory["name"] ?? null;
        $knowledgeCategoryID = $knowledgeCategory["knowledgeCategoryID"] ?? null;

        if (!$name || !$knowledgeCategoryID) {
            throw new \Exception("Invalid knowledge category row.");
        }

        $slug = \Gdn_Format::url("{$knowledgeCategoryID}-{$name}");

        $locale = $knowledgeCategory['locale']
            ?? $knowledgeCategory['sourceLocale']
            ?? $this->siteSectionModel->getCurrentSiteSection()->getContentLocale();
        $siteSectionSlug = $this->knowledgeBaseModel->getSiteSectionSlug($knowledgeCategory['knowledgeBaseID'], $locale);
        $result = \Gdn::request()->getSimpleUrl($siteSectionSlug . "/kb/categories/" . $slug);

        return $result;
    }

    /**
     * Recalculate and update articleCount, articleCountRecursive and childCategoryCount columns
     *
     * @param int $knowledgeCategoryID Category id to recalculate
     * @param bool $updateParents Flag for recursive or non-recursive mode to update all parents
     *
     * @return bool Return tru when record updated succesfully
     */
    public function updateCounts(int $knowledgeCategoryID, bool $updateParents = true): bool {
        $countCategories = $this->sql()
            ->select('c.knowledgeCategoryID')
            ->select('DISTINCT children.knowledgeCategoryID', 'COUNT', 'childrenCount')
            ->select('children.articleCountRecursive', 'SUM', 'countRecursive')
            ->from('knowledgeCategory c')
            ->leftJoin('knowledgeCategory children', 'children.parentID = c.knowledgeCategoryID')
            ->where('c.knowledgeCategoryID', $knowledgeCategoryID)
            ->groupBy('c.knowledgeCategoryID')
            ->get()->nextRow(DATASET_TYPE_ARRAY)
        ;
        $countArticles = $this->sql()
            ->select('c.knowledgeCategoryID')
            ->select('DISTINCT a.articleID', 'COUNT', 'articleCount')
            ->from('knowledgeCategory c')
            ->leftJoin('article a', 'a.knowledgeCategoryID = c.knowledgeCategoryID AND a.status = \'' . ArticleModel::STATUS_PUBLISHED . '\'')
            ->where(['c.knowledgeCategoryID' => $knowledgeCategoryID])
            ->groupBy('c.knowledgeCategoryID')
            ->get()->nextRow(DATASET_TYPE_ARRAY)
        ;

        if (is_array($countCategories) && is_array($countArticles)) {
            $res = $this->update(
                [
                    'articleCount' => $countArticles['articleCount'],
                    'articleCountRecursive' => ($countArticles['articleCount'] + $countCategories['countRecursive']),
                    'childCategoryCount' => $countCategories['childrenCount'],
                ],
                [
                    'knowledgeCategoryID' => $knowledgeCategoryID,
                ]
            );
            if ($res && $updateParents) {
                $categories = $this->selectWithAncestors($knowledgeCategoryID);
                $categories = array_column($categories, null, 'knowledgeCategoryID');
                $cat = $categories[$knowledgeCategoryID];
                while ($parent = ($categories[$cat->getParentID()] ?? false)) {
                    $res = $this->updateCounts($parent->getKnowledgeCategoryID(), false);
                    $cat = $parent;
                }
                if ($updateParents) {
                    $this->knowledgeBaseModel->updateCounts($cat->getKnowledgeBaseID());
                }

                return $res;
            } else {
                return $res;
            }
        } else {
            return false;
        }
    }

    /**
     * Recalculate all counts fields for all categories of knowledgeBase
     *
     * @param int $knowledgeBaseID Knowledge Base id to recalculate
     *
     * @return bool Return tru when record updated successfully
     */
    public function resetAllCounts(int $knowledgeBaseID): bool {
        $notParent = $this->sql()
            ->select('c.knowledgeCategoryID')
            ->from('knowledgeCategory c')
            ->leftJoin('knowledgeCategory children', 'children.parentID = c.knowledgeCategoryID')
            ->where('c.knowledgeBaseID', $knowledgeBaseID)
            ->where('children.knowledgeCategoryID', null)
            ->get()->resultArray()
        ;
        if (is_array($notParent)) {
            foreach ($notParent as $cat) {
                $this->updateCounts($cat['knowledgeCategoryID']);
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Get max sort among all direct childre of category (including sub categories and articles)
     *
     * @param int $knowledgeCategoryID
     * @return array [maxSort => int, viewType => guide|help, sortArticles => manual|...]
     */
    public function getMaxSortIdx(int $knowledgeCategoryID): array {

        $sortInfo = $this->sql()
            ->select('sub.sort', 'MAX', 'maxSort')
            //->select('DISTINCT c.knowledgeCategoryID', 'COUNT', 'children')
            ->select('kb.viewType')
            ->select('kb.sortArticles')
            ->from('knowledgeCategory c')
            ->where([
                'c.knowledgeCategoryID' => $knowledgeCategoryID,
            ])
            ->join('knowledgeBase kb', 'kb.knowledgeBaseID = c.knowledgeBaseID')
            ->leftJoin('knowledgeCategory sub', 'sub.parentID = c.knowledgeCategoryID')
            ->groupBy('c.knowledgeCategoryID, kb.knowledgeBaseID, kb.viewType, kb.sortArticles')
            ->get()->nextRow(DATASET_TYPE_ARRAY)
        ;

        if ($sortInfo['viewType'] === KnowledgeBaseModel::TYPE_GUIDE) {
            //only check articles sort if knowledgeBase is Guide + manual
            $maxArticlesSort = $this->sql()
                ->select('a.sort', 'MAX', 'maxSort')
                ->from('article a')
                ->where([
                    'a.knowledgeCategoryID' => $knowledgeCategoryID,
                    'a.status' => ArticleModel::STATUS_PUBLISHED,
                ])
                ->groupBy('a.knowledgeCategoryID')
                ->get()->nextRow(DATASET_TYPE_ARRAY)
            ;
        } else {
            $maxArticlesSort['maxSort'] = -1;
        }
        $sortInfo['maxSort'] = max(-1, $maxArticlesSort ? $maxArticlesSort['maxSort'] : false, $sortInfo['maxSort']);

        return $sortInfo;
    }

    /**
     * Shift all sort values higher than $idx
     * Up (when direction self::SORT_INCREMNENT)
     * or Down (when direction self::SORT_DECREMENT)
     *
     * @param int $knowledgeCategoryID
     * @param int $idx
     * @param int $protectedID
     * @param string $protectType
     * @param bool $direction
     * @return bool
     */
    public function shiftSorts(
        int $knowledgeCategoryID,
        int $idx,
        int $protectedID = -1,
        string $protectType = self::SORT_TYPE_ARTICLE,
        bool $direction = self::SORT_INCREMNENT
    ): bool {
        $knowledgeBase = $this->sql()
            ->select('kb.knowledgeBaseID, kb.viewType, kb.sortArticles')
            ->from('knowledgeCategory c')
            ->where([
                'c.knowledgeCategoryID' => $knowledgeCategoryID,
            ])
            ->leftJoin('knowledgeBase kb', 'kb.knowledgeBaseID = c.knowledgeBaseID')
            ->get()->nextRow(DATASET_TYPE_ARRAY)
        ;
        if ($knowledgeBase['viewType'] === KnowledgeBaseModel::TYPE_GUIDE) {
            $query = $this->sql()->update('article')
                ->set('sort' . ($direction ? '+' : '-'), 1)
                ->where(
                    [
                        'knowledgeCategoryID' => $knowledgeCategoryID,
                        'status' => ArticleModel::STATUS_PUBLISHED,
                        'sort >=' => $idx,
                    ]
                );

            if ($protectType === self::SORT_TYPE_ARTICLE) {
                $query->where('articleID <>', $protectedID);
            }

            $updateArticles = $query->put();
        } else {
            $updateArticles = true;
        }

        $query = $this->sql()->update('knowledgeCategory')
            ->set('sort' . ($direction ? '+' : '-'), 1)
            ->where(
                [
                    'parentID' => $knowledgeCategoryID,
                    'sort >=' => $idx,
                ]
            )
        ;

        if ($protectType === self::SORT_TYPE_CATEGORY) {
            $query->where('knowledgeCategoryID <>', $protectedID);
        }
        $updateCategories = $query->put();

        return ($updateArticles !== false && $updateCategories !== false);
    }
}
