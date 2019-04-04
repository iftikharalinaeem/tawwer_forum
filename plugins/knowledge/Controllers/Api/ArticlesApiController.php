<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use Garden\Web\Exception\ClientException;
use Gdn_Session as SessionInterface;
use MediaModel;
use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Gdn_Format;
use UserModel;
use Vanilla\Knowledge\Models\ArticleDraft;
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
use Vanilla\ApiUtils;

/**
 * API controller for managing the articles resource.
 */
class ArticlesApiController extends AbstractKnowledgeApiController {

    use ArticlesApiSchemes;

    use UpdateMediaTrait;

    const REVISIONS_LIMIT = 10;

    /** @var ArticleModel */
    private $articleModel;

    /** @var ArticleRevisionModel */
    private $articleRevisionModel;

    /** @var ArticleReactionModel */
    private $articleReactionModel;

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
     * @param Parser $parser
     * @param KnowledgeCategoryModel $knowledgeCategoryModel
     * @param KnowledgeBaseModel $knowledgeBaseModel
     * @param FormatService $formatService
     * @param MediaModel $mediaModel
     * @param DiscussionsApiController $discussionApi
     * @param SessionInterface $sessionInterface
     * @param BreadcrumbModel $breadcrumbModel
     * @param DiscussionArticleModel $discussionArticleModel
     */
    public function __construct(
        ArticleModel $articleModel,
        ArticleRevisionModel $articleRevisionModel,
        ArticleReactionModel $articleReactionModel,
        UserModel $userModel,
        DraftModel $draftModel,
        ReactionModel $reactionModel,
        ReactionOwnerModel $reactionOwnerModel,
        Parser $parser,
        KnowledgeCategoryModel $knowledgeCategoryModel,
        KnowledgeBaseModel $knowledgeBaseModel,
        FormatService $formatService,
        MediaModel $mediaModel,
        DiscussionsApiController $discussionApi,
        SessionInterface $sessionInterface,
        BreadcrumbModel $breadcrumbModel,
        DiscussionArticleModel $discussionArticleModel
    ) {
        $this->articleModel = $articleModel;
        $this->articleRevisionModel = $articleRevisionModel;
        $this->articleReactionModel = $articleReactionModel;
        $this->userModel = $userModel;
        $this->draftModel = $draftModel;
        $this->reactionModel = $reactionModel;
        $this->reactionOwnerModel = $reactionOwnerModel;
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
        $this->knowledgeBaseModel = $knowledgeBaseModel;
        $this->discussionApi = $discussionApi;
        $this->parser = $parser;
        $this->breadcrumbModel = $breadcrumbModel;
        $this->discussionArticleModel = $discussionArticleModel;

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
     *
     * @return array
     * @throws NotFoundException If the article could not be found.
     * @throws ValidationException If the result fails schema validation.
     */
    private function articleByID(int $id, bool $includeRevision = false, bool $includeDeleted = false): array {
        try {
            if ($includeRevision) {
                $article = $this->articleModel->getIDWithRevision($id);
            } else {
                $article = $this->articleModel->selectSingle(["articleID" => $id]);
            }
            if (!$includeDeleted) {
                $knowledgeCategory = $this->knowledgeCategoryModel->selectSingle(['knowledgeCategoryID' => $article['knowledgeCategoryID']]);
                $this->knowledgeBaseModel->checkKnowledgeBasePublished($knowledgeCategory['knowledgeBaseID']);
            }
        } catch (NoResultsException $e) {
            throw new NotFoundException("Article");
        }
        return $article;
    }

    /**
     * Delete an article draft.
     *
     * @param int $draftID
     * @return mixed
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws NotFoundException If the article draft could not be found.
     * @throws PermissionException If the user does not have the specified permission(s).
     * @throws ValidationException If the output fails to validate against the schema.
     */
    public function delete_drafts(int $draftID) {
        $this->permission("Garden.SignIn.Allow");

        $in = $this->schema([
            "draftID" => [
                "description" => "Target article draft ID.",
                "type" => "integer",
            ],
        ], "in")->setDescription("Delete an article draft.");
        $out = $this->schema([], "out");

        $draft = $this->draftByID($draftID);
        if ($draft["insertUserID"] !== $this->getSession()->UserID) {
            $this->permission("Garden.Settings.Manage");
        }
        $this->draftModel->delete(
            ["draftID" => $draft["draftID"]]
        );
    }

    /**
     * Get an article draft by its numeric ID.
     *
     * @param int $id Article ID.
     * @param bool $includeDeleted Include articles which belongs to knowledge base "deleted"
     *
     * @return array
     * @throws NotFoundException If the draft could not be found.
     * @throws ValidationException If the result fails schema validation.
     */
    private function draftByID(int $id, bool $includeDeleted = false): array {
        try {
            $draft = $this->draftModel->selectSingle([
                "draftID" => $id,
                "recordType" => "article",
            ]);
            if (!$includeDeleted && ($draft['recordID'] ?? false)) {
                //check if article exists and knowledge base is "published"
                $this->articleByID($draft['recordID']);
            }
        } catch (NoResultsException $e) {
            throw new NotFoundException("Draft");
        }
        return $draft;
    }

    /**
     * Handle GET requests to the root of the endpoint.
     *
     * @param int $id
     * @return array
     * @throws Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     * @throws ValidationException If input validation fails.
     * @throws ValidationException If output validation fails.
     * @throws NotFoundException If the article could not be found.
     * @throws ServerException If there was an error normalizing the output.
     */
    public function get(int $id) {
        $this->permission("knowledge.kb.view");

        $in = $this->idParamSchema()->setDescription("Get an article.");
        $out = $this->articleSchema("out");

        $article = $this->articleByID($id, true);
        $this->userModel->expandUsers(
            $article,
            ["insertUserID", "updateUserID"]
        );

        $crumbs = $this->breadcrumbModel->getForRecord(new KbCategoryRecordType($article['knowledgeCategoryID']));
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

        $article = $this->normalizeOutput($article);
        $result = $out->validate($article);
        return $result;
    }

    /**
     * Get a single article draft.
     *
     * @param int $draftID
     * @return mixed
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws NotFoundException If the article draft could not be found.
     * @throws PermissionException If the user does not have the specified permission(s).
     * @throws ValidationException If the output fails to validate against the schema.
     */
    public function get_drafts(int $draftID) {
        $this->permission("knowledge.articles.add");

        $in = $this->schema([
            "draftID" => [
                "description" => "Target article draft ID.",
                "type" => "integer",
            ],
        ], "in")->setDescription("Get a single article draft.");
        $out = $this->schema($this->fullDraftSchema(), "out");

        $draft = $this->draftByID($draftID);
        $draft = (new ArticleDraft($this->parser))->normalizeDraftFields($draft);
        $result = $out->validate($draft);
        return $result;
    }

    /**
     * Get an article for editing.
     *
     * @param int $id
     * @return array
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     * @throws NotFoundException If the article could not be found.
     * @throws ValidationException If the output fails to validate against the schema.
     */
    public function get_edit(int $id): array {
        $this->permission("knowledge.articles.add");

        $this->idParamSchema()->setDescription("Get an article for editing.");
        $out = $this->schema(Schema::parse([
            "articleID",
            "knowledgeCategoryID",
            "sort",
            "name",
            "body",
            "format",
            "locale",
        ])->add($this->fullSchema()), "out");

        $article = $this->articleByID($id, true);
        $body = $article['body'];
        $article = $this->normalizeOutput($article);
        $article['body'] = $body;
        $result = $out->validate($article);
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
        $this->permission("knowledge.kb.view");
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
            "order:s?" => [
                "description" => "Sort method for results.",
                "enum" => ["dateInserted", "dateUpdated", "sort"],
                "default" => "dateInserted",
            ],
            "page:i?" => [
                "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                "default" => 1,
                "minimum" => 1,
                "maximum" => 100,
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
            "orderFields" => $query["order"],
        ];
        switch ($query["order"]) {
            case "dateUpdated":
                $options["orderDirection"] = "desc";
                break;
            default:
                $options["orderDirection"] = "asc";
        }

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

        $rows = $this->articleModel->getWithRevision(
            ["a.knowledgeCategoryID" => $query["knowledgeCategoryID"]],
            $options
        );

        foreach ($rows as &$row) {
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
     * Get a community discussion in a format that is easy to consume when creating a new article.
     *
     * @param array $query Request query.
     */
    public function index_fromDiscussion(array $query) {
        $this->permission("knowledge.articles.add");

        $in = $this->schema([
            "discussionID" => [
                "description" => "Unique identifier for the community discussion.",
                "type" => "integer",
            ]
        ], "in");
        $out = $this->discussionArticleSchema("out");

        $query = $in->validate($query);
        $article = $this->discussionArticleModel->discussionData($query["discussionID"]);

        $result = $out->validate($article);
        return $result;
    }

    /**
     * List article drafts.
     *
     * @param array $query
     * @return mixed
     * @throws HttpException If a relevant ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     * @throws ValidationException If input validation fails.
     * @throws ValidationException If output validation fails.
     */
    public function index_drafts(array $query) {
        $this->permission("knowledge.articles.add");

        $in = $this->schema([
            "articleID?" => [
                "description" => "Unique ID article associated with a draft.",
                "type" => "integer",
                "x-filter" => [
                    "field" => "recordID",
                ],
            ],
            "insertUserID?" => [
                "description" => "Unique ID of the user who created the article draft.",
                "type" => "integer",
                "x-filter" => [
                    "field" => "insertUserID",
                ],
            ],
            "expand?" => ApiUtils::getExpandDefinition(["insertUser", "updateUser"]),
        ], "in")->setDescription("List article drafts.")->requireOneOf(["articleID", "insertUserID"]);
        $out = $this->schema([
            ":a" => $this->fullDraftSchema(),
        ], "out");

        $query = $in->validate($query);
        if ($query['articleID'] ?? false) {
            //check if article exists and knowledge base is "published"
            $this->articleByID($query['articleID']);
        }

        $where = ["recordType" => "article"] + \Vanilla\ApiUtils::queryToFilters($in, $query);
        $options = ['orderFields' => 'dateUpdated', 'orderDirection' => 'desc'];
        $rows = $this->draftModel->get($where, $options);
        $rows = (new ArticleDraft($this->parser))->normalizeDraftFields($rows, false);

        $expandUsers = $this->resolveExpandFields(
            $query,
            [
                "insertUser" => "insertUserID",
                "updateUser" => "updateUserID",
            ]
        );
        $this->userModel->expandUsers(
            $rows,
            $expandUsers
        );

        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Get revisions from a specific article.
     *
     * @param int $id
     * @return Data
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     * @throws NotFoundException If the article could not be found.
     * @throws ValidationException If the output fails to validate against the schema.
     */
    public function index_revisions(int $id, array $query = []): Data {
        $this->permission("knowledge.kb.view");

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
                "insertUser",
                "dateInserted",
            ])->add($this->fullRevisionSchema()),
        ], "out");

        $article = $this->articleByID($id);

        $revisions = $this->articleRevisionModel->get(
            ["articleID" => $article["articleID"]],
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
     * Get an knowledge category by its numeric ID.
     *
     * @param int $id Knowledge category ID.
     * @param bool $includeDeleted Include knowledge category which belongs to knowledge base "deleted"
     * @return array
     * @throws NotFoundException If the knowledge category could not be found.
     * @throws ValidationException If the result fails schema validation.
     */
    private function knowledgeCategoryByID(int $id, bool $includeDeleted = false): array {
        try {
            $knowledgeCategory = $this->knowledgeCategoryModel->selectSingle(["knowledgeCategoryID" => $id]);
            if (!$includeDeleted) {
                $this->knowledgeBaseModel->checkKnowledgeBasePublished($knowledgeCategory['knowledgeBaseID']);
            }
        } catch (NoResultsException $e) {
            throw new NotFoundException("Knowledge Category");
        }
        return $knowledgeCategory;
    }

    /**
     * Massage article row data for useful API output.
     *
     * @param array $row
     * @return array
     * @throws ServerException If no article ID was found in the row.
     */
    public function normalizeOutput(array $row): array {
        $articleID = $row["articleID"] ?? null;
        if (!$articleID) {
            throw new ServerException("No ID in article row.");
        }

        $name = $row["name"] ?? null;
        $slug = $articleID . ($name ? "-" . Gdn_Format::url($name) : "");
        $row["url"] = \Gdn::request()->url("/kb/articles/{$slug}");

        $bodyRendered = $row["bodyRendered"] ?? null;
        $row["body"] = $bodyRendered;
        $row["outline"] = isset($row["outline"]) ? json_decode($row["outline"], true) : [];
        // Placeholder data.
        $row["seoName"] = null;
        $row["seoDescription"] = null;
        $row["slug"] = $slug;

        return $row;
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
        $this->permission("knowledge.articles.add");

        $in = $this->articlePostSchema("in")
            ->addValidator("knowledgeCategoryID", [$this->knowledgeCategoryModel, "validateKBArticlesLimit"])
            ->setDescription("Update an existing article.");
        $out = $this->articleSchema("out");

        $body = $in->validate($body, true);
        $this->save($body, $id);
        $row = $this->articleByID($id, true);

        $crumbs = $this->breadcrumbModel->getForRecord(new KbCategoryRecordType($row['knowledgeCategoryID']));
        $row['breadcrumbs'] = $crumbs;

        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Update an article draft.
     *
     * @param int $draftID
     * @param array $body
     * @return array
     * @throws Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     */
    public function patch_drafts(int $draftID, array $body): array {
        $this->permission("knowledge.articles.add");


        $this->schema(["draftID" => "Target article draft ID."], "in");
        $in = $this->schema($this->draftPostSchema(), "in")
            ->setDescription("Update an article draft.");
        $out = $this->schema($this->fullDraftSchema(), "out");

        $body = $in->validate($body, true);

        $body["recordType"] = "article";
        $body = (new ArticleDraft($this->parser))->prepareDraftFields($body);

        $draft = $this->draftByID($draftID);
        if ($draft["insertUserID"] !== $this->getSession()->UserID) {
            $this->permission("Garden.Settings.Manage");
        }

        $this->draftModel->update($body, ["draftID" => $draftID]);
        $row = $this->draftByID($draftID);
        $row = (new ArticleDraft($this->parser))->normalizeDraftFields($row);
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
        $this->permission("knowledge.articles.add");

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
        $this->permission("knowledge.kb.view");
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
     */
    public function post(array $body): array {
        $this->permission("knowledge.articles.add");

        $in = $this->articlePostSchema("in")
            ->addValidator("knowledgeCategoryID", [$this->knowledgeCategoryModel, "validateKBArticlesLimit"])
            ->setDescription("Create a new article.");
        $out = $this->articleSchema("out");

        $body = $in->validate($body);
        $articleID = $this->save($body);
        $row = $this->articleByID($articleID, true);
        $row = $this->normalizeOutput($row);
        $crumbs = $this->breadcrumbModel->getForRecord(new KbCategoryRecordType($row['knowledgeCategoryID']));
        $row['breadcrumbs'] = $crumbs;
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Create a new article draft.
     *
     * @param array $body
     * @return array
     * @throws Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     */
    public function post_drafts(array $body): array {
        $this->permission("knowledge.articles.add");

        $in = $this->schema($this->draftPostSchema(), "in")
            ->setDescription("Create a new article draft.");
        $out = $this->schema($this->fullDraftSchema(), "out");

        $body = $in->validate($body);
        $body["recordType"] = "article";
        if ($body['recordID'] ?? false) {
            //check if article exists and knowledge base is "published"
            $this->articleByID($body['recordID']);
        }

        $body = (new ArticleDraft($this->parser))->prepareDraftFields($body);

        $draftID = $this->draftModel->insert($body);
        $row = $this->draftByID($draftID);
        $row = (new ArticleDraft($this->parser))->normalizeDraftFields($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Separate article and revision fields from request input and save to the proper resources.
     *
     * @param array $fields
     * @param int|null $articleID
     * @return int
     * @throws Exception If an error is encountered while performing underlying database operations.
     * @throws NoResultsException If the article could not be found.
     */
    private function save(array $fields, int $articleID = null): int {
        $revisionFields = ["body" => true, "format" => true, "locale" => true, "name" => true];

        $article = array_diff_key($fields, $revisionFields);
        $revision = array_intersect_key($fields, $revisionFields);

        if ($articleID !== null) {
            // this means we patch existing Article
            $prevState = $this->articleModel->getID($articleID);

            //check if knowledge category exists and knowledge base is "published"
            $this->knowledgeCategoryByID($article['knowledgeCategoryID'] ?? $prevState['knowledgeCategoryID']);

            $moveToAnotherCategory = (isset($article['knowledgeCategoryID'])
                && $prevState['knowledgeCategoryID'] !== $article['knowledgeCategoryID']);

            if (!is_int($fields['sort'] ?? false)) {
                if ($moveToAnotherCategory) {
                    $sortInfo = $this->knowledgeCategoryModel->getMaxSortIdx($article['knowledgeCategoryID']);
                    $maxSortIndex = $sortInfo['maxSort'];
                    $article['sort'] = $maxSortIndex + 1;
                    $updateSorts = false;
                } else {
                    // if we don't change the categoryID and there is no $fields['sort']
                    // then we don't need to update sorting
                    $updateSorts = false;
                }
            } else {
                //update sorts for other records only if 'sort' changed
                $updateSorts = ($article['sort'] != $prevState['sort']);
            }

            if (isset($article['sort'])
                && isset($prevState['knowledgeCategoryID'])
                && isset($prevState['sort'])
                && $article['sort'] != $prevState['sort'] ) {
                $this->knowledgeCategoryModel->updateCounts($prevState['knowledgeCategoryID']);
                //shift sorts down for source category when move one article to another category
                $this->knowledgeCategoryModel->shiftSorts(
                    $prevState['knowledgeCategoryID'],
                    $prevState['sort'],
                    $prevState['articleID'],
                    KnowledgeCategoryModel::SORT_TYPE_ARTICLE,
                    KnowledgeCategoryModel::SORT_DECREMENT
                );
            }

            $this->articleModel->update($article, ["articleID" => $articleID]);

            if ($moveToAnotherCategory) {
                if (!empty($prevState['knowledgeCategoryID'])) {
                    $this->knowledgeCategoryModel->updateCounts($prevState['knowledgeCategoryID']);
                }
            }


            if ($updateSorts) {
                $this->knowledgeCategoryModel->shiftSorts(
                    $article['knowledgeCategoryID'] ?? $prevState['knowledgeCategoryID'],
                    $article['sort'],
                    $articleID,
                    KnowledgeCategoryModel::SORT_TYPE_ARTICLE
                );
            }
        } else {
            //check if knowledge category exists and knowledge base is "published"
            $this->knowledgeCategoryByID($fields['knowledgeCategoryID']);
            // this means we insert a new Article
            $sortInfo = $this->knowledgeCategoryModel->getMaxSortIdx($fields['knowledgeCategoryID']);
            $maxSortIndex = $sortInfo['maxSort'];
            if (!is_int($fields['sort'] ?? false)) {
                if ($sortInfo['viewType'] === KnowledgeBaseModel::TYPE_GUIDE) {
                    $fields['sort'] = $maxSortIndex + 1;
                }
                $updateSorts = false;
            } else {
                if ($sortInfo['viewType'] === KnowledgeBaseModel::TYPE_GUIDE) {
                    $updateSorts = ($fields['sort'] <= $maxSortIndex);
                } else {
                    // when KB is in Help center mode or KB is not in Manual sorting mode
                    // we don't need to update sorting from Articles API
                    $updateSorts = false;
                }
            }
            $articleID = $this->articleModel->insert($fields);
            if ($updateSorts) {
                $this->knowledgeCategoryModel->shiftSorts(
                    $fields['knowledgeCategoryID'],
                    $fields['sort'],
                    $articleID,
                    KnowledgeCategoryModel::SORT_TYPE_ARTICLE
                );
            }
        }
        if (!empty($article['knowledgeCategoryID'])) {
            $this->knowledgeCategoryModel->updateCounts($article['knowledgeCategoryID']);
        }


        if (!empty($revision)) {
            // Grab the current revision, if available, to load as initial defaults.
            $currentRevision = array_intersect_key($this->articleModel->getIDWithRevision($articleID), $revisionFields);
            $revision = array_merge($currentRevision, $revision);
            $revision["articleID"] = $articleID;
            $revision["locale"] = $fields["locale"] ?? $this->getLocale()->current();

            // Temporary defaults until drafts are implemented, at which point these fields will be required.
            $revision["name"] = $revision["name"] ?? "";
            $revision["body"] = $revision["body"] ?? "";
            $revision["format"] = $revision["format"] ?? strtolower(\Gdn_Format::defaultFormat());

            // Temporary hack to avoid a Rich format error if we have no body.
            if ($revision["body"] === "" && $revision["format"] === "rich") {
                $revision["body"] = "[]";
            }

            $revision["bodyRendered"] = \Gdn_Format::to($revision["body"], $revision["format"]);

            if ($revision["format"] === "rich") {
                $plainText = (new ArticleDraft($this->parser))->getPlainText($revision["body"]);
                $revision["plainText"] = $plainText;
                $revision["excerpt"] = (new ArticleDraft($this->parser))->getExcerpt($plainText);
                $revision["outline"] = json_encode(ArticleDraft::getOutline($revision["body"]));
            }

            $articleRevisionID = $this->articleRevisionModel->insert($revision);
            $this->articleRevisionModel->publish($articleRevisionID);

            $this->flagInactiveMedia($articleID, $revision["body"], $revision["format"]);
            $this->refreshMediaAttachments($articleID, $revision["body"], $revision["format"]);
        }

        if ($fields['discussionID'] ?? false) {
            // canonicalize discussion
            $article = $this->articleModel->getIDWithRevision($articleID);
            $articleUrl = $this->articleModel->url($article);
            $this->discussionApi->put_canonicalUrl($fields['discussionID'], ['canonicalUrl' => $articleUrl]);
        }

        if (array_key_exists("draftID", $fields)) {
            $this->draftModel->delete([
                "draftID" => $fields["draftID"],
                "recordType" => "article",
            ]);
        }

        return $articleID;
    }
}
