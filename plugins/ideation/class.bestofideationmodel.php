<?php
/**
 * BestOfIdeation Model
 *
 * @author David Barbier <david.barbier@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 * @since     4.0
 *
 */

use \Vanilla\Models\PipelineModel;

/**
 * Class BestOfIdeationModel
 */
class BestOfIdeationModel extends PipelineModel {
    /**
     * The database column where a category's settings for BestOfIdeation module will be saved.
     */
    public const SETTINGS_COL_NAME = 'BestOfIdeationSettings';

    /**
     * BestOfIdeationModel constructor.
     */
    public function __construct() {
        parent::__construct('BestOfIdeationConfig');
    }

    /**
     * Allows to save(update or insert) the BestOfIdeation settings for a CategoryID.
     *
     * @param int $categoryID the CategoryID for which we want to save the settings.
     * @param array $settings an array of the settings to save.
     */
    public function saveConfiguration(int $categoryID, array $settings) {
        try {
            if ($this->get(['CategoryID' => $categoryID])) {
                $this->update(['BestOfIdeationSettings' => dbencode($settings)], ['CategoryID' => $categoryID]);
            } else {
                $this->insert(['CategoryID' => $categoryID, 'BestOfIdeationSettings' => dbencode($settings)]);
            }
        } catch (Exception $exception) {
            echo 'Exception: ' . $exception->getMessage();
            die();
        }
    }

    /**
     * Loads/returns the BestOfIdeation configuration for a categoryID.
     *
     * @param int $categoryID
     * @return array
     */
    public function loadConfiguration(int $categoryID): array {
        $configuration = [];

        $catBOIDatas = $this->get(['categoryID' => $categoryID]);

        if (count($catBOIDatas) == 1) {
            $catBOIDatas = reset($catBOIDatas);

            if (isset($catBOIDatas[BestOfIdeationModel::SETTINGS_COL_NAME])) {
                $catBOISettings = dbdecode($catBOIDatas[BestOfIdeationModel::SETTINGS_COL_NAME]);
                if (is_array($catBOISettings)) {
                    $configuration = [
                        'IsEnabled' => true,
                        'Dates' => $catBOISettings['Dates'],
                        'Limit' => $catBOISettings['Limit']
                    ];
                }
            }
        }

        return $configuration;
    }

    /**
     * Delete the BestOfIdeation settings based on categoryID
     *
     * @param int $categoryID the categoryID for which we want to delete the BEstOfIdeation settings.
     * @return bool
     * @throws Exception If an error is encountered while performing the query.
     */
    public function deleteConfiguration(int $categoryID): bool {
        return $this->delete(['categoryID' => $categoryID]);
    }
}
