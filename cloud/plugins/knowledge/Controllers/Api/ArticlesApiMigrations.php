<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use Garden\Web\Exception\NotFoundException;
use Vanilla\Knowledge\Models\KbCategoryRecordType;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;

/**
 * API controller migration related endpoints
 */
trait ArticlesApiMigration {
    /**
     * Get a community discussion in a format that is easy to consume when creating a new article.
     *
     * @param array $query Request query.
     */
    public function index_fromDiscussion(array $query) {
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);

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
     * PUT article aliases.
     *
     * @param int $id ArticleID
     * @param array $body Incoming json array with 'aliases'.
     *
     * @return array Data array Article record/item

     */
    public function put_aliases(int $id, array $body): array {
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);

        $this->idParamSchema();
        $in = $this->schema([
            "aliases:a" => [
                "description" => "Article aliases list",
                "items" => ["type" => "string"]
            ],
        ], "in")
            ->addValidator("aliases", [ArticlesApiSchemes::class, 'validateAliases'])
            ->setDescription("Set article aliases.");
        $out = $this->articleSchema("out");
        $body = $in->validate($body);

        // This is just check if article exists and knowledge base has status "published"
        $article = $this->articleHelper->articleByID($id);

        $aliases = array_unique($body['aliases']);

        $existingAliases = $this->pageRouteAliasModel->getAliases(
            ArticleModel::RECORD_TYPE,
            $id
        );
        foreach ($aliases as $alias) {
            if ($exists = array_search($alias, $existingAliases)) {
                unset($existingAliases[$exists]);
            } else {
                $this->pageRouteAliasModel->addAlias(
                    ArticleModel::RECORD_TYPE,
                    $id,
                    $alias
                );
            }
        }
        if (count($existingAliases) > 0) {
            $this->pageRouteAliasModel->dropAliases(
                ArticleModel::RECORD_TYPE,
                $id,
                $existingAliases
            );
        }


        $row = $this->articleHelper->articleByID($id, true);

        $row['breadcrumbs'] =$this->breadcrumbModel->getForRecord(new KbCategoryRecordType($row['knowledgeCategoryID']));
        $row['aliases']  = $this->pageRouteAliasModel->getAliases(
            ArticleModel::RECORD_TYPE,
            $id,
            true
        );
        $row = $this->articleHelper->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get article aliases.
     *
     * @param int $id ArticleID
     *
     * @return array Data array Article record/item

     */
    public function get_aliases(int $id): array {
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);

        $this->idParamSchema();
        $in = $this->schema([], "in")->setDescription("Get article aliases.");
        $out = $this->articleAliasesSchema("out");

        $row = $this->articleHelper->articleByID($id, true);

        $row['aliases']  = $this->pageRouteAliasModel->getAliases(
            ArticleModel::RECORD_TYPE,
            $id,
            true
        );
        $row = $this->articleHelper->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get article by its alias.
     *
     * @param array $query Query should have one mandatory argument: alias
     *
     * @return array Data array Article record/item

     */
    public function get_byAlias(array $query): array {
        $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);

        $in = $this->schema([
            "alias" => [
                "type" => "string",
            ],
        ], "in")->setDescription("Get article by its alias.");
        $out = $this->articleSchema("out");
        $query = $in->validate($query);
        $alias = $encoded = implode('/', array_map(
            function ($str) {
                return rawurlencode(rawurldecode($str));
            },
            explode('/', $query['alias'])
        ));

        try {
            $articleID = $this->pageRouteAliasModel->getRecordID(ArticleModel::RECORD_TYPE, $alias);
        } catch (NoResultsException $e) {
            throw new NotFoundException("Article with alias: ".$query['alias'].' not found.');
        }

        return $this->get($articleID);
    }
}
