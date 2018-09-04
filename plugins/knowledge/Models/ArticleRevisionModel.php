<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Knowledge\Models;

/**
 * A model for managing article revisions.
 */
class ArticleRevisionModel extends \Vanilla\Models\Model {

    /**
     * ArticleModel constructor.
     */
    public function __construct() {
        parent::__construct('articleRevision');
    }
}
