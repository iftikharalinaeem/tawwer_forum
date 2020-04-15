<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Exception;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\CacheInterface;
use Vanilla\Contracts\Site\TranslationProviderInterface;
use Vanilla\Exception\PermissionException;
use Vanilla\Knowledge\Controllers\Api\KnowledgeNavigationApiSchemes;
use Vanilla\Models\Model;
use Vanilla\Site\TranslationModel;

/**
 * Utility class for handling navigation data.
 */
class KnowledgeNavigationModel {

    use KnowledgeNavigationApiSchemes;

    // Record type for knowledge categories.
    const RECORD_TYPE_CATEGORY = "knowledgeCategory";

    // Record type for articles.
    const RECORD_TYPE_ARTICLE = "article";

    /** Filter value for limiting results to only knowledge categories. */
    const FILTER_RECORD_TYPE_CATEGORY = "knowledgeCategory";

    /** Filter value for allowing all record types. */
    const FILTER_RECORD_TYPE_ALL = "all";

    /** Default limit on the number of articles returned for a help center category. */
    const HELP_CENTER_DEFAULT_ARTICLES_LIMIT = 5;

    /** @var ArticleModel */
    private $articleModel;

    /** @var KnowledgeBaseModel */
    private $knowledgeBaseModel;

    /** @var KnowledgeCategoryModel */
    private $knowledgeCategoryModel;

    /** @var TranslationProviderInterface */
    private $translation;

    /** @var DefaultArticleModel */
    private $defaultArticleModel;

    /** @var KnowledgeNavigationCache */
    private $navCache;

    /**
     * Constructor.
     *
     * @param KnowledgeCategoryModel $knowledgeCategoryModel
     * @param ArticleModel $articleModel
     * @param KnowledgeBaseModel $knowledgeBaseModel
     * @param TranslationModel $translationModel
     * @param DefaultArticleModel $defaultArticleModel
     * @param KnowledgeNavigationCache $navCache
     */
    public function __construct(
        KnowledgeCategoryModel $knowledgeCategoryModel,
        ArticleModel $articleModel,
        KnowledgeBaseModel $knowledgeBaseModel,
        TranslationModel $translationModel,
        DefaultArticleModel $defaultArticleModel,
        KnowledgeNavigationCache $navCache
    ) {
        $this->articleModel = $articleModel;
        $this->knowledgeBaseModel = $knowledgeBaseModel;
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
        $this->translation = $translationModel->getContentTranslationProvider();
        $this->defaultArticleModel = $defaultArticleModel;
        $this->navCache = $navCache;
    }

    /**
     * Build a navigation structure for some parameters.
     *
     * - Do permission checking before checking calling this function.
     * - Results are cached.
     *
     * @param KnowledgeNavigationQuery $query
     * @return array
     */
    public function buildNavigation(KnowledgeNavigationQuery $query): array {
        $cachedResult = $this->navCache->get($query);

        if ($cachedResult !== null) {
            return [
                'cached' => true,
                'result' => $cachedResult,
            ];
        }

        $navigation = $this->buildNavigationInternal($query);
        $this->navCache->set($query, $navigation);
        return [
            'cached' => false,
            'result' => $navigation,
        ];
    }

