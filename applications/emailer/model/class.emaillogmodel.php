<?php

/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */
class EmailLogModel extends Gdn_Model {

    /**
     * EmailLogModel constructor.
     */
    public function __construct() {
        parent::__construct('EmailLog');
    }

    /**
     * {@inheritdoc}
     */
    private function serializeFields(&$fields) {
        if (isset($fields['Post']) && is_array($fields['Post'])) {
            $fields['Post'] = json_encode($fields['Post'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function insert($Fields) {
        $this->serializeFields($Fields);

        return parent::insert($Fields);
    }

    /**
     * {@inheritdoc}
     */
    public function setField($RowID, $Property, $Value = false) {
        if (!is_array($Property)) {
            $array = [$Property => $Value];
        } else {
            $array = $Property;
        }
        $this->serializeFields($array);

        parent::setField($RowID, $array);
    }
}
