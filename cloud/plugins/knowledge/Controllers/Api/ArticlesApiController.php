<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use Garden\EventManager;
use Garden\Http\HttpClient;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\HttpException;
use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use UserModel;
use Vanilla\ApiUtils;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Database\Operation;
use Vanilla\Exception\PermissionException;
use Vanilla\Formatting\ExtendedContentFormatService;
use Vanilla\Formatting\FormatCompatTrait;
use Vanilla\Formatting\FormatService;
use Vanilla\Knowledge\Models\ArticleFeaturedModel;
use Vanilla\Knowledge\Models\KbCategoryRecordType;
use Vanilla\Knowledge\Models\ArticleReactionModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeNavigationModel;
use Vanilla\Models\DraftModel;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;
use Vanilla\Formatting\Quill\Parser;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Vanilla\Models\Model;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Models\ReactionModel;
use Vanilla\Models\ReactionOwnerModel;
use DiscussionsApiController;
use Vanilla\Knowledge\Models\DiscussionArticleModel;
use Garden\Web\Data;
use Vanilla\Knowledge\Models\PageRouteAliasModel;
use Vanilla\Knowledge\Models\DefaultArticleModel;
use Vanilla\ReCaptchaVerification;
use Vanilla\Utility\SchemaUtils;

/**
 * API controller for managing the articles resource.
 */
class ArticlesApiController extends AbstractKnowledgeApiController {

    use ArticlesApiSchemes;

    use FormatCompatTrait;

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

    /** @var KnowledgeNavigationModel */
    private $knowledgeNavigationModel;

    /** @var DefaultArticleModel $defaultArticleModel */
    private $defaultArticleModel;

    /** @var PageRouteAliasModel */
    private $pageRouteAliasModel;

    /** @var EventManager */
    private $eventManager;

    /** @var KnowledgeBasesApiController */
    private $knowledgeApiController;

    /** @var ArticlesApiHelper */
    private $articleHelper;

    /** @var \Gdn_Session */
    private $session;

    /** @var ExtendedContentFormatService */
    private $formatService;

    /** @var ReCaptchaVerification */
    private $reCaptchaVerification;