    /**
     * Build a navigation structure for some parameters.
     *
     * - Do permission checking before checking calling this function.
     * - Results are cached.
     *
     * @param KnowledgeNavigationQuery $query
     *
     * @return array
     */
    private function buildNavigationInternal(KnowledgeNavigationQuery $query): array {
        $knowledgeBase = $this->knowledgeBaseByID($query->getKnowledgeBaseID());

        $dbQueryOptions = [
            'only-translated' => $query->isOnlyTranslated(),
        ];
        $dbWhere = [];
        $queryLocale = $query->getLocale() ?? $knowledgeBase['sourceLocale'];
        if ($query->isOnlyTranslated()) {
            $dbWhere['ar.locale'] = $queryLocale;
        } else {
            $dbWhere['ar.locale'] = $queryLocale;
            if (!empty($query->getLocale())) {
                $dbQueryOptions['arl.locale'] = $query->getLocale();
            }
        }

        $dbQueryOptions["queryLocale"] = $queryLocale;

        $categories = $this->knowledgeCategoryModel->get(
            [ "knowledgeBaseID" => $query->getKnowledgeBaseID() ],
            [
                'orderFields' => 'sort',
                'orderDirection' => 'asc'
            ]
        );
        if ($knowledgeBase['viewType'] === KnowledgeBaseModel::TYPE_GUIDE) {
            $categoryIDs = array_column($categories, 'knowledgeCategoryID');
            // Guides get articles included.
            $dbWhere = array_merge(
                $dbWhere,
                [
                    'a.knowledgeCategoryID' => $categoryIDs,
                    'a.status' => ArticleModel::STATUS_PUBLISHED
                ]
            );
            $dbQueryOptions = array_merge(
                $dbQueryOptions,
                [
                    "limit" => false,
                    "orderFields" => 'sort',
                    "orderDirection" => 'asc',
                ]
            );

            $articles = $this->articleModel->getExtended(
                $dbWhere,
                $dbQueryOptions,
                ['recordType' => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE]
            );
        } else {
            [$orderField, $orderDirection] = $this->knowledgeBaseModel->articleSortConfig($knowledgeBase["sortArticles"]);

            $dbQueryOptions = array_merge(
                $dbQueryOptions,
                [
                    "limit" => self::HELP_CENTER_DEFAULT_ARTICLES_LIMIT,
                    "orderFields" => $orderField,
                    "orderDirection" => $orderDirection,
                ]
            );
            
            // We have all the categories. We need to group them by common depth-1 categories.
            $articles = $this->articleModel->getTopPerCategory(
                $categories,
                $dbWhere,
                $dbQueryOptions
            );
        }

        // Fixup locale fields.
        if (!empty($dbQueryOptions['queryLocale'])) {
            foreach ($articles as &$article) {
                $article['queryLocale'] = $dbQueryOptions['queryLocale'];
            }
            foreach ($categories as &$category) {
                $category['locale'] = $dbQueryOptions['queryLocale'];
            }
        }

        $result = $this->normalizeNavigation(
            $categories,
            $articles,
            $query->isFlat(),
            KnowledgeCategoryModel::ROOT_ID,
            $knowledgeBase["sortArticles"]
        );
        return $result;
    }

    /**
     * Return navigation array of a section
     *
     * @param array $categories
     * @param array $articles
     * @param bool $flatMode Mode: flat or tree
     * @param int $rootCategoryID Category ID to start from
     * @param string $sortOrder Knowledge base sort order settings
     *
     * @return array
     */
    private function normalizeNavigation(
        array $categories,
        array $articles,
        bool $flatMode = true,
        int $rootCategoryID = KnowledgeCategoryModel::ROOT_ID,
        string $sortOrder = KnowledgeBaseModel::ORDER_MANUAL
    ): array {
        $categories = $this->normalizeOutput($categories, KnowledgeNavigationModel::RECORD_TYPE_CATEGORY);
        $articles = $this->normalizeOutput($articles, KnowledgeNavigationModel::RECORD_TYPE_ARTICLE);

        if ($flatMode) {
            $result = array_merge($categories, $articles);
            if ($sortOrder === KnowledgeBaseModel::ORDER_MANUAL) {
                usort($result, function ($prev, $next) {
                    if ($prev['sort'] === $next['sort']) {
                        return $prev['name'] <=> $next['name'];
                    } else {
                        return ($prev['sort'] < $next['sort']) ? -1 : 1;
                    }
                });
            }
            $schema = Schema::parse([":a" => $this->categoryNavigationFragment()]);
            $result = $schema->validate($result);
            return $result;
        } else {
            $result = $this->makeNavigationTree($rootCategoryID, $categories, $articles);
            $schema = Schema::parse([":a" => $this->schemaWithChildren()]);
            $result = $schema->validate($result);
            return $result;
        }
    }

    /**
     * Transform flat array into tree array when tree mode is required
     *
     * @param int $parentID Top level category ID
     * @param array $categories List of categories in section
     * @param array $articles List of articles in section
     * @return array
     */
    private function makeNavigationTree(int $parentID, array $categories, array $articles): array {
        $parentsIndex = [];
        foreach ($categories as $c) {
            $parentsIndex[$c['parentID']][] = $c;
        }
        foreach ($articles as $a) {
            $parentsIndex[$a['knowledgeCategoryID']][] = $a;
        }
        $result = $this->createTree($parentsIndex, $parentsIndex[$parentID]);

        return $result;
    }

