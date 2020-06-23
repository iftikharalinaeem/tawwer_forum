<?php


namespace Vanilla\Knowledge\Models;


use Garden\Schema\Schema;
use Garden\Web\Exception\HttpException;
use Vanilla\DateFilterSchema;
use Vanilla\Knowledge\Controllers\Api\KnowledgeApiController;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Search\AbstractSearchType;
use Vanilla\Search\SearchResultItem;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Search\SearchQuery;


/**
 * Search record type for a discussion.
 */
class KnowledgeArticleSearchType extends AbstractSearchType {

    const SPHINX_DTYPE = 5;

    /** @var Schema */
    private $searchResultSchema;

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
            $results  = $this->knowledgeApiController->getArticles(
                $recordIDs, self::SPHINX_DTYPE
            );
            $resultItems = array_map(function ($result) {
                $mapped['recordID'] = $result['articleID'];
                $mapped['foreignID'] = $result['articleRevisionID'];
                $mapped['name'] = $result['name'];
                $mapped['url'] = $result['url'];
                $mapped['dateInserted'] = $result['dateInserted'];
                $mapped['recordType'] = $this->getSearchGroup();
                $mapped['type'] = $this->getType();
                $mapped['breadcrumbs'] = $this->breadcrumbModel->getForRecord(new KbCategoryRecordType($result['knowledgeCategoryID']));
                return new SearchResultItem($mapped);
            }, $results);

            return $resultItems;
        }  catch (HttpException $exception) {
            trigger_error($exception->getMessage(), E_USER_WARNING);
            return [];
        }

    }

    /**
     * @inheritdoc
     */
    public function applyToQuery(SearchQuery $query) {
        $knowledgeBaseID  = $query->getQueryParameter('knowledgeBaseID');

        $knowledgeCategories = [];
        if ($knowledgeBaseID) {
            $knowledgeCategories = $this->getKnowledgeCategories($knowledgeBaseID);
        }

        if ($query->getQueryParameter('knowledgeCategoryIDs')) {
            $knowledgeCategories = array_intersect($query->getQueryParameter('knowledgeCategoryIDs'), $knowledgeCategories);
        };

        $filteredKnowledgeCategoryIDs = $this->filterKnowledgeBases($knowledgeCategories);
        $query->setFilter('knowledgeCategoryID', $filteredKnowledgeCategoryIDs);

        $updatedUserIDs = $query->getQueryParameter('updatedUserIDs');
        if ($updatedUserIDs) {
            $query->setFilter('updatedUserIDs', $updatedUserIDs);
        }

        $statuses = $query->getQueryParameter('statuses');
        if ($statuses) {
            $query->setFilter('status', $statuses);
        }

        $locale = $query->getQueryParameter('locale');
        if ($locale) {
            $query->setFilter('locale', $locale);
        }

        $siteSectionGroup = $query->getQueryParameter('siteSectionGroup');
        if ($siteSectionGroup) {
            $query->setFilter('siteSectionGroup', $siteSectionGroup);
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
            "knowledgeBaseID:i?" => "Unique ID of a knowledge base. Results will be relative to this value.",
            "knowledgeCategoryIDs:a?" => "Knowledge category ID to filter results.",
            "updateUserIDs:a?" => "Array of updateUserIDs (last editors of an article) to filter results.",
            "statuses:a?" => "Article statuses array to filter results.",
            "locale:s?" => "The locale articles are published in",
            "siteSectionGroup:s?" => "The site-section-group articles are associated to",
            "featured:b?" => "Search for featured articles only. Default: false",
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
     * @param array $knowledgeCategories
     * @return array
     */
    private function filterKnowledgeBases(array $knowledgeCategories = []): array {
        $kbs = $this->knowledgeBaseModel->updateKnowledgeIDsWithCustomPermission([]);
        $allKnowledgeCategories = array_column(
            $this->knowledgeCategoryModel->get(
                ['knowledgeBaseID' => $kbs['knowledgeBaseID']],
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

}

