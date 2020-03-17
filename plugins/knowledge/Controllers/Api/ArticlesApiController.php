<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use Garden\EventManager;
use Garden\Web\Exception\ClientException;
use Gdn_Session as SessionInterface;
use MediaModel;
use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use UserModel;
use Vanilla\Exception\PermissionException;
use Vanilla\Formatting\ExtendedContentFormatService;
use Vanilla\Formatting\FormatCompatTrait;
use Vanilla\Knowledge\Controllers\Api\KnowledgeNavigationApiController;
use Vanilla\Knowledge\Models\ArticleFeaturedModel;
use Vanilla\Knowledge\Models\KbCategoryRecordType;
use Vanilla\Knowledge\Models\ArticleReactionModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Models\DraftModel;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;
use Vanilla\Formatting\Quill\Parser;
use Vanilla\Formatting\UpdateMediaTrait;
use Vanilla\Formatting\FormatService;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Models\ReactionModel;
use Vanilla\Models\ReactionOwnerModel;
use DiscussionsApiController;
use Vanilla\Knowledge\Models\DiscussionArticleModel;
use Garden\Web\Data;
use Vanilla\Knowledge\Models\PageRouteAliasModel;
use Vanilla\Knowledge\Models\DefaultArticleModel;

/**
 * API controller for managing the articles resource.
 */
class ArticlesApiController extends AbstractKnowledgeApiController {

    use ArticlesApiSchemes;

    use UpdateMediaTrait;

    use FormatCompatTrait;

    use ArticlesApiHelper;
    use ArticlesApiDrafts;
    use ArticlesApiMigration;

    use CheckGlobalPermissionTrait;

    const REVISIONS_LIMIT = 10;


    /** @var ArticleModel */
    private $articleModel;

    /** @var ArticleRevisionModel */
    private $articleRevisionModel;

    /** @var ArticleReactionModel */
    private $articleReactionModel;

    /** @var ArticleFeaturedModel */
    private $articleFeaturedModel;

    /** @var KnowledgeCategoryModel */
    private $knowledgeCategoryModel;

    /** @var KnowledgeBaseModel */
    private $knowledgeBaseModel;

    /** @var DraftModel */
    private $draftModel;

    /** @var ReactionModel */
    private $reactionModel;

    /** @var ReactionOwnerModel */
    private $reactionOwnerModel;

    /** @var DiscussionsApiController */
    private $discussionApi;

    /** @var Schema */
    private $idParamSchema;

    /** @var UserModel */
    private $userModel;

    /** @var Parser */
    private $parser;

    /** @var BreadcrumbModel */
    private $breadcrumbModel;

    /** @var DiscussionArticleModel */
    private $discussionArticleModel;

    /** @var KnowledgeNavigationApiController $knowledgeNavigationApi */
    private $knowledgeNavigationApi;

    /** @var DefaultArticleModel $defaultArticleModel */
    private $defaultArticleModel;

    /** @var PageRouteAliasModel */
    private $pageRouteAliasModel;

    /** @var EventManager */
    private $eventManager;

    /** @var KnowledgeBasesApiController */
    private $knowledgeApiController;

