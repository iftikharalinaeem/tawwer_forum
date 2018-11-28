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
class KnowledgeBaseApiController extends AbstractApiController {

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




}
