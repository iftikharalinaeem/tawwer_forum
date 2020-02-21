<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

/**
 * A model for managing knowledge bases.
 */
class KnowledgeUniversalSourceModel extends \Vanilla\Models\PipelineModel {

    /** @var KnowledgeBaseModel */
    private $knowledgeBaseModel;


    /**
     * KnowledgeUniversalSourceModel constructor.
     *
     * @param KnowledgeBaseModel $knowledgeBaseModel
     */
    public function __construct(
        KnowledgeBaseModel $knowledgeBaseModel
    ) {
        parent::__construct("knowledgeUniversalSource");
        $this->knowledgeBaseModel = $knowledgeBaseModel;
    }

}
