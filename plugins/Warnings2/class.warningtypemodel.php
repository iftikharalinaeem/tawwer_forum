<?php
/**
 * WarningTypeModel
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Class WarningTypeModel
 */
class WarningTypeModel extends Gdn_Model {
    /**
     * Class constructor.
     */
    public function __construct() {
        parent::__construct('WarningType');
    }

    /**
     * Return an instance of WarningTypeModel.
     *
     * @return WarningTypeModel WarningTypeModel
     */
    public static function instance() {
        static $warningTypeModel = null;

        if ($warningTypeModel === null) {
            $warningTypeModel = new WarningTypeModel();
        }

        return $warningTypeModel;
    }

    /**
     * Return all WarningTypes.
     *
     * @return array The form data.
     */
    public function getAll() {
        $warningTypes = $this->get()->resultArray();
        return Gdn_DataSet::index($warningTypes, 'WarningTypeID');
    }

    /**
     * Save data received from a Form.
     *
     * @param array $formPostValues The data to save.
     * @param bool $settings Unused
     *
     * @return bool Returns true on success, false otherwise.
     */
    public function save($formPostValues, $settings = false) {
        $this->Validation->applyRule('Name', 'Required');
        $this->Validation->applyRule('Points', 'Integer');
        $this->Validation->applyRule('Points', 'Required');
        $this->Validation->applyRule('ExpireNumber', 'Integer');
        $this->Validation->applyRule('ExpireNumber', 'Required');
        $this->Validation->applyRule('ExpireType', 'Enum');
        $this->Validation->applyRule('ExpireType', 'Required');

        $warningTypeID = val('WarningTypeID', $formPostValues);
        if ($warningTypeID) {
            $success = (bool)$this->update($formPostValues, ['WarningTypeID' => $warningTypeID]);
        } else {
            $success = $this->insert($formPostValues);
        }

        return $success;
    }
}
