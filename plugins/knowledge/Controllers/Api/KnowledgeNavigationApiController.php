<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;
use Exception;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Exception\PermissionException;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Vanilla\Knowledge\Models\Navigation;
use Vanilla\Site\TranslationModel;
use Vanilla\Contracts\Site\TranslationProviderInterface;
use Vanilla\Knowledge\Models\DefaultArticleModel;

/**
 * Endpoint for the virtual "knowledge navigation" resource.
 */
class KnowledgeNavigationApiController extends AbstractApiController {
    use KnowledgeNavigationApiSchemes;

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

    /** @var TranslationProviderInterface $translation */
    private $translation;

    /** @var DefaultArticleModel $defaultArticleModel */
    private $defaultArticleModel;

    /**
     * KnowledgeNavigationApiController constructor.
     *
     * @param KnowledgeCategoryModel $knowledgeCategoryModel
     * @param ArticleModel $articleModel
     * @param KnowledgeBaseModel $knowledgeBaseModel
     * @param TranslationModel $translationModel
     */
    public function __construct(
        KnowledgeCategoryModel $knowledgeCategoryModel,
        ArticleModel $articleModel,
        KnowledgeBaseModel $knowledgeBaseModel,
        TranslationModel $translationModel,
        DefaultArticleModel $defaultArticleModel
    ) {
        $this->articleModel = $articleModel;
        $this->knowledgeBaseModel = $knowledgeBaseModel;
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
        $this->translation = $translationModel->getContentTranslationProvider();
        $this->defaultArticleModel = $defaultArticleModel;
    }


    /**
     * Get a navigation-friendly record hierarchy of categories and articles in flat mode.
     *
     * @param array $query Request query.
     *
     * @return array Navigation items, arranged hierarchically.
     */
    public function flat(array $query = []): array {
        return $this->knowledgeBaseNavigation($query["knowledgeBaseID"], true, $query["recordType"], $query);
    }

    /**
     * Get a navigation-friendly record hierarchy of categories and articles in tree mode.
     *
     * @param array $query Request query.
     *
     * @return array Navigation items, arranged hierarchically.
     */
    public function tree(array $query = []): array {
        return $this->knowledgeBaseNavigation($query["knowledgeBaseID"], false, $query["recordType"], $query);
    }

