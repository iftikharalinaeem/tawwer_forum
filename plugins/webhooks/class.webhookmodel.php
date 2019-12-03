<?php

class WebhookModel extends GDN_Model {

    public function __construct() {
        parent::__construct('Webhook');
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
    public function getID($webhookID,  $dataSetType = DATASET_TYPE_OBJECT, $options = []) {
        $this->options($options);
        $webhook = $this->SQL
            ->select('w.*')
            ->select('w.Active, w.Name, w.Events, w.Url, w.Secret, w.DateInserted, w.InsertUserID, w.DateUpdated, w.UpdateUserID')
            ->from('Webhook w')
            ->where('w.webhookID', $webhookID)
            ->get()
            ->firstRow();

        if (!$webhook) {
            return $webhook;
        }
        return $dataSetType == DATASET_TYPE_ARRAY ? (array)$webhook : $webhook;
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
        $webhookID = $formPostValues['WebhookID'] ?? false;
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
                    'Active' => $formPostValues['Active'],
                    'Events' => $formPostValues['Events'],
                    'Name' => $formPostValues['Name'],
                    'Url' =>  $formPostValues['Url'],
                    'Secret' => $formPostValues['Secret']
                ];
                $webhook = $this->coerceData($webhook);
                $webhookID = $this->insert($webhook);

            } else {
                $newWebhookData = $this->coerceData($formPostValues);
                $this->update($newWebhookData, ['WebhookID' => $webhookID]);
            }
        }
        return $webhookID;
    }

    /**
     * Delete a webhook.
     *
     * This is a hard delete that completely removes it from the database.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $webhookID Unique ID of the webhook to be deleted.
     * @param array $options Additional options for the delete.
     * @param bool Always returns TRUE.
     */
    public function deleteID($webhookID, $options = []) {
        $webhook = $this->getID($webhookID, DATASET_TYPE_ARRAY);
        if (!$webhook) {
            return false;
        }
        // Log the deletion.
        $log = isset($options['log']) ? $options['log'] : 'Delete';
        LogModel::insert($log, 'Webhook', $webhook, isset($options['log']) ? $options['log'] : []);

        // Delete the webhook.
        $this->SQL->delete('Webhook', ['WebhookID' => $webhookID]);
        return true;
    }
}