    /**
     * DI.
     * @inheritdoc
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
        BreadcrumbModel $breadcrumbModel,
        DiscussionArticleModel $discussionArticleModel,
        PageRouteAliasModel $pageRouteAliasModel,
        EventManager $eventManager,
        ArticleFeaturedModel $articleFeaturedModel,
        KnowledgeApiController $knowledgeApiController,
        ArticlesApiHelper $articleHelper,
        \Gdn_Session $session,
        ExtendedContentFormatService $formatService,
        ReCaptchaVerification $reCaptchaVerification
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
        $this->breadcrumbModel = $breadcrumbModel;
        $this->discussionArticleModel = $discussionArticleModel;
        $this->pageRouteAliasModel = $pageRouteAliasModel;
        $this->eventManager = $eventManager;
        $this->knowledgeApiController = $knowledgeApiController;
        $this->articleHelper = $articleHelper;
        $this->session = $session;
        $this->formatService = $formatService;
        $this->reCaptchaVerification = $reCaptchaVerification;

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
        $article = $this->articleHelper->retrieveRow($id, $query);

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
                'userReaction' => ($this->session->UserID) ? $this->articleReactionModel->getUserReaction(
                    ArticleReactionModel::TYPE_HELPFUL,
                    $id,
                    $this->session->UserID
                ) : null,
            ];

        if (isset($query["locale"])) {
            $article["queryLocale"] = $query["locale"];
        }
        $article = $this->articleHelper->normalizeOutput($article);
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
            "dateUpdated:dt",
            "translationStatus:s" => [
                "enum" =>["up-to-date", "out-of-date", "not-translated"]
            ],
        ], "out")
        ]);

        $query = $in->validate($query);
        $article = $this->articleByID($id, true, false, true);

        $result =  $this->articleHelper->getArticleTranslationData($article);
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
            "featured?",
            "locale",
            "status",
            "foreignID",
            "dateUpdated"
        ])->add($this->fullSchema()), "out");
        $article = $this->articleHelper->retrieveRow($id, $query);
        $this->knowledgeBaseModel->checkEditPermission($article['knowledgeBaseID']);
        $body = $article['body'];
        $article = $this->articleHelper->normalizeOutput($article);
        $article['body'] = $body;
        $result = $out->validate($article);
        $this->applyFormatCompatibility($result, 'body', 'format');
        return $result;
    }

    /**
     * List published articles in a given knowledge category.
     *
     * @param array $query
     * @return Data
     * @throws ValidationException If input validation fails.
     * @throws ValidationException If output validation fails.
     * @throws HttpException If a relevant ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     */
    public function index(array $query = []) {
        $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);
        $in = $this->schema([
            // Filter by specific articles.
            'articleID?' => \Vanilla\Schema\RangeExpression::createSchema([':int'])->setField('x-filter', ['field' => 'a.articleID']),
            // Filter by a category.
            "knowledgeCategoryID?" => [
                "type" => "integer",
                "minimum" => 1,
            ],
            "includeSubcategories:b?",

            "locale:s?" => [
                "description" => "Filter revisions by locale.",
            ],

            'sort:s?' => [
                'enum' => ApiUtils::sortEnum('sort', 'dateInserted', 'dateUpdated', 'dateFeatured', 'score', 'articleID'),
            ],

            "page:i?" => [
                "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                "default" => 1,
                "minimum" => 1,
                "maximum" => 100,
            ],
            "limit" => [
                "default" => ArticleModel::LIMIT_DEFAULT,
                "minimum" => 1,
                "maximum" => 100,
                "type" => "integer",
            ],
            "only-translated?" => [
                "description" => "If transalted revisions does not exist don not return related article.",
                "type" => "boolean",
                "default" => false
            ],
            "expand?" => \Vanilla\ApiUtils::getExpandDefinition(["excerpt"]),
        ], "in")
            ->requireOneOf(['articleID', 'knowledgeCategoryID'])
            ->addValidator('', SchemaUtils::onlyOneOf(['articleID', 'knowledgeCategoryID']))
            ->setDescription("List published articles in a given knowledge category.");

        $out = $this->schema([":a" => $this->articleSimpleSchema()], "out");

        $query = $in->validate($query);

        [$offset, $limit] = offsetLimit("p{$query['page']}", $query['limit']);
        $includeExcerpts = $this->isExpandField("excerpt", $query["expand"]);

        $options = [
            "includeBody" => $includeExcerpts,
            "limit" => $limit,
            "offset" => $offset,
        ];

        $where = [
            "a.status" => ArticleModel::STATUS_PUBLISHED,
        ];

        // Do the basic top-level filtering.
        if (!empty($query['knowledgeCategoryID'])) {
            $includeSubcategories = $query['includeSubcategories'] ?? false;
            $knowledgeCategory = $this->articleHelper->knowledgeCategoryByID($query["knowledgeCategoryID"]);
            $knowledgeBase = $this->knowledgeBaseModel->selectSingle(
                $this->knowledgeBaseModel->updateKnowledgeIDsWithCustomPermission(['knowledgeBaseID' => $knowledgeCategory['knowledgeBaseID']]),
                ['selects' => 'sortArticles']
            );

            $categoryIDs = [$query['knowledgeCategoryID']];
            if ($includeSubcategories) {
                $collection = $this->knowledgeCategoryModel->getCollectionForKB($knowledgeBase['knowledgeBaseID']);
                $includedCategories = $collection->getWithChildren($query['knowledgeCategoryID']);
                $categoryIDs = array_column($includedCategories, 'knowledgeCategoryID');
            }

            $where["a.knowledgeCategoryID"] = $categoryIDs;
        } elseif (!empty($query['articleID'])) {
            $where['a.articleID'] = $query['articleID'];
        }

