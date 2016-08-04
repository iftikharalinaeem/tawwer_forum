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
     * Return an instance of WarningTypeModel
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
     * Return all WarningTypes
     *
     * @return array The form data.
     */
    public function getAll() {
        $warningTypes = $this->get()->resultArray();
        return Gdn_DataSet::index($warningTypes, 'WarningTypeID');
    }

    /**
     * Take the row like structure and flatten it to be used in a Form.
     *
     * @return array The form data.
     */
    public function getFormData() {
        $formData = [];

        foreach ($this->getAll() as $warningTypeID => $warningType) {
            foreach ($warningType as $fieldName => $fieldValue) {
                $formData[$warningTypeID.'_'.$fieldName] = $fieldValue;
            }
        }

        return $formData;
    }

    /**
     * Take the form's data and convert it into a row like structure.
     *
     * @param array $formData
     * @return array
     */
    public function convertFormData($formData) {
        $warningTypesData = [];

        // Keep only what is good
        $validFormData = $this->getFormData();
        $formData = array_intersect_key($formData, $validFormData);

        foreach ($formData as $warningTypeIDFieldName => $fieldValue) {
            $pos = strpos($warningTypeIDFieldName, '_');
            if ($pos && strlen($warningTypeIDFieldName) > $pos + 1) {
                $warningTypeID = substr($warningTypeIDFieldName, 0, $pos);
                $fieldName = substr($warningTypeIDFieldName, $pos + 1);
                $warningTypesData[$warningTypeID][$fieldName] = $fieldValue;
            }
        }

        return $warningTypesData;
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
        $success = true;

        $this->Validation->applyRule('Name', 'Required');
        $this->Validation->applyRule('Points', 'Integer');
        $this->Validation->applyRule('Points', 'Required');
        $this->Validation->applyRule('ExpireNumber', 'Integer');
        $this->Validation->applyRule('ExpireNumber', 'Required');
        $this->Validation->applyRule('ExpireType', 'Enum');
        $this->Validation->applyRule('ExpireType', 'Required');

        $warningTypesData = $this->convertFormData($formPostValues);
        foreach ($warningTypesData as $warningTypeID => $warningTypeData) {
            $success = $success && $this->update($warningTypeData, ['WarningTypeID' => $warningTypeID]);
        }

        return $success;
    }
}