    /**
     * Transforms flat array into tree array
     *
     * @param array $list Initial array to be transformed
     * @param array $parent Parent element (root)
     *
     * @return array Transformed tree array
     */
    private function createTree(array $list, array $parent): array {
        $tree = [];
        foreach ($parent as $k => $l) {
            if (isset($list[$l['knowledgeCategoryID']]) && $l['recordType'] === KnowledgeNavigationModel::RECORD_TYPE_CATEGORY) {
                $l['children'] = $this->createTree($list, $list[$l['recordID']]);
                usort($l["children"], [$this, "compareItems"]);
            }
            $tree[] = $l;
        }
        return $tree;
    }

    /**
     * Given two navigation items, compare them and determine their sort order.
     *
     * @param array $a
     * @param array $b
     * @return int A value of -1, 0 or 1, depending on if $a is less than, equal to, or greater than $b.
     */
    private function compareItems(array $a, array $b): int {
        $sortA = $a["sort"] ?? null;
        $sortB = $b["sort"] ?? null;
        if ($sortA === $sortB) {
            // Same sort weight? We must go deeper.
            $typeA = $a["recordType"] ?? null;
            $typeB = $b["recordType"] ?? null;
            if ($typeA === $typeB) {
                // Same record type? Sort by name.
                $nameA = $a["name"] ?? null;
                $nameB = $b["name"] ?? null;
                return $nameA <=> $nameB;
            }
            // Articles rank lower than categories.
            return $typeA === KnowledgeNavigationModel::RECORD_TYPE_ARTICLE ? 1 : -1;
        } elseif ($sortA === null) {
            // If they're not the same, and A is null, then B must not be null. B should rank higher.
            return 1;
        } elseif ($sortB === null) {
            // If they're not the same, and B is null, then A must not be null. A should rank higher.
            return -1;
        } else {
            // We have two non-null, non-equal sort weights. Compare them using the combined-comparison operator.
            return $sortA <=> $sortB;
        }
    }

    /**
     * Sync resource rows with a navigation structure.
     *
     * @param array $rows All rows of a particular resource that chould be represented in the tree (i.e. everything in a knowledge base).
     * @param array $navigation Flat list of navigation items.
     * @param string $type Type of resource being synchronized (e.g. article, knowlegeCategory).
     * @param string $idField Unique ID field of the resource being processed (e.g. articleID, knowledgeCategoryID).
     * @param string $parentField Field used to determine organization of the resource (e.g. knowledgeCategoryID, parentID).
     * @param Model $model Database model for performing the resource row updates.
     * @throws Exception If an error is encountered while performing an update query.
     */
    private function modifyAlteredRows(
        array $rows,
        array $navigation,
        string $type,
        string $idField,
        string $parentField,
        Model $model
    ) {
        if ($navigation[0] ?? false) {
            throw new Exception("Navigation array not properly indexed.");
        }

        foreach ($rows as $row) {
            $key = $type."-".$row[$idField];
            $navItem = $navigation[$key] ?? null;
            if ($navItem === null) {
                $model->update(
                    ["sort" => null],
                    [$idField => $row[$idField]]
                );
            } elseif ($navItem["sort"] !== $row["sort"] || $navItem["parentID"] !== $row[$parentField]) {
                $model->update(
                    [
                        "sort" => $navItem["sort"],
                        $parentField => $navItem["parentID"],
                    ],
                    [$idField => $row[$idField]]
                );
            }
        }
    }

    /**
     * Get a single knowledge base by its ID.
     *
     * @param int $knowledgeBaseID
     * @return array
     * @throws NotFoundException If the knowledge base could not be found.
     */
    private function knowledgeBaseByID(int $knowledgeBaseID): array {
        try {
            $result = $this->knowledgeBaseModel->selectSingle(["knowledgeBaseID" => $knowledgeBaseID]);
        } catch (\Vanilla\Exception\Database\NoResultsException $e) {
            throw new NotFoundException('Knowledge Base with ID: ' . $knowledgeBaseID . ' not found!');
        }

        return $result;
    }

