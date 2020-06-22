<?php


namespace Vanilla\Knowledge\Models;


use DiscussionModel;
use Garden\Schema\Schema;
use Gdn_Session;
use Vanilla\Adapters\SphinxClient as SphinxAdapter;
use Vanilla\DateFilterSchema;
use Vanilla\DateFilterSphinxSchema;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Search\AbstractSearchType;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Search\SearchQuery;

/**
 * Search record type for a discussion.
 */
class KnowledgeArticleSearchType extends AbstractSearchType {

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
        Gdn_Session $session
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

    }

    /**
     * @inheritdoc
     */
    public function applyToQuery(SearchQuery $query) {
        $knowledgeBaseID  = $query->getQueryParameter('knowledgeBaseID');

        $knowledgeCategories = [];
        if($knowledgeBaseID) {
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
        } else {
            $query->setFilter('status', [ArticleModel::STATUS_PUBLISHED]);
        }

        $locale = $query->getQueryParameter('locale');
        if ($locale) {
            $query->setFilter('locale', $locale);
        } else {
            $siteSection = $this->siteSectionModel->getCurrentSiteSection();
            $query->setFilter('locale', [$siteSection->getContentLocale()]);
        }

        $siteSectionGroup = $query->getQueryParameter('siteSectionGroup');
        if ($siteSectionGroup) {
            $query->setFilter('siteSectionGroup', $siteSectionGroup);
        }
        $featured = $query->getQueryParameter('featured');
        if ($featured) {
            $this->sphinx->setFilter('featured', [1]);
            $this->sphinx->setSortMode(SphinxAdapter::SORT_ATTR_DESC, 'dateFeatured');
        }


//        if ($query->getQueryParameter('dateUpdated')) {
//            $range = DateFilterSphinxSchema::dateFilterRange($this->query['dateUpdated']);
//            $range['startDate'] = $range['startDate'] ?? (new \DateTime())->setDate(1970, 1, 1)->setTime(0, 0, 0);
//            $range['endDate'] = $range['endDate'] ?? (new \DateTime())->setDate(2100, 12, 31)->setTime(0, 0, 0);
//            $query->setFilterRange('dateUpdated', $range['startDate']->getTimestamp(), $range['endDate']->getTimestamp());
//        }

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

