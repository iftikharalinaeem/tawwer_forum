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
use Vanilla\Knowledge\Models\KnowledgeBaseModel;

/**
 * A model for managing fetaured field of ArticleModel.
 */
class ArticleFeaturedModel extends \Vanilla\Models\LimitedPipelineModel {
    const RECORD_TYPE = 'article';
    protected $operations = ["update"];

    /** @var Gdn_Session */
    private $session;

    /**
     * ArticleFeaturedModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct("article");
        $this->session = $session;

        $dateProcessor = new Operation\CurrentDateFieldProcessor();
        $dateProcessor->setUpdateFields(["dateFeatured"]);
        $this->addPipelineProcessor($dateProcessor);

        $this->writeSchema = Schema::parse([
            "featured" => [
                'allowNull' => false,
                'required' => true,
                'type' => 'integer',
            ],
            "dateFeatured" => [
                'allowNull' => false,
                'required' => true,
                'type' => 'datetime',
            ]
        ]);
    }
}
