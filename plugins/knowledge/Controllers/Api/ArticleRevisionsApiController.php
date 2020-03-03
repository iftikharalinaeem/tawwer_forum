<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use UserModel;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Formatting\Exception\FormattingException;
use Vanilla\Knowledge\Models\ArticleRevisionModel;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\ArticleDraft;
use Vanilla\Formatting\Quill\Parser;
use Vanilla\Formatting\FormatService;
use Vanilla\Formatting\Formats;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;

/**
 * API controller for managing the article revisions resource.
 *
 * This API controller currently exists here instead of as a subresource on the articles controller
 * due to limitations of our routing system. There is currently no way to differentiate
 * - /articles/revisions/:revisionID
 * - /articles/:articleID/revisions
 * As a result we have to do /article-revisions/:id
 * @see https://github.com/vanilla/knowledge/issues/264
 */
class ArticleRevisionsApiController extends AbstractKnowledgeApiController {
    use CheckGlobalPermissionTrait;

    /** The maximum limit of revisions that can be re-rendered at a time. */
    const LIMIT = 1000;

    /** The default re-render starting point. */
    const OFFSET = 0;

    /** @var ArticleModel */
    private $articleModel;

    /** @var ArticleRevisionModel */
    private $articleRevisionModel;

    /** @var Schema */
    private $articleRevisionSchema;

    /** @var Schema */
    private $idParamSchema;

    /** @var Schema */
    private $reRenderSchema;

    /** @var UserModel */
    private $userModel;

    /** @var FormatService */
    private $formatService;

    /**
     * ArticleRevisionsApiController constructor.
     *
     * @param ArticleRevisionModel $articleRevisionModel
     * @param ArticleModel $articleModel
     * @param UserModel $userModel
     * @param FormatService $formatService
     */
    public function __construct(
        ArticleRevisionModel $articleRevisionModel,
        ArticleModel $articleModel,
        UserModel $userModel,
        FormatService $formatService
    ) {
        $this->articleRevisionModel = $articleRevisionModel;
        $this->articleModel = $articleModel;
        $this->userModel = $userModel;
        $this->formatService = $formatService;
    }

    /**
     * Get an article by its numeric ID.
     *
     * @param int $id Article ID.
     * @return array
     * @throws NotFoundException If the article revision could not be found.
     * @throws ValidationException If a fetched row fails to validate against the article schema.
     */
    private function articleRevisionByID(int $id): array {
        $resultSet = $this->articleRevisionModel->get(["articleRevisionID" => $id], ["limit" => 1]);
        if (empty($resultSet)) {
            throw new NotFoundException("Article Revision");
        }
        $row = reset($resultSet);
        return $row;
    }

    /**
     * Get the full schema for an article revision.
     *
     * @param string $type
     * @return Schema
     */
    public function articleRevisionSchema(string $type = ""): Schema {
        if ($this->articleRevisionSchema === null) {
            $this->articleRevisionSchema = $this->schema(Schema::parse([
                "articleRevisionID",
                "articleID",
                "status",
                "name",
                "body",
                "bodyRendered",
                "locale",
                "insertUserID",
                "dateInserted",
                "insertUser?",
            ])->add($this->fullSchema()), "ArticleRevision");
        }
        return $this->schema($this->articleRevisionSchema, $type);
    }

    /**
     * Get the schema for the reRender.
     *
     * @param string $type
     * @return Schema
     */
    public function reRenderSchema(string $type = ""): Schema {
        if ($this->reRenderSchema === null) {
            $this->reRenderSchema = $this->schema(Schema::parse([
                "processed",
                "errorCount",
                "firstArticleRevisionID",
                "lastArticleRevisionID",
                "errors" => [
                    "items" => [
                        "properties" => [
                            "articleRevisionID" => [
                                "type" => "integer",
                                "description" => "Article revision ID ",
                            ],
                            "errorMessage" => [
                                "type" => "string",
                                "description" => "Error message thrown when re-rendering the revision",
                            ]
                        ]
                    ]
                ]
            ]));
        }
        return $this->schema($this->reRenderSchema, $type);
    }

