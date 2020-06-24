<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\TranslationsApi\Models;

use Garden\Web\Exception\ClientException;
use Gdn_Session;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Models\PipelineModel;

/**
 * ResourceModel
 */
class ResourceModel extends PipelineModel {

    /** @var Gdn_Session */
    private $session;

    /**
     * ResourceModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct("resource");
        $this->session = $session;

        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])
            ->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])
            ->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Ensure that a resource exists.
     *
     * @param string $resource
     * @throws ClientException
     */
    public function ensureResourceExists(string $resource) {
        try {
            $this->selectSingle(
                [
                    "urlCode" => $resource,
                ]
            );
        } catch (NoResultsException $e) {
            throw new ClientException("The '". $resource."' resource doesn't exist");
        }
    }
}
