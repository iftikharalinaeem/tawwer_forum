<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ServerException;
use Gdn_Session;
use Vanilla\Database\Operation;
use Vanilla\Exception\Database\NoResultsException;

/**
 * A model for managing fetaured field of ArticleModel.
 */
class DefaultArticleModel extends \Vanilla\Models\LimitedPipelineModel {
    const RECORD_TYPE = 'knowledgeBase';
    protected $operations = ["update"];

    /** @var Gdn_Session */
    private $session;

    /**
     * ArticleFeaturedModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct("knowledgeBase");
        $this->session = $session;

        $this->writeSchema = Schema::parse([
            "defaultArticleID" => [
                'allowNull' => true,
                'required' => true,
                'type' => 'integer',
            ],
        ]);
    }
}