        // Filter the translation.
        $options['only-translated'] = (isset($query['only-translated'])) ? $query['only-translated'] : false;
        if ($options['only-translated']) {
            if (!empty($query['locale'])) {
                $where['ar.locale'] = $query['locale'];
            } elseif (isset($knowledgeBase)) {
                $where['ar.locale'] = $knowledgeBase['sourceLocale'];
            }
        } elseif (!empty($query['articleID']) || !isset($knowledgeBase)) {
            if (!empty($query['locale'])) {
                $where['ar.locale'] = $query['locale'];
            }
        } else {
            $where['ar.locale'] = $knowledgeBase['sourceLocale'];
            if (!empty($query['locale']) && $query['locale'] !== $where['ar.locale']) {
                $options['arl.locale'] = $query['locale'];
            }
        }

        if (!empty($query['sort'])) {
            $options['orderFields'] = [$query['sort']];
        } elseif (isset($knowledgeBase)) {
            $sortRule = KnowledgeBaseModel::SORT_CONFIGS[$knowledgeBase['sortArticles']];
            [$options["orderFields"], $options["orderDirection"]] = $sortRule;
        } else {
            $options['orderFields'] = ['articleID'];
        }

        $rows = $this->articleModel->getWithRevision(
            $where,
            $options
        );

        $locale = $query["locale"] ?? ($knowledgeBase["sourceLocale"] ?? '');
        foreach ($rows as &$row) {
            if (isset($query["locale"])) {
                $row["queryLocale"] = $locale;
            }
            $row = $this->articleHelper->normalizeOutput($row);
            if (!$includeExcerpts) {
                unset($row["excerpt"]);
            }
        }
        $this->userModel->expandUsers(
            $rows,
            ["insertUserID", "updateUserID"]
        );

        if (isset($knowledgeCategory)) {
            $paging = \Vanilla\ApiUtils::numberedPagerInfo(
                $includeSubcategories ? $knowledgeCategory['articleCountRecursive'] : $knowledgeCategory["articleCount"],
                "/api/v2/articles",
                $query,
                $in
            );

            // A page beyond our bounds is expected to return a not-found (404) response.
            if ($query["page"] > 1 && $query["page"] > $paging["pageCount"]) {
                throw new NotFoundException();
            }
        } else {
            $paging = ApiUtils::morePagerInfo(
                $rows,
                '/api/v2/articles',
                $query,
                $in
            );
        }

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
     * @return Data
     * @throws \Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     */
    public function patch(int $id, array $body = []): Data {
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);

        $in = $this->articlePatchSchema("in")
            ->addValidator("knowledgeCategoryID", [$this->knowledgeCategoryModel, "validateKBArticlesLimit"])
            ->setDescription("Update an existing article.");
        $out = $this->articleSchema("out");

        $body = $in->validate($body, true);

        $initialRow = $this->articleHelper->retrieveRow($id);
        // Make sure we have permission of the place we're coming from.
        $initialKnowledgeBase = $this->articleHelper->getKnowledgeBaseFromCategoryID($initialRow["knowledgeCategoryID"]);
        $this->knowledgeBaseModel->checkEditPermission($initialKnowledgeBase['knowledgeBaseID']);

        if (isset($body['knowledgeCategoryID'])) {
            // Make sure we have permission of the place we're going to.
            $newKnowledgeBase = $this->articleHelper->getKnowledgeBaseFromCategoryID($body['knowledgeCategoryID']);
            $this->knowledgeBaseModel->checkEditPermission($newKnowledgeBase['knowledgeBaseID']);
        }

        if (array_key_exists("locale", $body)) {
            $body = $this->validateFirstArticleRevision($id, $body);
        }