    /**
     * Massage tree data for useful API output.
     *
     * @param array $rows
     * @param string $recordType Record type: RECORD_TYPE_CATEGORY || RECORD_TYPE_ARTICLE
     * @return array
     * @throws \Exception If $row is not a valid knowledge category.
     */
    private function normalizeOutput(array $rows, string $recordType = KnowledgeNavigationModel::RECORD_TYPE_CATEGORY): array {
        if (!is_null($this->translation)
            && $recordType === KnowledgeNavigationModel::RECORD_TYPE_CATEGORY
            && !empty($rows[0]['locale'] ?? null)) {
            $rows = $this->translation->translateProperties(
                $rows[0]['locale'],
                'kb',
                KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                'knowledgeCategoryID',
                $rows,
                ['name']
            );
        }
        foreach ($rows as &$row) {
            if ($recordType === KnowledgeNavigationModel::RECORD_TYPE_CATEGORY) {
                $row["recordID"] = $row["knowledgeCategoryID"];
                $row["recordType"] = KnowledgeNavigationModel::RECORD_TYPE_CATEGORY;
                $row["url"] = $this->knowledgeCategoryModel->url($row);
            } elseif ($recordType === KnowledgeNavigationModel::RECORD_TYPE_ARTICLE) {
                $row["recordType"] = KnowledgeNavigationModel::RECORD_TYPE_ARTICLE;
                $row["parentID"] = $row["knowledgeCategoryID"];
                $row["recordID"] = $row["articleID"];
                $row["url"] = $this->articleModel->url($row);
            }
            if (!empty($row["children"])) {
                $row["children"] = $this->normalizeOutput($row["children"]);
            }
        }

        return $rows;
    }

    /**
     * Update the navigation structure of a knowledge base, using the flat format.
     *
     * @param int $id The knowledge base ID
     * @param array $body Request body.
     * @return array Navigation items, arranged in a one-dimensional array.
     * @throws Exception If no session is available.
     * @throws HttpException If a relevant ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have adequate permission(s).
     * @throws ValidationException Throws an exception when input does not validate against the input schema.
     * @throws ValidationException Throws an exception when output does not validate against the output schema.
     */
    public function patchFlat(int $id, array $body = []): array {
        $knowledgeBase = $this->knowledgeBaseByID($id);

        // Add a basic index.
        $navigation = [];
        foreach ($body as $item) {
            $key = $item["recordType"]."-".$item["recordID"];
            $navigation[$key] = $item;
        }

        // Update categories.
        $categories = $this->knowledgeCategoryModel->get(["knowledgeBaseID" => $id]);
        KnowledgeNavigationModel::modifyAlteredRows(
            $categories,
            $navigation,
            KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
            "knowledgeCategoryID",
            "parentID",
            $this->knowledgeCategoryModel
        );

        // Update articles.
        $articles = $this->articleModel->get(
            ["knowledgeCategoryID" => array_column($categories, "knowledgeCategoryID")],
            ["limit" => false]
        );
        KnowledgeNavigationModel::modifyAlteredRows(
            $articles,
            $navigation,
            KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
            "articleID",
            "knowledgeCategoryID",
            $this->articleModel
        );

        if ($knowledgeBase['viewType'] === KnowledgeBaseModel::TYPE_GUIDE) {
            $defaultArticleID = $this->getDefaultArticleID($id);
            $this->defaultArticleModel->update(['defaultArticleID' => $defaultArticleID], ['knowledgeBaseID' => $id]);
        }

        // Dumb update all categories until we get knowledge base support to locate the proper top-level category.
        foreach ($categories as $category) {
            $parentID = $category["parentID"] ?? null;
            if ($parentID === -1) {
                $this->knowledgeCategoryModel->resetAllCounts($category["knowledgeBaseID"]);
                break;
            }
        }

        // Grab the new navigation state.
        $navigation = $this->buildNavigationInternal(new KnowledgeNavigationQuery(
            $id,
            null, // In the future we should be getting this from a query param.
            true
        ));

        return $navigation;
    }

    /**
     * Get default article id of knowledge base.
     * Note: Should be called only when knowledge base is GUIDE.
     *
     * @param int $knowledgeBaseID
     * @return int|null Returns int articleID when knowledge base is in GUIDE mode and has any article.
     *                  In any other cases returns null
     */
    public function getDefaultArticleID(int $knowledgeBaseID) {
        $tree = $this->buildNavigation(new KnowledgeNavigationQuery($knowledgeBaseID, null, false))['result'];
        return $this->getArticleIDFromTree($tree);
    }

    /**
     * Recursive scan of the navigation tree to find first RECORD_TYPE_ARTICLE
     *
     * @param array $tree
     * @return int|null
     */
    private function getArticleIDFromTree(array $tree) {
        foreach ($tree as $branch) {
            if ($branch['recordType'] === KnowledgeNavigationModel::RECORD_TYPE_ARTICLE) {
                return $branch['recordID'];
            } elseif (isset($branch['children']) && !empty($branch['children'])) {
                $articleID = $this->getArticleIDFromTree($branch['children']);
                if (null !== $articleID) {
                    return $articleID;
                }
            }
        }
        return null;
    }
}
