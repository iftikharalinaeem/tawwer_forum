<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Knowledge\Models;

/**
 * A model for managing articles.
 */
class ArticleModel extends \Vanilla\Models\Model {

    /**
     * ArticleModel constructor.
     */
    public function __construct() {
        parent::__construct('article');
    }
}