    /**
     * Get navigation items for a knowledge base.
     *
     * @param integer $knowledgeBaseID
     * @param boolean $flat
     * @param string $recordType
     * @param array $query Extra query prameters passed if any
     */
    private function knowledgeBaseNavigation(int $knowledgeBaseID, bool $flat, string $recordType = self::FILTER_RECORD_TYPE_ALL, array $query = []) {
        try {
            $knowledgeBase = $this->knowledgeBaseModel->selectSingle(["knowledgeBaseID" => $knowledgeBaseID]);
        } catch (\Vanilla\Exception\Database\NoResultsException $e) {
            throw new NotFoundException('Knowledge Base with ID: '.$knowledgeBaseID.' not found!');
        }

        $categories = $this->knowledgeCategoryModel->get(
            ["knowledgeBaseID" => $knowledgeBaseID],
            [
                'orderFields' => 'sort',
                'orderDirection' => 'asc'
            ]
        );

        [$options, $where] = $this->getOnlyTranslatedQueryParams($query, $knowledgeBase);

        $options["queryLocale"] = $query["locale"] ?? null;

        if ($recordType === self::FILTER_RECORD_TYPE_ALL) {
            $catIds = array_column($categories, 'knowledgeCategoryID');
            if ($knowledgeBase["viewType"] === KnowledgeBaseModel::TYPE_GUIDE) {
                $where = array_merge(
                    $where,
                    [
                        'a.knowledgeCategoryID' => $catIds,
                        'a.status' => ArticleModel::STATUS_PUBLISHED
                    ]
                );
                $options = array_merge(
                    $options,
                    [
                        "limit" => false,
                        "orderFields" => 'sort',
                        "orderDirection" => 'asc',
                    ]
                );

                $articles = $this->articleModel->getExtended(
                    $where,
                    $options,
                    ['recordType' => Navigation::RECORD_TYPE_ARTICLE]
                );
            } else {
                list($orderField, $orderDirection) = $this->knowledgeBaseModel->articleSortConfig($knowledgeBase["sortArticles"]);

                $options = array_merge(
                    $options,
                    [
                        "limit" => self::HELP_CENTER_DEFAULT_ARTICLES_LIMIT,
                        "orderFields" => $orderField,
                        "orderDirection" => $orderDirection,
                    ]
                );

                $articles = $this->articleModel->getTopPerCategory(
                    $catIds,
                    $where,
                    $options
                );
            }
        } else {
            $articles = [];
        }

        if (!empty($options['queryLocale'])) {
            foreach ($articles as &$article) {
                $article['queryLocale'] = $options['queryLocale'];
            }
            foreach ($categories as &$category) {
                $category['locale'] = $options['queryLocale'];
            }
        }

        $result = $this->getNavigation($categories, $articles, $flat, KnowledgeCategoryModel::ROOT_ID, $knowledgeBase["sortArticles"]);
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
     * @param string $locale Locale
     *
     * @return array
     */
    private function getNavigation(
        array $categories,
        array $articles,
        bool $flatMode = true,
        int $rootCategoryID = KnowledgeCategoryModel::ROOT_ID,
        string $sortOrder = KnowledgeBaseModel::ORDER_MANUAL,
        string $locale = null
    ): array {
        $categories = $this->normalizeOutput($categories, Navigation::RECORD_TYPE_CATEGORY);
        $articles = $this->normalizeOutput($articles, Navigation::RECORD_TYPE_ARTICLE);

        if ($flatMode) {
            $all = array_merge($categories, $articles);
            if ($sortOrder === KnowledgeBaseModel::ORDER_MANUAL) {
                usort($all, function ($prev, $next) {
                    if ($prev['sort'] === $next['sort']) {
                        return $prev['name'] <=> $next['name'];
                    } else {
                        return ($prev['sort'] < $next['sort']) ? -1 : 1;
                    }
                });
            }
            return $all;
        } else {
            return $this->makeNavigationTree($rootCategoryID, $categories, $articles);
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
            if (isset($list[$l['knowledgeCategoryID']]) && $l['recordType'] === Navigation::RECORD_TYPE_CATEGORY) {
                $l['children'] = $this->createTree($list, $list[$l['recordID']]);
                usort($l["children"], [Navigation::class, "compareItems"]);
            }
            $tree[] = $l;
        }
        return $tree;
    }

    /**
     * Get a single knowledge base by its ID.
     *
     * @param int $knowledgeBaseID
     * @return array
     * @throws NotFoundException If the knowledge base could not be found.
     */
    public function knowledgeBaseByID(int $knowledgeBaseID): array {
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
    private function normalizeOutput(array $rows, string $recordType = Navigation::RECORD_TYPE_CATEGORY): array {
        if (!is_null($this->translation)
            && $recordType === Navigation::RECORD_TYPE_CATEGORY
            && !empty($rows[0]['locale'] ?? null)) {
            $rows = $this->translation->translateProperties(
                $rows[0]['locale'],
                'kb',
                Navigation::RECORD_TYPE_CATEGORY,
                'knowledgeCategoryID',
                $rows,
                ['name']
            );
        }
        foreach ($rows as &$row) {
            if ($recordType === Navigation::RECORD_TYPE_CATEGORY) {
                $row["recordID"] = $row["knowledgeCategoryID"];
                $row["recordType"] = Navigation::RECORD_TYPE_CATEGORY;
                $row["url"] = $this->knowledgeCategoryModel->url($row);
            } elseif ($recordType === Navigation::RECORD_TYPE_ARTICLE) {
                $row["recordType"] = Navigation::RECORD_TYPE_ARTICLE;
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
        Navigation::updateAlteredRows(
            $categories,
            $navigation,
            Navigation::RECORD_TYPE_CATEGORY,
            "knowledgeCategoryID",
            "parentID",
            $this->knowledgeCategoryModel
        );

        // Update articles.
        $articles = $this->articleModel->get(
            ["knowledgeCategoryID" => array_column($categories, "knowledgeCategoryID")],
            ["limit" => false]
        );
        Navigation::updateAlteredRows(
            $articles,
            $navigation,
            Navigation::RECORD_TYPE_ARTICLE,
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
        $navigation = $this->knowledgeBaseNavigation($id, true);

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
        $tree = $this->knowledgeBaseNavigation($knowledgeBaseID, false, self::FILTER_RECORD_TYPE_ALL);
        return $this->getArticleID($tree);
    }

    /**
     * Recursive scan of the navigation tree to find first RECORD_TYPE_ARTICLE
     *
     * @param array $tree
     * @return int|null
     */
    public function getArticleID(array $tree) {
        foreach ($tree as $branch) {
            if ($branch['recordType'] === Navigation::RECORD_TYPE_ARTICLE) {
                return $branch['recordID'];
            } elseif (isset($branch['children']) && !empty($branch['children'])) {
                $articleID = $this->getArticleID($branch['children']);
                if (null !== $articleID) {
                    return $articleID;
                }
            }
        }
        return null;
    }

    /**
     * Get the query options and where clauses when Only-Translated parameters is passed.
     *
     * @param array $query
     * @param array $knowledgeBase
     *
     * @return array
     */
    private function getOnlyTranslatedQueryParams(array $query, array $knowledgeBase): array {
        $options = [];
        $where = [];

        $options['only-translated'] = (isset($query['only-translated'])) ? $query['only-translated'] : false;

        if ($options['only-translated']) {
            $where['ar.locale'] = $query['locale'] ?? $knowledgeBase['sourceLocale'];
        } else {
            $where['ar.locale'] = $knowledgeBase['sourceLocale'];
            if (!empty($query['locale'])) {
                $options['arl.locale'] = $query['locale'];
            }
        }

        return array($options, $where);
    }
}
