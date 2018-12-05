<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Vanilla\Knowledge\Models\Entities\KnowledgeBaseEntity;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;

/**
 * Endpoint for the knowledge base resource.
 */
class KnowledgeBasesApiController extends AbstractApiController {
    use KnowledgeBasesApiSchemes;

    /** @var KnowledgeBaseModel */
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

    /**
     * POST new Knowledge Base
     *
     * @param array $body
     * @return array
     */
    public function post(array $body): array {
        $this->permission("garden.setttings.manage");

        $in = $this->schema($this->knowledgeBasePostSchema())
            ->setDescription("Create a new knowledge base.");
        $out = $this->schema($this->fullSchema(), "out");
        $body = $in->validate($body);

        //$entity = new KnowledgeBaseEntity($body);
        $knowledgeBaseID = $this->knowledgeBaseModel->insert($entity->asArray('insert'));

        $row = $this->knowledgeBaseByID($knowledgeBaseID);
       // die(print_r($row));
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
        $this->permission("garden.settings.manage");

        $this->idParamSchema()->setDescription("Get a knowledge base for editing.");
        $out = $this->schema(Schema::parse([
            'knowledgeBaseID',
            'name',
            'description',
            'viewType',
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
     * @throws ValidationException If output validation fails while getting the knowledge base.
     * @throws \Garden\Web\Exception\ClientException If the root knowledge category is not empty.
     */
    public function delete(int $id) {
        $this->permission("garden.setttings.manage");

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
