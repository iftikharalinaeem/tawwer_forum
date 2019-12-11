<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Schema\ValidationException;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Models\PipelineModel;

/**
 * Class WebhookModel
 */
class WebhookModel extends PipelineModel {

    /**
     * WebhookModel constructor.
     */
    public function __construct() {
        parent::__construct('webhook');
    }

    /**
     * Get a single webhook by ID.
     *
     * @param int $webhookID Unique ID of the webhook.
     * @return array SQL result.
     * @throws ValidationException If the result fails schema validation.
     * @throws NoResultsException If the webhook could not be found.
     */
    public function getID(int $webhookID): array {
        return $this->selectSingle(["webhookID" => $webhookID]);
    }

    /**
     * Get all webhooks data.
     *
     * @return mixed|null
     */
    public function webhooks(array $where = []) {
       return $this->get($where);
    }

    /**
     * Save a webhook returns the webhook id.
     *
     * @param array $formPostValues The data to save.
     * @return int|bool ID of the inserted row, or true on update.
     * @throws Exception If an error is encountered while performing the query.
     */
    public function save($formPostValues) {
        if (isset($formPostValues['active'])) {
            $formPostValues['active'] = $formPostValues['active'] ? 1 : 0;
        }
        $webhookID = $formPostValues['webhookID'] ?? false;
        $insert = !$webhookID;

        if ($insert) {
            // Save the webhook record.
            $webhook = [
                'active' => $formPostValues['active'],
                'events' => $formPostValues['events'],
                'name' => $formPostValues['name'],
                'url' =>  $formPostValues['url'],
                'secret' => $formPostValues['secret']
            ];
            $webhookID = $this->insert($webhook);
        } else {
            $webhookID = $this->update($formPostValues, ['webhookID' => $webhookID]);
        }
        return $webhookID;
    }

    /**
     * Delete a webhook.
     *
     * @param int $webhookID Unique ID of the webhook to be deleted.
     * @param array $options Additional options for the delete.
     * @return bool Always returns TRUE.
     */
    public function deleteID($webhookID, $options = []) {
        $webhook = $this->getID($webhookID);
        if (!$webhook) {
            return false;
        }
        // Delete the webhook.
        $this->delete(['webhookID' => $webhookID], $options);
        return true;
    }
}
