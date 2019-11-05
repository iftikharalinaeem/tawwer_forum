<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\TranslationsAPI\models;

use Garden\Web\Exception\ClientException;
use Gdn_Session;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Models\PipelineModel;

/**
 *
 */
class resourceModel extends PipelineModel {

    /** @var Gdn_Session */
    private $session;

    /** @var string */
    private $resource;

    /** @var string */
    private $recordType;

    /** @var int */
    private $recordID;

    /** @var string */
    private $recordKey;

    /** @var string */
    private $key;


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
     * @param string $resource
     * @param string $recordType
     * @throws ClientException
     */
    public function ensureResourceExists(string $resource, string $recordType) {
        try {
            $this->selectSingle(
                [
                    "url" => $resource,
                    "name" => $recordType
                ]
            );
        } catch (NoResultsException $e) {
            throw new ClientException("Resource doesn't exist");
        }
    }


}