    /**
     * Get a schema representing an article revision.
     *
     * @return Schema
     */
    private function fullSchema(): Schema {
        return Schema::parse([
            "articleRevisionID:i" => "Unique article revision ID.",
            "articleID:i" => "Associated article ID.",
            "status:s" => [
                "allowNull" => true,
                "description" => "",
                "enum" => ["published"],
            ],
            "name:s" => [
                "allowNull" => true,
                "description" => "Title of the article.",
            ],
            "format:s" => [
                "allowNull" => true,
                "enum" => ["text", "textex", "markdown", "wysiwyg", "html", "bbcode", "rich"],
                "description" => "Format of the raw body content.",
            ],
            "body:s" => [
                "allowNull" => true,
                "description" => "Body contents.",
            ],
            "bodyRendered:s" => [
                "allowNull" => true,
                "description" => "Rendered body contents.",
            ],
            "locale:s" => [
                "allowNull" => true,
                "description" => "Locale the article was written in.",
            ],
            "insertUserID:i" => "Unique ID of the user who originally created the article.",
            "dateInserted:dt" => "When the article was created.",
            "insertUser?" => $this->getUserFragmentSchema(),
        ]);
    }

    /**
     * Get an article revision by its unique ID.
     *
     * @param int|string $id
     * @return array
     * @throws NotFoundException If the article revision could not be located.
     * @throws ValidationException If input or output fail schema validation.
     * @throws \Garden\Web\Exception\HttpException If a permission ban applies to this user ("Access denied").
     * @throws \Vanilla\Exception\PermissionException If the current user does not have sufficient permissions.
     */
    public function get($id): array {
        $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);

        $this->idParamSchema()->setDescription("Get an article revision.");
        $out = $this->articleRevisionSchema("out");

        $row = $this->articleRevisionByID($id);
        $row = $this->normalizeOutput($row);
        $this->userModel->expandUsers(
            $row,
            ["insertUserID"]
        );
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get an ID-only article revision schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    private function idParamSchema(string $type = "in"): Schema {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse(["id:i" => "The article revision ID."]),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * Massage article row data for useful API output.
     *
     * @param array $row
     * @return array
     */
    private function normalizeOutput(array $row): array {
        return $row;
    }

    /**
     * ReRender content in the articleRevision Table (bodyRendered, format, plainText, excerpt, outline).
     *
     * @param array $body A specific id to rerender.
     * @return array $results The number of records processed.
     */
    public function patch_reRender(array $body = []): array {
        $this->permission("Garden.Settings.Manage");

        $in = $this->schema(
            Schema::parse([
                "articleID:i?" => "The article ID.",
                "limit:i?" => [
                    "Description" => "The desired number of revisions to process.",
                    'default' => self::LIMIT,
                    'minimum' => 1,
                    'maximum' => self::LIMIT
                ],
                "offset:i?" => "The number revisions to exclude."
                ]),
            "in"
        );

        $body = $in->validate($body);

        $where = [];
        if (!empty($body["articleID"] ?? null)) {
            $where = ["articleID" => $body["articleID"]];
        }

        $limit = $body["limit"] ?? self::LIMIT;
        $offset = $body["offset"] ?? self::OFFSET;

        $options = ["limit" => $limit, "offset" => $offset];

        $revisions = $this->articleRevisionModel->get($where, $options);
        $processed = 0;
        $notProcessed = 0;

        $firstRevision = null;
        $lastRevision = null;
        $errors =[];

        foreach ($revisions as $revision) {
            $updateRev = [];

            try {
                $revision["body"] = $this->formatService->filter($revision["body"], $revision["format"]);
                $updateRev["bodyRendered"] = $this->formatService->renderHTML($revision["body"], $revision["format"]);

                if ($revision["format"] === "rich") {
                    $plainText = $this->formatService->renderPlainText($revision["body"], $revision["format"]);
                    $updateRev["plainText"] = $plainText;
                    $updateRev["excerpt"] =  $this->formatService->renderExcerpt($revision["body"], $revision["format"]);
                    $updateRev["outline"] = json_encode($this->formatService->parseHeadings($revision["body"], $revision["format"]));
                }

                $this->articleRevisionModel->update($updateRev, ["articleRevisionID" => $revision["articleRevisionID"]]);
                $processed++;
            } catch (FormattingException $e) {
                $errors[] = [
                        "articleRevisionID" => $revision["articleRevisionID"],
                        "errorMessage" => $e->getMessage()
                    ];
                $notProcessed++;
            }
            $firstRevision = $firstRevision ?? $revision["articleRevisionID"];
            $lastRevision = $revision["articleRevisionID"];
        }

        $records = [
            "processed" => $processed,
            "errorCount" => $notProcessed,
            "firstArticleRevisionID" => $firstRevision,
            "lastArticleRevisionID" => $lastRevision,
            "errors" => $errors
        ];

        $out = $this->reRenderSchema("out");
        $result = $out->validate($records);

        return $result;
    }
}
