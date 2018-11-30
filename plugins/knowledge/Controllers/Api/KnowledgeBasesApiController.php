<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Knowledge\Models\Entities\KnowledgeBaseEntity;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Controllers\Api\KnowledgeBasesApiSchemes;

/**
 * Endpoint for the knowledge category resource.
 */
class KnowledgeBasesApiController extends AbstractApiController {
    use KnowledgeBasesApiSchemes;

    /** @var KnowledgeCategoryModel */
    private $knowledgeBaseModel;

    /**
     * KnowledgeBaseApiController constructor.
     *
     * @param KnowledgeBaseModel $knowledgeBaseModel
     */
    public function __construct(KnowledgeBaseModel $knowledgeBaseModel) {
        $this->knowledgeBaseModel = $knowledgeBaseModel;
    }


    /**
     * Get a single knowledge category.
     *
     * @param int $id
     * @return array
     */
    public function get(int $id): array {
        $this->permission("knowledge.kb.view");
        $this->idParamSchema()->setDescription("Get a single knowledge base.");
        $out = $this->schema($this->fullSchema(), "out");

        $row = $this->knowledgeBaseByID($id);
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
        $result = $out->validate($rows);
        return $result;
    }

    public function post(array $body): array {
        $this->permission("garden.setttings.manage");

        $in = $this->schema($this->knowledgeBasePostSchema())
            ->setDescription("Create a new knowledge base.");
        $out = $this->schema($this->fullSchema(), "out");
        $body = $in->validate($body);

        $entity = new KnowledgeBaseEntity($body);
        $knowledgeBaseID = $this->knowledgeBaseModel->insert($entity->asArray());

        $row = $this->knowledgeBaseByID($knowledgeBaseID);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get a knowledge category for editing.
     *
     * @param int $id
     * @return array
     */
    public function get_edit(int $id): array {
        $this->permission("garden.settings.manage");

        $this->idParamSchema()->setDescription("Get a knowledge base for editing.");
        $out = $this->schema(Schema::parse([
            'knowledgeBaseID',
            'name',
            'description',
            'type',
            'icon',
            'sortArticles',
            'sourceLocale',
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
        $this->permission("garden.setttings.manage");

        $this->idParamSchema();
        $in = $this->schema($this->knowledgeBasePostSchema())
            ->setDescription("Update an existing knowledge base.");
        $out = $this->schema($this->fullSchema(), "out");

        $body = $in->validate($body, true);

        $this->knowledgeBaseModel->update($body, ["knowledgeBaseID" => $id]);

        $row = $this->knowledgeBaseByID($id);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Delete a knowledge base.
     *
     * @param int $id
     * @throws ValidationException If output validation fails while getting the knowledge category.
     * @throws \Garden\Web\Exception\HttpException If a ban has been applied on the permission(s) for this session.
     * @throws \Garden\Web\Exception\NotFoundException If the knowledge category could not be found.
     * @throws \Vanilla\Exception\PermissionException If the user does not have the specified permission(s).
     * @throws \Garden\Web\Exception\ClientException If the target knowledge category is not empty.
     */
    public function delete(int $id) {
        $this->permission("garden.setttings.manage");

        $this->idParamSchema()->setDescription("Delete a knowledge base.");
        $this->schema([], "out");

        $row = $this->knowledgeBaseByID($id);
        $rootCategory = $row['rootCategoryID'];
        if ($row["articleCount"] < 1 && $row["childCategoryCount"] < 1) {
            $this->knowledgeCategoryModel->delete(["knowledgeCategoryID" => $row["knowledgeCategoryID"]]);
            if (!empty($row['parentID']) && ($row['parentID'] !== -1)) {
                $this->knowledgeCategoryModel->updateCounts($row['parentID']);
            }
        } else {
            throw new \Garden\Web\Exception\ClientException("Knowledge category is not empty.", 409);
        }
    }

    /**
     * Get a single knowledge base by its ID.
     *
     * @param int $knowledgeBaseID
     * @return array
     * @throws \Garden\Web\Exception\NotFoundException If the knowledge base could not be found.
     */
    public function knowledgeBaseByID(int $knowledgeBaseID): array {
        try {
            $result = $this->knowledgeBaseModel->selectSingle(["knowledgeBaseID" => $knowledgeBaseID]);
        } catch (\Vanilla\Exception\Database\NoResultsException $e) {
            throw new \Garden\Web\Exception\NotFoundException('Knowledge Base with ID: '.$knowledgeBaseID.' not found!');
        }
        return $result;
    }
}
