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


        return [__CLASS__ => __METHOD__];
    }

//    public function index(): array {
//        $this->permission("knowledge.kb.view");
//
//
//        return [__CLASS__ => __METHOD__];
//    }

    public function post(array $body): array {
        $this->permission("garden.setttings.manage");

        $in = $this->schema($this->knowledgeBasePostSchema())
            ->setDescription("Create a new knowledge base.");
        $out = $this->schema($this->fullSchema(), "out");

        $body = $in->validate($body);

        $knowledgeBaseID = $this->knowledgeBaseModel->insert($body);

        $row = $this->knowledgeBaseByID($knowledgeBaseID);
        //$row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get a single knowledge base by its ID.
     *
     * @param int $knowledgeBaseID
     * @return array
     * @throws \Garden\Web\Exception\NotFoundException If the knowledge base could not be found.
     * @throws ValidationException If the knowledge base row fails validating against the model's output schema.
     */
    public function knowledgeBaseByID(int $knowledgeBaseID): array {
        try {
            $result = $this->knowledgeBaseModel->selectSingle(["knowledgeCategoryID" => $knowledgeBaseID]);
        } catch (\Vanilla\Exception\Database\NoResultsException $e) {
            throw new \Garden\Web\Exception\NotFoundException('Knowledge Base with ID: '.$knowledgeBaseID.' not found!');
        }
        return $result;
    }
}
