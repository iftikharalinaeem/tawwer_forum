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

/**
 * Endpoint for the knowledge category resource.
 */
class KnowledgeBasesApiController extends AbstractApiController {

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
            ->setDescription("Create a new knowledge category.")
            ->addValidator("parentID", [$this->knowledgeCategoryModel, "validateParentID"]);
        $out = $this->schema($this->fullSchema(), "out");

        $body = $in->validate($body);

        $knowledgeCategoryID = $this->knowledgeCategoryModel->insert($body);
        if (!empty($body['parentID']) && $body['parentID'] != -1) {
            $this->knowledgeCategoryModel->updateCounts($body['parentID']);
        }
        $row = $this->knowledgeCategoryByID($knowledgeCategoryID);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }
}