    /**
     * ArticlesApiController constructor
     *
     * @param ArticleModel $articleModel
     * @param ArticleRevisionModel $articleRevisionModel
     * @param ArticleReactionModel $articleReactionModel
     * @param UserModel $userModel
     * @param DraftModel $draftModel
     * @param ReactionModel $reactionModel
     * @param ReactionOwnerModel $reactionOwnerModel
     * @param KnowledgeCategoryModel $knowledgeCategoryModel
     * @param KnowledgeBaseModel $knowledgeBaseModel
     * @param ExtendedContentFormatService $formatService
     * @param MediaModel $mediaModel
     * @param DiscussionsApiController $discussionApi
     * @param SessionInterface $sessionInterface
     * @param BreadcrumbModel $breadcrumbModel
     * @param DiscussionArticleModel $discussionArticleModel
     * @param PageRouteAliasModel $pageRouteAliasModel
     * @param EventManager $eventManager
     * @param ArticleFeaturedModel $articleFeaturedModel
     * @param KnowledgeApiController $knowledgeApiController
     * @param DefaultArticleModel $defaultArticleModel
     * @param KnowledgeNavigationApiController $knowledgeNavigationApi
     */
    public function __construct(
        ArticleModel $articleModel,
        ArticleRevisionModel $articleRevisionModel,
        ArticleReactionModel $articleReactionModel,
        UserModel $userModel,
        DraftModel $draftModel,
        ReactionModel $reactionModel,
        ReactionOwnerModel $reactionOwnerModel,
        KnowledgeCategoryModel $knowledgeCategoryModel,
        KnowledgeBaseModel $knowledgeBaseModel,
        ExtendedContentFormatService $formatService,
        MediaModel $mediaModel,
        DiscussionsApiController $discussionApi,
        SessionInterface $sessionInterface,
        BreadcrumbModel $breadcrumbModel,
        DiscussionArticleModel $discussionArticleModel,
        PageRouteAliasModel $pageRouteAliasModel,
        EventManager $eventManager,
        ArticleFeaturedModel $articleFeaturedModel,
        KnowledgeApiController $knowledgeApiController,
        DefaultArticleModel $defaultArticleModel,
        KnowledgeNavigationApiController $knowledgeNavigationApi
    ) {
        $this->articleModel = $articleModel;
        $this->articleRevisionModel = $articleRevisionModel;
        $this->articleReactionModel = $articleReactionModel;
        $this->articleFeaturedModel = $articleFeaturedModel;
        $this->userModel = $userModel;
        $this->draftModel = $draftModel;
        $this->reactionModel = $reactionModel;
        $this->reactionOwnerModel = $reactionOwnerModel;
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
        $this->knowledgeBaseModel = $knowledgeBaseModel;
        $this->discussionApi = $discussionApi;
        $this->breadcrumbModel = $breadcrumbModel;
        $this->discussionArticleModel = $discussionArticleModel;
        $this->pageRouteAliasModel = $pageRouteAliasModel;
        $this->eventManager = $eventManager;
        $this->knowledgeApiController = $knowledgeApiController;
        $this->defaultArticleModel = $defaultArticleModel;
        $this->knowledgeNavigationApi = $knowledgeNavigationApi;

        $this->setMediaForeignTable("article");
        $this->setMediaModel($mediaModel);
        $this->setFormatterService($formatService);
        $this->setSessionInterface($sessionInterface);
    }

    /**
     * Get an article by its numeric ID.
     *
     * @param int $id Article ID.
     * @param bool $includeRevision
     * @param bool $includeDeleted Include articles which belongs to knowledge base "deleted"
     * @param bool $includeTranslations Whether to include translated article revisions.
     *
     * @return array
     * @throws NotFoundException If the article could not be found.
     * @throws ValidationException If the result fails schema validation.
     */
    private function articleByID(int $id, bool $includeRevision = false, bool $includeDeleted = false, bool $includeTranslations = false): array {
        try {
            if ($includeRevision) {
                $article = $this->articleModel->getIDWithRevision($id, $includeTranslations);
                if ($includeTranslations) {
                    $knowledgeCategoryID = array_unique(array_column($article, "knowledgeCategoryID"));
                } else {
                    $knowledgeCategoryID = $article["knowledgeCategoryID"];
                }
                if (empty($article)) {
                    throw new NoResultsException("No rows matched the provided criteria.");
                }
            } else {
                $article = $this->articleModel->selectSingle(["articleID" => $id]);
                $knowledgeCategoryID = $article['knowledgeCategoryID'];
            }
            if (!$includeDeleted) {
                $knowledgeCategory = $this->knowledgeCategoryModel->selectSingle(['knowledgeCategoryID' => $knowledgeCategoryID]);
                $this->knowledgeBaseModel->checkKnowledgeBasePublished($knowledgeCategory['knowledgeBaseID']);
            }
        } catch (NoResultsException $e) {
            throw new NotFoundException("Article");
        }
        return $article;
    }

