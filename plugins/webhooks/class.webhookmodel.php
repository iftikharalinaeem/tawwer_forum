<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Class WebhookModel
 */
class WebhookModel extends GDN_Model {

    /**
     * WebhookModel constructor.
     */
    public function __construct() {
        parent::__construct('webhook');
    }

    /**
     * Get a single webhook by ID.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $webhookID Unique ID of the webhook.
     * @param string $dataSetType Format to return webhook in.
     * @param array $options options to pass to the database.
     * @return mixed SQL result in format specified by $dataSetType.
     */
    public function getID($webhookID, $dataSetType = DATASET_TYPE_OBJECT, $options = []) {
        $this->options($options);
        $webhook = $this->SQL
            ->select('w.*')
            ->select('w.active, w.name, w.events, w.url, w.secret, w.dateInserted, w.insertUserID, w.dateUpdated, w.updateUserID')
            ->from('webhook w')
            ->where('w.webhookID', $webhookID)
            ->get()
            ->firstRow();

        if (!$webhook) {
            return $webhook;
        }
        return $dataSetType == DATASET_TYPE_ARRAY ? (array)$webhook : $webhook;
    }

    /**
     * Get all ranks data.
     *
     * @return mixed|null
     */
    public static function webhooks() {
        return $webhooks = Gdn::sql()->get('webhook')->resultArray();
    }
    /**
     * Save a webhook returns the webhook id.
     *
     * @param array $formPostValues The data to save.
     * @param array $settings
     * @return int|false Returns the ID of the webhook or false on error.
     */
    public function save($formPostValues, $settings = []) {
        $formPostValues = $this->filterForm($formPostValues);
        $webhookID = $formPostValues['webhookID'] ?? false;
        $insert = !$webhookID;

        if ($insert) {
            $this->addInsertFields($formPostValues);
        } else {
            $this->addUpdateFields($formPostValues);
        }

        // Define the primary key in this model's table.
        $this->defineSchema();
        $this->validate($formPostValues, $insert);

        if (count($this->Validation->results()) == 0) {
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
                $this->update($formPostValues, ['webhookID' => $webhookID]);
            }
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
        $webhook = $this->getID($webhookID, DATASET_TYPE_ARRAY);
        if (!$webhook) {
            return false;
        }
        // Log the deletion.
//        $log = isset($options['log']) ? $options['log'] : 'Delete';
//        LogModel::insert($log, 'webhook', $webhook, isset($options['log']) ? $options['log'] : []);

        // Delete the webhook.
        $this->SQL->delete('webhook', ['webhookID' => $webhookID]);
        return true;
    }
}
