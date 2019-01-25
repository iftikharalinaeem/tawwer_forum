<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;

/**
 * Endpoint for the knowledge base resource.
 */
class KnowledgeBasesApiController extends AbstractApiController {
    use KnowledgeBasesApiSchemes;

    /** @var KnowledgeBaseModel */
    private $knowledgeBaseModel;

    /** @var KnowledgeNavigationApiController */
    private $knowledgeNavigationApi;

    /** @var KnowledgeCategoryModel */
    private $knowledgeCategoryModel;

    /**
     * KnowledgeBaseApiController constructor.
     *
     * @param KnowledgeBaseModel $knowledgeBaseModel
     * @param KnowledgeNavigationApiController $knowledgeNavigationApi
     * @param KnowledgeCategoryModel $knowledgeCategoryModel
     */
    public function __construct(
        KnowledgeBaseModel $knowledgeBaseModel,
        KnowledgeNavigationApiController $knowledgeNavigationApi,
        KnowledgeCategoryModel $knowledgeCategoryModel
    ) {
        $this->knowledgeBaseModel = $knowledgeBaseModel;
        $this->knowledgeNavigationApi = $knowledgeNavigationApi;
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
    }

    /**
     * Get a single knowledge base.
     *
     * @param int $id
     * @return array
     */
    public function get(int $id): array {
        $this->permission("knowledge.kb.view");
        $this->idParamSchema()->setDescription("Get a single knowledge base.");
        $out = $this->schema($this->fullSchema(), "out");

        $row = $this->knowledgeBaseByID($id);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);