    /**
     * Handle GET requests to the root of the endpoint.
     *
     * @param int $id
     * @param array $query
     * @return array
     */
    public function get(int $id, array $query = []) {
        $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);

        $in = $this->idParamSchema()->setDescription("Get an article.");

        $query["id"] = $id;
        $query = $in->validate($query);

        $out = $this->articleSchema("out");
        $article = $this->retrieveRow($id, $query);

        $this->userModel->expandUsers(
            $article,
            ["insertUserID", "updateUserID"]
        );

        $crumbs = $this->breadcrumbModel->getForRecord(new KbCategoryRecordType($article['knowledgeCategoryID']), $query['locale'] ?? null);
        $article['breadcrumbs'] = $crumbs;

        $reactionCounts = $this->articleReactionModel->getReactionCount($id);
        $article['reactions'][]  = [
            'reactionType' => ArticleReactionModel::TYPE_HELPFUL,
            'yes' => (int)$reactionCounts['positiveCount'] ?? 0,
            'no' => (int)$reactionCounts['neutralCount'] ?? 0,
            'total' => (int)$reactionCounts['allCount'] ?? 0,
            'userReaction' => $this->articleReactionModel->getUserReaction(
                ArticleReactionModel::TYPE_HELPFUL,
                $id,
                $this->sessionInterface->UserID
            ),
        ];
        if (isset($query["locale"])) {
            $article["queryLocale"] = $query["locale"];
        }
        $article = $this->normalizeOutput($article);
        $result = $out->validate($article);
        return $result;
    }

    /**
     * Get the translations for an article.
     *
     * @param int $id
     * @param array $query
     * @return array
     */
    public function get_translations(int $id, array $query):array {
        $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);
        $this->idParamSchema()->setDescription("Get translations for an article");

        $in = Schema::parse([
            "status:s?" =>[
                "enum" => $this->articleModel::getAllStatuses()
            ]
        ]);

        $out = $this->schema([":a" => Schema::parse([
            "articleRevisionID:i",
            "name:s?",
            "url:s?",
            "locale:s",
            "sourceLocale:s",
            "translationStatus:s" => [
                "enum" =>["up-to-date", "out-of-date", "not-translated"]
            ],
        ], "out")
        ]);

        $query = $in->validate($query);
        $article = $this->articleByID($id, true, false, true);

        $result =  $this->getArticleTranslationData($article);
        $result = $out->validate($result);

        return $result;
    }

    /**
     * Get an article for editing.
     *
     * @param int $id
     * @param array $query
     * @return array
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     * @throws NotFoundException If the article could not be found.
     * @throws ValidationException If the output fails to validate against the schema.
     */
    public function get_edit(int $id, array $query): array {
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);

        $in = $this->idParamSchema()->setDescription("Get an article for editing.");

        $query["id"] = $id;
        $query = $in->validate($query);

        $out = $this->schema(Schema::parse([
            "articleID",
            "knowledgeCategoryID",
            "sort",
            "name",
            "body",
            "format",
            "locale",
            "foreignID"
        ])->add($this->fullSchema()), "out");
        $article = $this->retrieveRow($id, $query);
        $this->knowledgeBaseModel->checkEditPermission($article['knowledgeBaseID']);
        $body = $article['body'];
        $article = $this->normalizeOutput($article);
        $article['body'] = $body;
        $result = $out->validate($article);
        $this->applyFormatCompatibility($result, 'body', 'format');
        return $result;
    }

    /**
     * List published articles in a given knowledge category.
     *
     * @param array $query
     * @return \Garden\Web\Data
     * @throws ValidationException If input validation fails.
     * @throws ValidationException If output validation fails.
     * @throws HttpException If a relevant ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     */
    public function index(array $query = []) {
        $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);
        $in = $this->schema([
            "expand?" => \Vanilla\ApiUtils::getExpandDefinition(["excerpt"]),
            "knowledgeCategoryID" => [
                "type" => "integer",
                "minimum" => 1,
            ],
            "limit" => [
                "default" => ArticleModel::LIMIT_DEFAULT,
                "minimum" => 1,
                "maximum" => 100,
                "type" => "integer",
            ],
            "page:i?" => [
                "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                "default" => 1,
                "minimum" => 1,
                "maximum" => 100,
            ],
            "locale:s?" => [
                "description" => "Filter revisions by locale.",
            ],
            "only-translated?" => [
                "description" => "If transalted revisions does not exist don not return related article.",
                "type" => "boolean",
                "default" => false
            ],
        ], "in")->setDescription("List published articles in a given knowledge category.");
        $out = $this->schema([":a" => $this->articleSimpleSchema()], "out");

        $query = $in->validate($query);

        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);
        $includeExcerpts = $this->isExpandField("excerpt", $query["expand"]);

        $options = [
            "includeBody" => $includeExcerpts,
            "limit" => $limit,
            "offset" => $offset,
        ];

        $knowledgeCategory = $this->knowledgeCategoryByID($query["knowledgeCategoryID"]);
        $paging = \Vanilla\ApiUtils::numberedPagerInfo(
            $knowledgeCategory["articleCount"],
            "/api/v2/articles",
            $query,
            $in
        );

        // A page beyond our bounds is expected to return a not-found (404) response.
        if ($query["page"] > 1 && $query["page"] > $paging["pageCount"]) {
            throw new NotFoundException();
        }

        $kb = $this->knowledgeBaseModel->get(
            $this->knowledgeBaseModel->updateKnowledgeIDsWithCustomPermission(['knowledgeBaseID' => $knowledgeCategory['knowledgeBaseID']]),
            ['selects' => 'sortArticles']
        );
        $knowledgeBase = array_pop($kb);
        $sortRule = KnowledgeBaseModel::SORT_CONFIGS[$knowledgeBase['sortArticles']];
        $options["orderFields"] = $sortRule[0];
        $options["orderDirection"] = $sortRule[1];

        $locale = $query["locale"] ?? $knowledgeBase["sourceLocale"];

        $where = [
            "a.knowledgeCategoryID" => $query["knowledgeCategoryID"],
            "ar.locale" => $locale,
            "a.status" => ArticleModel::STATUS_PUBLISHED,
        ];

        $options['only-translated'] = (isset($query['only-translated'])) ? $query['only-translated'] : false;

        if ($options['only-translated']) {
            $where['ar.locale'] = $query['locale'] ?? $knowledgeBase['sourceLocale'];
        } else {
            $where['ar.locale'] = $knowledgeBase['sourceLocale'];
            if (!empty($query['locale']) && $query['locale'] !== $where['ar.locale']) {
                $options['arl.locale'] = $query['locale'];
            }
        }

        $rows = $this->articleModel->getWithRevision(
            $where,
            $options
        );

        foreach ($rows as &$row) {
            if (isset($query["locale"])) {
                $row["queryLocale"] = $locale;
            }
            $row = $this->normalizeOutput($row);
            if (!$includeExcerpts) {
                unset($row["excerpt"]);
            }
        }
        $this->userModel->expandUsers(
            $rows,
            ["insertUserID", "updateUserID"]
        );

        $result = $out->validate($rows);
        return new Data($result, ["paging" => $paging]);
    }

    /**
     * Get revisions from a specific article.
     *
     * @param int $id
     * @param array $query
     * @return Data
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     * @throws NotFoundException If the article could not be found.
     * @throws ValidationException If the output fails to validate against the schema.
     */
    public function index_revisions(int $id, array $query = []): Data {
        $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);

        $this->idParamSchema()->setDescription("Get revisions from a specific article.");
        $in = Schema::parse([
            "limit:i" => [
                "default" => self::REVISIONS_LIMIT,
                "minimum" => 1,
                "maximum" => 100,
                "type" => "integer",
            ],
            "page:i" => [
                "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                "default" => 1,
                "minimum" => 1,
                "maximum" => 100,
            ],
            "locale:s?" => [
                "description" => "Filter revisions by locale.",
            ],
        ]);
        $query = $in->validate($query);
        $offset = ($query['page'] - 1) * $query['limit'];

        $out = $this->schema([
            ":a" => Schema::parse([
                "articleRevisionID",
                "articleID",
                "status",
                "name",
                "locale",
                "translationStatus",
                "insertUser",
                "dateInserted",
            ])->add($this->fullRevisionSchema()),
        ], "out");

        $article = $this->articleByID($id);
        $where = ["articleID" => $article["articleID"]];

        if (!empty($query['locale'])) {
            $where['locale'] = $query['locale'];
        }

        $revisions = $this->articleRevisionModel->get(
            $where,
            [
                "orderFields" => "dateInserted",
                "orderDirection" => "desc",
                "offset" => $offset,
                "limit" => $query['limit']
            ]
        );

        $paging = \Vanilla\ApiUtils::numberedPagerInfo(
            $this->articleRevisionModel->getRevisionsCount($article["articleID"]),
            '/api/v2/articles/'.$article["articleID"].'/revisions',
            $query,
            $in
        );
        // A page beyond our bounds is expected to return a not-found (404) response.
        if ($query["page"] > 1 && $query["page"] > $paging["pageCount"]) {
            throw new NotFoundException();
        }

        foreach ($revisions as &$revision) {
            $this->userModel->expandUsers(
                $revision,
                ["insertUserID", "updateUserID"]
            );
        }

        $result = $out->validate($revisions);
        return new Data($result, ["paging" => $paging]);
    }

    /**
     * Update an existing article.
     *
     * @param int $id
     * @param array $body
     * @return array
     * @throws Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     */
    public function patch(int $id, array $body = []): array {
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);

        $in = $this->articlePatchSchema("in")
            ->addValidator("knowledgeCategoryID", [$this->knowledgeCategoryModel, "validateKBArticlesLimit"])
            ->setDescription("Update an existing article.");
        $out = $this->articleSchema("out");

        $body = $in->validate($body, true);

        if (array_key_exists("locale", $body)) {
            $body = $this->validateFirstArticleRevision($id, $body);
        }

        $this->save($body, $id);
        $row = $this->retrieveRow($id, $body);

        $locale = $body['locale'] ?? $row['locale'] ?? null;
        $crumbs = $this->breadcrumbModel->getForRecord(new KbCategoryRecordType($row['knowledgeCategoryID']), $locale);
        $row['breadcrumbs'] = $crumbs;

        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Update article status an existing article.
     *
     * @param int $id ArticleID
     * @param array $body Incoming json array with 'status' key.
     *        Possible values: published, deleted, etc
     *
     * @return array Data array Article record/item
     * @throws Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     */
    public function patch_status(int $id, array $body): array {
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);

        $this->idParamSchema();
        $in = $this->schema([
            "status:s" => [
                "description" => "Article status.",
                "enum" => ArticleModel::getAllStatuses(),
            ],
        ], "in")->setDescription("Set the status of an article.");
        $out = $this->articleSchema("out");
        $body = $in->validate($body);
        $article = $this->articleByID($id);
        if ($article['status'] !== $body['status']) {
            $this->articleModel->update(
                ['status' => $body['status']],
                ["articleID" => $id]
            );
            $this->knowledgeCategoryModel->updateCounts($article['knowledgeCategoryID']);
        }

        $row = $this->articleByID($id, true);
        $row = $this->normalizeOutput($row);
        $crumbs = $this->breadcrumbModel->getForRecord(new KbCategoryRecordType($row['knowledgeCategoryID']));
        $row['breadcrumbs'] = $crumbs;
        $result = $out->validate($row);
        return $result;
    }

    /**
     * PUT reaction on article ('helpful').
     *
     * @param int $id ArticleID
     * @param array $body Incoming json array with 'reaction' key.
     *        Possible values: , deleted, etc
     *
     * @return array Data array Article record/item
     * @throws Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     */
    public function put_react(int $id, array $body): array {
        $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);
        if (!$this->sessionInterface->isValid()) {
            throw new ClientException('User must be signed in to post reaction.');
        }

        $this->idParamSchema();
        $in = $this->schema([
            "helpful:s" => [
                "description" => "Article 'Was it Helpful?' reaction.",
                "enum" => ["yes", "no"],
            ],
        ], "in")->setDescription("Reaction about an article.");
        $out = $this->articleSchema("out");
        $body = $in->validate($body);

        // This is just check if article exists and knowledge base has status "published"
        $article = $this->articleByID($id);

        $reactionValue = array_search($body[ArticleReactionModel::TYPE_HELPFUL], ArticleReactionModel::getHelpfulReactions());
        $fields = ArticleReactionModel::getReactionFields($id, ArticleReactionModel::TYPE_HELPFUL, $reactionValue);

        $existingReactionValue = $this->articleReactionModel->getUserReaction(
            ArticleReactionModel::TYPE_HELPFUL,
            $id,
            $this->sessionInterface->UserID
        );

        if ($existingReactionValue !== null) {
            throw new ClientException('You already reacted on this article before.');
        }

        $fields['reactionOwnerID'] = $this->reactionOwnerModel->getReactionOwnerID($fields);

        $this->reactionModel->insert($fields);

        $reactionCounts = $this->articleReactionModel->updateReactionCount($id);

        $row = $this->articleByID($id, true);

        $newReactionValue = $this->articleReactionModel->getUserReaction(
            ArticleReactionModel::TYPE_HELPFUL,
            $id,
            $this->sessionInterface->UserID
        );
        $row['breadcrumbs'] =$this->breadcrumbModel->getForRecord(new KbCategoryRecordType($row['knowledgeCategoryID']));
        $row['reactions'][]  = [
            'reactionType' => ArticleReactionModel::TYPE_HELPFUL,
            'yes' => (int)$reactionCounts['positiveCount'],
            'no' => (int)$reactionCounts['neutralCount'],
            'total' => (int)$reactionCounts['allCount'],
            'userReaction' => $newReactionValue,
        ];
        $this->eventManager->fire("afterArticleReact", $row, $newReactionValue);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Create a new article.
     *
     * @param array $body
     * @return array
     * @throws Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     * @throws ClientException If locale is not supported.
     */
    public function post(array $body): array {
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);

        $in = $this->articlePostSchema("in")
            ->addValidator("knowledgeCategoryID", [$this->knowledgeCategoryModel, "validateKBArticlesLimit"])
            ->setDescription("Create a new article.");
        $out = $this->articleSchema("out");
        $body = $in->validate($body);

        $knowledgeBase = $this->getKnowledgeBaseFromCategoryID($body["knowledgeCategoryID"]);
        $sourceLocale = $knowledgeBase["sourceLocale"] ?? c("Garden.Locale");

        if (array_key_exists("locale", $body) && isset($body["locale"])) {
            if ($body["locale"] !== $sourceLocale) {
                throw new ClientException("Articles must be created in {$sourceLocale} locale.");
            }
        } else {
            $body["locale"] = $sourceLocale;
        }

        $articleID = $this->save($body);
        $row = $this->articleByID($articleID, true);
        $this->eventManager->fire("afterArticleCreate", $row);
        $row = $this->normalizeOutput($row);
        $crumbs = $this->breadcrumbModel->getForRecord(new KbCategoryRecordType($row['knowledgeCategoryID']));
        $row['breadcrumbs'] = $crumbs;
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Invalidate article translations.
     *
     * @param int $id
     * @param array $body
     * @return array
     */
    public function put_invalidateTranslations(int $id, array $body): array {
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);

        $in = $this->schema($this->idParamSchema(), "in");
        $in->validate(['id' => $id]);

        $out = $this->schema([":a" => Schema::parse([
            "articleID:i",
            "articleRevisionID:i",
            "locale:s",
            "translationStatus:s" => [
                "enum" => ArticleRevisionModel::getTranslationStatuses()
            ],
        ], "out")
        ]);

        $this->updateInvalidateArticleTranslations($id);

        $articles = $this->articleModel->getIDWithRevision($id, true);
        $results = $out->validate($articles);

        return $results;
    }

    /**
     * Add the PUT /api/v2/articles/{id}/featured resource.
     *
     * @param int $id
     * @param array $body
     *
     * @return array
     */
    public function put_featured(int $id, array $body) {
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);

        $this->idParamSchema();
        $in = $this->schema(Schema::parse([
            "featured:b",
        ]), "in");

        // Check that the article exists.
        $this->articleByID($id);

        $body = $in->validate($body);

        $this->articleFeaturedModel->update(['featured' => ($body['featured'] ? 1 : 0)], ['articleID' => $id]);
        $this->knowledgeBaseModel->resetSphinxCounters();
        return $this->get($id);
    }

    /**
    * Get related articles.
    *
    * @param int $id
    * @param array $query
    * @return array
    */
    public function get_articlesRelated(int $id, array $query): array {
        $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);

        $query["locale"] = $query["locale"] ?? 'en';
        $query["limit"] = $query["limit"] ?? ArticleModel::RELATED_ARTICLES_LIMIT;

        $in = $this->schema(Schema::parse([
            "name:s?",
            "limit:i?",
            "locale:s?",
            "minimumArticles:i?"
        ], "in")->setDescription("Get related Articles"));

        $out = $this->schema([":a" => $this->knowledgeApiController->searchResultSchema()], "out");
        $query = $in->validate($query);

        $minimumArticles = $query['minimumArticles'] ?? null;
        $article = $this->articleByID($id, true);
        $knowledgeBaseID = $article["knowledgeBaseID"] ?? '';
        $knowledgeBase =  $this->knowledgeBaseModel->get(["knowledgeBaseID" => $knowledgeBaseID]);
        $knowledgeBase = reset($knowledgeBase);
        $siteSectionGroup = $knowledgeBase["siteSectionGroup"] ?? '';

        $query = [
            "all" => $article["name"],
            "locale" => $query["locale"],
            "knowledgeCategoryID" => $article["knowledgeCategoryID"],
            "limit" => $query["limit"]
        ];

        $articles = $this->queryRelatedArticles($id, $query);

        if (count($articles) < $minimumArticles) {
            $query = [
                "all" => $article["name"],
                "locale" => $query["locale"],
                "limit" => $query["limit"]
            ];

            if ($siteSectionGroup) {
                $query["siteSectionGroup"] = $siteSectionGroup;
            } else {
                $query["knowledgeBaseID"] = $knowledgeBaseID;
            }

            $articles = $this->queryRelatedArticles($id, $query);
        }

        $articles = $out->validate($articles);

        return $articles;
    }

    /**
     * Get related articles using search endpoint.
     *
     * @param int $id
     * @param array $query
     *
     * @return array
     */
    protected function queryRelatedArticles(int $id, array $query): array {
        $results = $this->knowledgeApiController->get_search($query);

        $articles = $results->getData();
        foreach ($articles as $key => $article) {
            if ($article["recordID"] === $id) {
                unset($articles[$key]);
                break;
            }
        }
        return array_values($articles);
    }
}
