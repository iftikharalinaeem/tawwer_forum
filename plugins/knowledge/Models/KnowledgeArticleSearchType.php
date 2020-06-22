<?php


namespace Vanilla\Knowledge\Models;


use DiscussionModel;
use Garden\Schema\Schema;
use Garden\Web\Exception\HttpException;
use Gdn_Session;
use Vanilla\Adapters\SphinxClient as SphinxAdapter;
use Vanilla\DateFilterSchema;
use Vanilla\DateFilterSphinxSchema;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Knowledge\Controllers\Api\ArticlesApiController;
use Vanilla\Knowledge\Controllers\Api\KnowledgeApiController;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Search\AbstractSearchType;
use Vanilla\Search\SearchResultItem;
use Vanilla\Search\SearchResults;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Search\SearchQuery;
use Vanilla\Utility\ArrayUtils;

/**
 * Search record type for a discussion.
 */
class KnowledgeArticleSearchType extends AbstractSearchType {

    const SPHINX_DTYPE = 5;

    /** @var Schema */
    private $searchResultSchema;

    /** @var array */
    private $results = [];

    /** @var array */
    private $query = [];

    /** @var SphinxAdapter */
    private $sphinx = null;

    /** @var string */
    private $sphinxQuery = '';

    /** @var string */
    private $sphinxIndexes = '';

    /** @var array */
    private $sphinxIndexWeights = [];

    /** @var ArticleModel */
    private $articleModel;

    /** @var \UserModel */
    private $userModel;

    /** @var KnowledgeCategoryModel */
    private $knowledgeCategoryModel;

    /** @var KnowledgeBaseModel $knowledgeBaseModel */
    private $knowledgeBaseModel;

    /** @var KnowledgeApiController $knowledgeApiController */
    private $knowledgeApiController;

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var CommentModel */
    private $commentModel;

    /** @var \CategoryCollection */
    private $categoryCollection;

    /** @var BreadcrumbModel */
    private $breadcrumbModel;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /** @var KnowledgeUniversalSourceModel */
    private $knowledgeUniversalSourceModel;

    /** @var Gdn_Session $session */
    private $session;

    /** @var array $knowledgeCategories */
    private $knowledgeCategories = null;

    /**
     * DI.
     *
     * @param ArticleModel $articleModel
     * @param \UserModel $userModel
     * @param KnowledgeCategoryModel $knowledgeCategoryModel
     * @param DiscussionModel $discussionModel
     * @param CommentModel $commentModel
     * @param \CategoryCollection $categoryCollection
     * @param BreadcrumbModel $breadcrumbModel
     * @param SiteSectionModel $siteSectionModel
     * @param KnowledgeUniversalSourceModel $knowledgeUniversalSourceModel
     * @param KnowledgeBaseModel $knowledgeBaseModel
     * @param Gdn_Session $session
     * @param KnowledgeApiController $knowledgeApiController
     */
    public function __construct(
        ArticleModel $articleModel,
        \UserModel $userModel,
        KnowledgeCategoryModel $knowledgeCategoryModel,
        DiscussionModel $discussionModel,
        \CommentModel $commentModel,
        \CategoryCollection $categoryCollection,
        BreadcrumbModel $breadcrumbModel,
        SiteSectionModel $siteSectionModel,
        KnowledgeUniversalSourceModel $knowledgeUniversalSourceModel,
        KnowledgeBaseModel $knowledgeBaseModel,
        Gdn_Session $session,
        KnowledgeApiController $knowledgeApiController
    ) {
        $this->articleModel = $articleModel;
        $this->userModel = $userModel;
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
        $this->discussionModel = $discussionModel;
        $this->commentModel = $commentModel;
        $this->categoryCollection = $categoryCollection;
        $this->breadcrumbModel = $breadcrumbModel;
        $this->siteSectionModel = $siteSectionModel;
        $this->knowledgeUniversalSourceModel = $knowledgeUniversalSourceModel;
        $this->knowledgeBaseModel = $knowledgeBaseModel;
        $this->session = $session;
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
                $recordIDs, self::SPHINX_DTYPE
            );
            $resultItems = array_map(function ($result) {
                $mapped = ArrayUtils::remapProperties($result, [
                    'recordID' => 'articleID',
                ]);
                $mapped['recordType'] = $this->getSearchGroup();
                $mapped['type'] = $this->getType();
                $mapped['breadcrumbs'] = $this->breadcrumbModel->getForRecord(new KbCategoryRecordType($mapped['knowledgeCategoryID']));
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
            $this->sphinx->setFilter('featured', [1]);
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
            'dateUpdated?' =>new DateFilterSchema([
                'description' => 'When the article was updated.',
                'x-filter' => [
                    'field' => 'dateUpdated',
                    'processor' => [DateFilterSchema::class, 'dateFilterField'],
                ],
            ]),
            "statuses:a?" => "Article statuses array to filter results.",
            "locale:s?" => "The locale articles are published in",
            "siteSectionGroup:s?" => "The site-section-group articles are associated to",
            "featured:b?" => "Search for featured articles only. Default: false",
            "sort:s?" => [
                "description" => "Sort option to order search results.",
                "enum" => [
                    "name",
                    "-name",
                    "dateInserted",
                    "-dateInserted",
                    "dateFeatured",
                    "-dateFeatured",
                ]
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

