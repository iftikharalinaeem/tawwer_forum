<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Gdn_Session;

/**
 * A model for managing knowledge bases.
 */
class DiscussionModel extends \Vanilla\Models\PipelineModel {
    /** @var Gdn_Session */
    private $session;

    /**
     * DiscussionModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct("Discussion");
        $this->session = $session;
    }
}
