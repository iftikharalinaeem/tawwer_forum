<?php

class UserNoteModel extends Gdn_Model {

    public function __construct() {
        parent::__construct('UserNote');
    }

    /**
     * Calculating notes row.
     *
     * @param array $data
     */
    public function calculate(&$data) {
        Gdn::userModel()->joinUsers($data, ['InsertUserID']);
        $isModerator = Gdn::session()->checkPermission('Garden.Moderation.Manage');
        foreach ($data as &$row) {
            $this->calculateRow($row);
        }
    }

    /**
     * Counting row, unsetting Moderator Note is user is not moderator.
     *
     * @param array $row
     */
    protected function calculateRow(&$row) {
        $isModerator = Gdn::session()->checkPermission('Garden.Moderation.Manage');
        $row['Body'] = Gdn_Format::to($row['Body'], $row['Format']);

        if (!$isModerator) {
            unset($row['ModeratorNote']);
        }
    }

    /**
     * Get a UserNote by ID.
     *
     * @param mixed $iD
     * @param bool|false $datasetType Unused
     * @param array $options Unused
     * @return array|object
     */
    public function getID($iD, $datasetType = false, $options = []) {
        $row = parent::getID($iD, DATASET_TYPE_ARRAY);
        $row = $this->expandAttributes($row);
        $this->calculateRow($row);
    
        return $row;
    }

    /**
     * Get a dataset for the model with a where filter.
     *
     * @param bool $where
     * @param string $orderFields
     * @param string $orderDirection
     * @param bool $limit
     * @param bool $offset
     * @return Gdn_DataSet
     */
    public function getWhere($where = false, $orderFields = '', $orderDirection = 'asc', $limit = false, $offset = false) {
        $data = parent::getWhere($where, $orderFields, $orderDirection, $limit, $offset);
        $data->datasetType(DATASET_TYPE_ARRAY);
        $data->expandAttributes();
        return $data;
    }

    /**
     *
     * @param array $formPostValues
     * @param bool $settings
     *
     * @return unknown
     */
    public function save($formPostValues, $settings = false) {
        $row = $this->collapseAttributes($formPostValues);

        return parent::save($row, $settings);
    }

    /**
     * Update a row in the database.
     *
     * @param int $rowID
     * @param array|string $name
     * @param null $value
     */
    public function setField($rowID, $name, $value = null) {
        if (!is_array($name)) {
            $name = [$name => $value];
        }

        $this->defineSchema();
        $fields = $this->Schema->fields();
        $inSchema = array_intersect_key($name, $fields);
        $notInSchema = array_diff_key($name, $inSchema);

        if (empty($notInSchema)) {
            return parent::setField($rowID, $name);
        } else {
            $row = $this->SQL->select('Attributes')->getWhere('UserNote', ['UserNoteID' => $rowID])->firstRow(DATASET_TYPE_ARRAY);
            if (isset($row['Attributes'])) {
                $attributes = dbdecode($row['Attributes']);
                if (is_array($attributes)) {
                    $attributes = array_merge($attributes, $notInSchema);
                }
                else
                    $attributes = $notInSchema;
            } else {
                $attributes = $notInSchema;
            }
            $inSchema['Attributes'] = $attributes;
            return parent::setField($rowID, $inSchema);
        }
    }
}
