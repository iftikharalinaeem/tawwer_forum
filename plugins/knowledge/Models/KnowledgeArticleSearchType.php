<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Garden\Schema\Schema;
use Garden\Web\Exception\HttpException;
use Vanilla\Knowledge\Controllers\Api\CheckGlobalPermissionTrait;
use Vanilla\Knowledge\Controllers\Api\KnowledgeApiController;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Search\AbstractSearchType;
use Vanilla\Search\SearchResultItem;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Search\SearchQuery;

/**
 * Search record type for a knowledge-article.
 */
class KnowledgeArticleSearchType extends AbstractSearchType {
    use CheckGlobalPermissionTrait;

    const SPHINX_DTYPE = 5;
    const TYPE_ARTICLE = 5;
    const TYPE_ARTICLE_DELETED = 6;

    const ARTICLE_STATUSES = [
        1 => ArticleModel::STATUS_PUBLISHED,
        2 => ArticleModel::STATUS_DELETED,
        3 => ArticleModel::STATUS_UNDELETED,
    ];

    /** @var ArticleModel */
    private $articleModel;

    /** @var KnowledgeCategoryModel */
    private $knowledgeCategoryModel;

    /** @var KnowledgeBaseModel $knowledgeBaseModel */
    private $knowledgeBaseModel;

    /** @var KnowledgeApiController $knowledgeApiController */
    private $knowledgeApiController;

    /** @var BreadcrumbModel */
    private $breadcrumbModel;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /** @var KnowledgeUniversalSourceModel */
    private $knowledgeUniversalSourceModel;

    /**
     * DI.
     *
     * @param ArticleModel $articleModel
     * @param KnowledgeCategoryModel $knowledgeCategoryModel
     * @param BreadcrumbModel $breadcrumbModel
     * @param SiteSectionModel $siteSectionModel
     * @param KnowledgeUniversalSourceModel $knowledgeUniversalSourceModel
     * @param KnowledgeBaseModel $knowledgeBaseModel
     * @param KnowledgeApiController $knowledgeApiController
     */
    public function __construct(
        ArticleModel $articleModel,
        KnowledgeCategoryModel $knowledgeCategoryModel,
        BreadcrumbModel $breadcrumbModel,
        SiteSectionModel $siteSectionModel,
        KnowledgeUniversalSourceModel $knowledgeUniversalSourceModel,
        KnowledgeBaseModel $knowledgeBaseModel,
        KnowledgeApiController $knowledgeApiController
    ) {
        $this->articleModel = $articleModel;
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
        $this->breadcrumbModel = $breadcrumbModel;
        $this->siteSectionModel = $siteSectionModel;
        $this->knowledgeUniversalSourceModel = $knowledgeUniversalSourceModel;
        $this->knowledgeBaseModel = $knowledgeBaseModel;
        $this->knowledgeApiController = $knowledgeApiController;
    }


    /**
     * @inheritdoc
     */
    public function getKey(): string {
        return 'article';
    }

    /**
     * @inheritdoc
     */
    public function getSearchGroup(): string {
        return 'article';
    }

    /**
     * @inheritdoc
     */
    public function getType(): string {
        return 'article';
    }

    /**
     * @inheritdoc
     */
    public function getResultItems(array $recordIDs): array {
        try {
            $results  = $this->knowledgeApiController->getArticlesAsDiscussions(
                $recordIDs,
                self::SPHINX_DTYPE
            );
            $resultItems = array_map(function ($result) {
                $mapped['recordID'] = $result['articleID'];
                $mapped['foreignID'] = $result['articleRevisionID'];
                $mapped['name'] = $result['name'];
                $mapped['url'] = $result['Url'];
                $mapped['dateInserted'] = $result['dateInserted'];
                $mapped['recordType'] = $this->getSearchGroup();
                $mapped['type'] = $this->getType();
                $mapped['breadcrumbs'] = $this->breadcrumbModel->getForRecord(new KbCategoryRecordType($result['knowledgeCategoryID']));
                return new SearchResultItem($mapped);
            }, $results);
            return $resultItems;
        } catch (HttpException $exception) {
            trigger_error($exception->getMessage(), E_USER_WARNING);
            return [];
        }
    }