        return $result;
    }

    /**
     * Get a knowledge base by it's URL code.
     *
     * @param array $query
     *
     * @return mixed
     * @throws NotFoundException If the knowledge base could not be found.
     * @throws ValidationException If the input or output was invalid.
     * @throws \Garden\Web\Exception\HttpException If the user has been banned.
     * @throws \Vanilla\Exception\PermissionException If the user did not have proper permission to view the resource.
     */
    public function get_byUrlCode(array $query) {
        $this->permission('knowledge.kb.view');

        // Schema
        $in = $this->schema(Schema::parse([
            'urlCode',
        ])->add($this->fullSchema()), "in")->setDescription('Get a knowledge base, using its urlCode.');
        $out = $this->schema($this->fullSchema(), 'out');
        $query = $in->validate($query);

        // Data fetching
        $urlCode = $query['urlCode'];
        $row = $this->knowledgeBaseModel->get(['urlCode' => $urlCode])[0] ?? null;
        if (!$row) {
            throw new NotFoundException('KnowledgeBase');
        }

        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);

        return $result;
    }

    /**
     * List knowledge bases.
     *
     * @return array
     */
    public function index(): array {
        $this->permission("knowledge.kb.view");

        $in = $this->schema([])->setDescription("List knowledge bases.");
        $out = $this->schema([":a" => $this->fullSchema()], "out");

        $rows = $this->knowledgeBaseModel->get();
        $rows = array_map(function ($row) {
            return $this->normalizeOutput($row);
        }, $rows);
        $result = $out->validate($rows);

        return $result;
    }

    /**
     * POST new Knowledge Base
     *
     * @param array $body
     * @return array
     */
    public function post(array $body): array {
        $this->permission("Garden.Settings.Manage");

        $in = $this->schema($this->knowledgeBasePostSchema())
            ->setDescription("Create a new knowledge base.")
        ;
        $in = $this->applyUrlCodeValidator($in);
        $out = $this->schema($this->fullSchema(), "out");
        $body = $in->validate($body);
        $knowledgeBaseID = $this->knowledgeBaseModel->insert($body);
        $knowledgeCategoryID = $this->knowledgeCategoryModel->insert([
            'name' => $body['name'],
            'knowledgeBaseID' => $knowledgeBaseID,
            'parentID' => -1,
        ]);
        $this->knowledgeBaseModel->update(['rootCategoryID' => $knowledgeCategoryID], ['knowledgeBaseID' => $knowledgeBaseID]);

        $row = $this->knowledgeBaseByID($knowledgeBaseID);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);

        return $result;
    }

    /**
     * Get a knowledge base for editing.
     *
     * @param int $id
     * @return array
     */
    public function get_edit(int $id): array {
        $this->permission("Garden.Settings.Manage");

        $this->idParamSchema()->setDescription("Get a knowledge base for editing.");
        $out = $this->schema(Schema::parse([
            'knowledgeBaseID',
            'name',
            'description',
            'viewType',
            'icon',
            'sortArticles',
            'sourceLocale',
            'urlCode',
        ])->add($this->fullSchema()), "out");

        $row = $this->knowledgeBaseByID($id);
        $result = $out->validate($row);

        return $result;
    }

    /**
     * Update an existing knowledge base.
     *
     * @param int $id
     * @param array $body
     * @return array
     */
    public function patch(int $id, array $body = []): array {
        $this->permission("Garden.Settings.Manage");

        $this->idParamSchema();
        $in = $this->schema($this->knowledgeBasePostSchema())
            ->setDescription("Update an existing knowledge base.")
        ;
        $in = $this->applyUrlCodeValidator($in, $id);

        $out = $this->schema($this->fullSchema(), "out");

        $body = $in->validate($body, true);

        $prevState = $this->knowledgeBaseByID($id);
        $this->knowledgeBaseModel->update($body, ["knowledgeBaseID" => $id]);
        if (isset($body['name']) && $prevState['name'] !== $body['name']) {
            $this->knowledgeCategoryModel->update(
                ['name' => $body['name']],
                ['knowledgeCategoryID' => $prevState['rootCategoryID']]
            );
        }

        $row = $this->knowledgeBaseByID($id);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);

        return $result;
    }

    /**
     * Apply {@link KnowledgeBasedApiController::validateUniqueUrlCode} to a Schema object.
     *
     * @param Schema $schema The schema to apply to.
     * @param int|null $recordID The existing ID of the current record if applicable.
     *
     * @return Schema
     */
    private function applyUrlCodeValidator(Schema $schema, int $recordID = null) {
        return $schema->addValidator(
            'urlCode',
            function (string $urlCode, ValidationField $validationField) use ($recordID) {
                return $this->validateUniqueUrlCode($urlCode, $validationField, $recordID);
            }
        );
    }

    /**
     * Validate that a url code is unique.
     *
     * @param string $urlCode The code to check.
     * @param ValidationField $validationField The validation field to apply errors to.
     * @param int|null $recordID The existing ID of the current record if applicable.
     *
     * @return bool Whether or not the url code passed validation.
     */
    private function validateUniqueUrlCode(string $urlCode, ValidationField $validationField, int $recordID = null): bool {
        $existingRow = $this->knowledgeBaseModel->get(['urlCode' => $urlCode])[0] ?? null;
        if ($existingRow && $existingRow['knowledgeBaseID'] !== $recordID) {
            $validationField->addError('The specified URL code is already in use by another knowledge base.');

            return false;
        }

        return true;
    }

    /**
     * Delete a knowledge base.
     *
     * @param int $id
     * @throws ValidationException If output validation fails while getting the knowledge base.
     * @throws \Garden\Web\Exception\ClientException If the root knowledge category is not empty.
     */
    public function delete(int $id) {
        $this->permission("Garden.Settings.Manage");

        $this->idParamSchema()->setDescription("Delete a knowledge base.");
        $this->schema([], "out");

        $row = $this->knowledgeBaseByID($id);

        if ($row["countArticles"] < 1 && $row["countCategories"] <= 1) {
            $this->knowledgeBaseModel->delete(["knowledgeBaseID" => $row["knowledgeBaseID"]]);
        } else {
            throw new \Garden\Web\Exception\ClientException("Knowledge base is not empty.", 409);
        }
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

        if ($result['viewType'] === KnowledgeBaseModel::TYPE_GUIDE) {
            $result['defaultArticleID'] = $this->knowledgeNavigationApi->getDefaultArticleID($knowledgeBaseID);
        } else {
            $result['defaultArticleID'] = null;
        }
        return $result;
    }

    /**
     * Normalize output.
     *
     * @param array $record A single knowledge base record.
     *
     * @return array
     */
    private function normalizeOutput(array $record): array {
        if (!isset($record['defaultArticleID'])) {
            if ($record['viewType'] === KnowledgeBaseModel::TYPE_GUIDE) {
                $record['defaultArticleID'] = $this->knowledgeNavigationApi->getDefaultArticleID($record['knowledgeBaseID']);
            } else {
                $record['defaultArticleID'] = null;
            }
        }
        $record['url'] = $this->knowledgeBaseModel->url($record, true);
        return $record;
    }
}
