<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

/**
 * A model for managing knowledge categories.
 */
class KnowledgeCategoryModel extends \Vanilla\Models\Model {

    /**
     * KnowledgeCategoryModel constructor.
     */
    public function __construct() {
        parent::__construct('knowledgeCategory');
    }
}