    /**
     * @inheritdoc
     */
    public function applyToQuery(SearchQuery $query) {
        $knowledgeBaseID = $query->getQueryParameter('knowledgeBaseID');

        $knowledgeCategories = [];
        if ($knowledgeBaseID) {
            $knowledgeCategories = $this->getKnowledgeCategories($knowledgeBaseID);
        }

        if ($query->getQueryParameter('knowledgeCategoryIDs')) {
            $knowledgeCategories = array_intersect(
                $query->getQueryParameter('knowledgeCategoryIDs'),
                $knowledgeCategories
            );
        };

        $filteredKnowledgeCategoryIDs = $this->filterKnowledgeBases($knowledgeCategories);
        $query->setFilter('knowledgeCategoryID', $filteredKnowledgeCategoryIDs);

        $updatedUserIDs = $query->getQueryParameter('updatedUserIDs');
        if ($updatedUserIDs) {
            $query->setFilter('updatedUserIDs', $updatedUserIDs);
        }

        $statuses = $query->getQueryParameter('statuses');
        if ($statuses) {
            $statusesToApply = $this->getStatusFilters($statuses, [self::TYPE_ARTICLE]);
            $query->setFilter('status', $statusesToApply);
        }

        $locale = $query->getQueryParameter('locale');
        if ($locale) {
            $query->setFilterString('locale', $locale);
        }

        $siteSectionGroup = $query->getQueryParameter('siteSectionGroup');
        if ($siteSectionGroup) {
            $query->setFilterString('siteSectionGroup', $siteSectionGroup);
        }

        $featured = $query->getQueryParameter('featured');
        if ($featured) {
            $query->setFilter('featured', [1]);
        }
    }

    /**
     * @inheritdoc
     */
    public function getSorts(): array {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getQuerySchema(): Schema {
        return $this->schemaWithTypes(Schema::parse([
            "knowledgeBaseID:i?" => [
                'description' => 'Unique ID of a knowledge base. Results will be relative to this value.',
                'x-search-scope' => true,
            ],
            'knowledgeCategoryIDs:a?' => [
                'description' => 'Set the scope of the search to a specific category.',
                'x-search-scope' => true,
            ],
            'statuses:a?' => [
                'items' => ['type' => 'string'],
                'description' => 'Article statuses array to filter results.',
                'x-search-filter' => true,
            ],
            "locale:s?" => [
                'description' => 'The locale articles are published in.',
                'x-search-scope' => true
            ],
            'siteSectionGroup:s?' => [
                'description' => 'The site-section-group articles are associated to',
                'x-search-scope' => true
            ],
            "featured:b?" => [
                'description' => "Search for featured articles only. Default: false",
                'x-search-filter' => true,
            ],
        ]));
    }

    /**
     * @inheritdoc
     */
    public function validateQuery(SearchQuery $query): void {
        return;
    }

    /**
     * Get all the knowledge-categories for query
     *
     * @param int $id
     * @return array
     */
    protected function getKnowledgeCategories(int $id): array {
        $knowledgeUniversalContent = $this->knowledgeUniversalSourceModel->get(
            [
                "targetKnowledgeBaseID" => $id,
            ]
        );
        if ($knowledgeUniversalContent) {
            $knowledgeBaseIDs = array_column($knowledgeUniversalContent, "sourceKnowledgeBaseID");
        }
        $knowledgeBaseIDs[] = $id;
        $knowledgeCategories = array_column(
            $this->knowledgeCategoryModel->get(
                ['knowledgeBaseID' => $knowledgeBaseIDs],
                ['select' => ['knowledgeCategoryID']]
            ),
            'knowledgeCategoryID'
        );

        return $knowledgeCategories;
    }

    /**
     * Filter knowledge-base for query.
     *
     * @param array $knowledgeCategories
     * @return array
     */
    private function filterKnowledgeBases(array $knowledgeCategories = []): array {
        $kbs = $this->knowledgeBaseModel->updateKnowledgeIDsWithCustomPermission([]);
        $kbIDs = $kbs['knowledgeBaseID'] ?? [];
        $allKnowledgeCategories = array_column(
            $this->knowledgeCategoryModel->get(
                ['knowledgeBaseID' => $kbIDs],
                ['select' => ['knowledgeCategoryID']]
            ),
            'knowledgeCategoryID'
        );
        if (empty($knowledgeCategories)) {
            $filterIDs = $allKnowledgeCategories;
        } else {
            $filterIDs = array_intersect($knowledgeCategories, $allKnowledgeCategories);
        }
        return $filterIDs;
    }

    /**
     * Get the article status Filters.
     *
     * @param array $queryStatuses
     * @param array $articleIndexes
     *
     * @return array
     */
    protected function getStatusFilters(array $queryStatuses, array $articleIndexes = [5]): array {
        $searchDeleted = in_array(ArticleModel::STATUS_DELETED, $queryStatuses);
        if ($searchDeleted) {
            $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);
        };
        $articleIndexes[] = self::TYPE_ARTICLE_DELETED;
        $statuses = array_map(
            function ($status) {
                return array_search($status, self::ARTICLE_STATUSES);
            },
            $queryStatuses
        );

        return $statuses;
    }
}