        $bodyWithFormat = $body;
        // Make sure the format gets passed.
        if (!isset($bodyWithFormat['format'])) {
            $bodyWithFormat['format'] = $initialRow['format'];
        }
        [$body, $rehostResponseHeaders] = $this->articleHelper->rehostArticleImages($bodyWithFormat);
        $this->articleHelper->save($body, $id);
        $row = $this->articleHelper->retrieveRow($id, $body);

        $locale = $body['locale'] ?? $row['locale'] ?? null;
        $crumbs = $this->breadcrumbModel->getForRecord(new KbCategoryRecordType($row['knowledgeCategoryID']), $locale);
        $row['breadcrumbs'] = $crumbs;

        $row = $this->articleHelper->normalizeOutput($row);
        $result = $out->validate($row);
        return new Data($result, [], $rehostResponseHeaders);
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
            $this->articleHelper->updateDefaultArticleID($article["knowledgeCategoryID"]);
        }

        $row = $this->articleByID($id, true);
        $row = $this->articleHelper->normalizeOutput($row);
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
     * @throws \Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     */
    public function put_react(int $id, array $body): array {
        $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);

        $this->idParamSchema();
        $in = $this->schema([
            "helpful:s" => [
                "description" => "Article 'Was it Helpful?' reaction.",
                "enum" => ["yes", "no"],
            ],
            "insertUserID:i?" => [
                "description" => "User id."
            ],
            "foreignID:s?" => [
                "description" => "Foreign id."
            ],
            "responseToken:s?" => [
                "description" => "The response provided from reCaptcha."
            ],
        ], "in")->setDescription("Reaction about an article.");

        $out = $this->articleSchema("out");
        $body = $in->validate($body);

        // This is just check if article exists and knowledge base has status "published"
        $row = $this->articleByID($id, true);
        $validReaction = true;


        $insertUserID = $body['insertUserID'] ?? null;
        $isGuest = ($this->session->UserID === 0 || $insertUserID === 0);
        if ($isGuest) {
            $responseToken = $body["responseToken"] ?? false;
            $validReaction = $this->reCaptchaVerification->siteVerifyV3($responseToken);
        }

        if ($validReaction) {
            $reactionValue = array_search($body[ArticleReactionModel::TYPE_HELPFUL], ArticleReactionModel::getHelpfulReactions());
            $fields = ArticleReactionModel::getReactionFields($id, ArticleReactionModel::TYPE_HELPFUL, $reactionValue);

            $mode = $this->articleHelper->getOperationMode();
            if ($mode === Operation::MODE_DEFAULT) {
                $fields['insertUserID'] = $this->session->UserID ?? $body['insertUserID'];
                $fields['foreignID'] = '';
            } else {
                $fields['insertUserID'] = $body['insertUserID'] ?? $this->session->UserID;
                $fields['foreignID'] = $body['foreignID'] ?? '';
            }

            if (empty($fields['foreignID'])) {
                $existingReactionValue = $this->articleReactionModel->getUserReaction(
                    ArticleReactionModel::TYPE_HELPFUL,
                    $id,
                    $fields['insertUserID']
                );
            } else {
                $existingReactionValue = $this->articleReactionModel->getReactionByForeignID($fields['foreignID']);
            }

            if ($existingReactionValue !== null && $fields['insertUserID'] !== 0) {
                throw new ClientException('You already reacted on this article before.');
            }

            $fields['reactionOwnerID'] = $this->reactionOwnerModel->getReactionOwnerID($fields);

            $this->reactionModel->insert($fields, [Model::OPT_MODE => $mode]);

            $reactionCounts = $this->articleReactionModel->updateReactionCount($id);

            $row = $this->articleByID($id, true);

            $newReactionValue = $this->articleReactionModel->getUserReaction(
                ArticleReactionModel::TYPE_HELPFUL,
                $id,
                $fields['insertUserID']
            );

            $row['reactions'][] = [
                'reactionType' => ArticleReactionModel::TYPE_HELPFUL,
                'yes' => (int)$reactionCounts['positiveCount'],
                'no' => (int)$reactionCounts['neutralCount'],
                'total' => (int)$reactionCounts['allCount'],
                'userReaction' => $newReactionValue,
            ];
            $this->eventManager->fire("afterArticleReact", $row, $newReactionValue);
        } else {
            $articleReaction = $body["helpful"] ?? null;

            // fake counts if reCaptcha Challenge fails.
            $reactionCounts = $this->articleReactionModel->getReactionCount($id);
            $positiveCount = (int)$reactionCounts['positiveCount'] ?? 0;
            $neutralCount =  (int)$reactionCounts['neutralCount'] ?? 0;
            $total = (int)$reactionCounts['allCount'] ?? 0;

            if ($articleReaction) {
                $row['reactions'][] = [
                    'reactionType' => ArticleReactionModel::TYPE_HELPFUL,
                    'yes' =>  ($articleReaction === ArticleReactionModel::YES) ?
                        $positiveCount + 1 :
                        $positiveCount,
                    'no' => ($articleReaction === ArticleReactionModel::NO) ?
                        $neutralCount + 1 :
                        $neutralCount,
                    'total' => $total + 1,
                    'userReaction' => $articleReaction,
                ];
            }
        }

        $row['breadcrumbs'] = $this->breadcrumbModel->getForRecord(new KbCategoryRecordType($row['knowledgeCategoryID']));
        $row = $this->articleHelper->normalizeOutput($row);
        $result = $out->validate($row);

        return $result;
    }

    /**
     * Create a new article.
     *
     * @param array $body
     * @return Data
     * @throws \Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     * @throws ClientException If locale is not supported.
     */
    public function post(array $body): Data {
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);

        $in = $this->articlePostSchema("in")
            ->addValidator("knowledgeCategoryID", [$this->knowledgeCategoryModel, "validateKBArticlesLimit"])
            ->setDescription("Create a new article.");
        $out = $this->articleSchema("out");
        $body = $in->validate($body);

        $knowledgeBase = $this->articleHelper->getKnowledgeBaseFromCategoryID($body["knowledgeCategoryID"]);
        $this->knowledgeBaseModel->checkEditPermission($knowledgeBase['knowledgeBaseID']);
        $sourceLocale = $knowledgeBase["sourceLocale"] ?? c("Garden.Locale");

        if (array_key_exists("locale", $body) && isset($body["locale"])) {
            if ($body["locale"] !== $sourceLocale) {
                throw new ClientException("Articles must be created in {$sourceLocale} locale.");
            }
        } else {
            $body["locale"] = $sourceLocale;
        }

        [$body, $rehostResponseHeaders] = $this->articleHelper->rehostArticleImages($body);

        $articleID = $this->articleHelper->save($body);
        $row = $this->articleByID($articleID, true);
        $this->eventManager->fire("afterArticleCreate", $row);
        $row = $this->articleHelper->normalizeOutput($row);
        $crumbs = $this->breadcrumbModel->getForRecord(new KbCategoryRecordType($row['knowledgeCategoryID']));
        $row['breadcrumbs'] = $crumbs;
        $result = $out->validate($row);
        return new Data($result, [], $rehostResponseHeaders);
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

        $this->articleHelper->updateInvalidateArticleTranslations($id);

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

    /**
     * Check if the required fields are there for the first revision in a different locale.
     *
     * @param int $id
     * @param array $body
     * @return array
     */
    protected function validateFirstArticleRevision(int $id, array $body) {
        $revisions = $this->articleRevisionModel->get(["articleID" => $id]);
        $revisionForLocale = array_column($revisions, "locale");
        if (!in_array($body["locale"], $revisionForLocale)) {
            $firstRevisionSchema = $this->firstArticleRevisionPatchSchema("in")
                ->setDescription("Update an existing article.");
            $body = $firstRevisionSchema->validate($body);
        }
        return $body;
    }
}
