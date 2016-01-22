<?php
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class StageModel extends Gdn_Model {

    private static $stages;

    public function __construct() {
        parent::__construct('Stage');
    }

    public function save($name, $status, $description = '', $attributes = array(), $stageID = 0) {
        // Put the data into a format that's savable.
        $this->defineSchema();
        $this->Validation->setSchema($this->Schema);

        $saveData = array(
            'Name' => $name,
            'Status' => $status
        );

        if ($description) {
            $saveData['Description'] = $description;
        }

        if ($stageID) {
            $saveData['StageID'] = $stageID;
        }

        if (sizeof($attributes)) {
            $saveData['Attributes'] = $attributes;
        }

        $insert = true;

        // Grab the current stage.
        if (isset($saveData['StageID'])) {
            $primaryKeyVal = $saveData['StageID'];
            $stage = $this->SQL->getWhere('Stage', array('StageID' => $primaryKeyVal))->FirstRow(DATASET_TYPE_ARRAY);
            if ($stage) {
                $insert = false;
                $oldStage = StageModel::getStage($saveData['StageID']);
            }
        } else {
            $primaryKeyVal = false;
        }

        // Validate the form posted values.
        if ($this->validate($saveData, $insert) === true) {
            $fields = $this->Validation->validationFields();

            if ($insert === false) {
                $fields = RemoveKeyFromArray($fields, $this->PrimaryKey); // Don't try to update the primary key
                $this->update($fields, array($this->PrimaryKey => $primaryKeyVal));
                $this->defineTag($name, 'Stage', val('Name', $oldStage));
            } else {
                $tagID = $this->defineTag($name);
                $fields['TagID'] = $tagID;
                $primaryKeyVal = $this->insert($fields);
            }
        } else {
            $primaryKeyVal = false;
        }
        return $primaryKeyVal;
    }

    protected static function stages($stageID = 0) {
        if (self::$stages === null) {
            // Fetch stages
            $stageModel = new StageModel();
            $stages = $stageModel->getWhere()->resultArray();
            self::$stages = Gdn_DataSet::index($stages, array('StageID'));
        }

        if ($stageID) {
            return val($stageID, self::$stages, NULL);
        } else {
            return self::$stages;
        }
    }

    public static function getStageByTagID($tagID) {
        $stages = self::stages();
        foreach($stages as $stage) {
            if (val('TagID', $stage) == $tagID) {
                return $stage;
            }
        }
        return false;
    }

    public static function getStages() {
        return self::stages();
    }

    public static function getStage($stageID = 0) {
        return self::stages($stageID);
    }

    protected function defineTag($name, $type = 'Stage', $oldName = FALSE) {
        $row = Gdn::sql()->getWhere('Tag', array('Name' => $name))->firstRow(DATASET_TYPE_ARRAY);

        if (!$row && $oldName) {
            $row = Gdn::sql()->getWhere('Tag', array('Name' => $oldName))->firstRow(DATASET_TYPE_ARRAY);
        }

        if (!$row) {
            $tagID = Gdn::sql()->insert('Tag', array(
                    'Name' => $name,
                    'Type' => 'Stage',
                    'InsertUserID' => Gdn::session()->UserID,
                    'DateInserted' => Gdn_Format::toDateTime())
            );
        } else {
            $tagID = $row['TagID'];
            if ($row['Type'] != $type || $row['Name'] != $name) {
                Gdn::sql()->put('Tag', array(
                    'Name' => $name,
                    'Type' => $type
                ), array('TagID' => $tagID));
            }
        }
        return $tagID;
    }

}
