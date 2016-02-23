<?php
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class StageModel extends Gdn_Model {

    /**
     * @var array An array representation of all the stages in the database.
     */
    private static $stages;

    public function __construct() {
        parent::__construct('Stage');
    }

    /**
     * Inserts or updates a stage in the Stage table.
     *
     * @param string $name The name of the stage.
     * @param string $status Either 'Open' or 'Closed'.
     * @param string $description The global description for the stage.
     * @param int $stageID The ID of the stage. Use if updating.
     * @return bool|int The ID of the saved stage.
     * @throws Exception
     */
    public function save($name, $status, $description = '', $stageID = 0) {
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

        $insert = true;

        // Grab the current stage.
        if (isset($saveData['StageID'])) {
            $primaryKeyVal = $saveData['StageID'];
            $stage = $this->SQL->getWhere('Stage', array('StageID' => $primaryKeyVal))->firstRow(DATASET_TYPE_ARRAY);
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
                unset($fields[$this->PrimaryKey]); // Don't try to update the primary key
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

    /**
     * Retrieves either the set of stages or a specific stage with the given ID.
     *
     * @param int $stageID The ID of the stage to retrieve.
     * @return array A set of all the stages or the specific stage with the passed ID.
     */
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

    /**
     * Retrieves the stage with the given tag ID.
     *
     * @param $tagID The ID of the tag to find the stage from.
     * @return array The stage with the given tag ID or an empty array.
     */
    public static function getStageByTagID($tagID) {
        $stages = self::stages();
        foreach($stages as $stage) {
            if (val('TagID', $stage) == $tagID) {
                return $stage;
            }
        }
        return [];
    }

    /**
     * Retrieves stages with open statuses.
     *
     * @return array The stages with an open status.
     */
    public static function getOpenStages() {
        $stages = self::stages();
        $openStages = [];
        foreach($stages as $stage) {
            if (val('Status', $stage) == 'Open') {
                $openStages[] = $stage;
            }
        }
        return $openStages;
    }

    /**
     * Retrieves stages with closed statuses.
     *
     * @return array The stages with a closed status.
     */
    public static function getClosedStages() {
        $stages = self::stages();
        $closedStages = array();
        foreach($stages as $stage) {
            if (val('Status', $stage) == 'Closed') {
                $closedStages[] = $stage;
            }
        }
        return $closedStages;
    }

    /**
     * Returns all the stages.
     *
     * @return array A set of all the stages.
     */
    public static function getStages() {
        return self::stages();
    }

    /**
     * Returns a stage with the given ID.
     *
     * @param int $stageID The stage ID of the stage to retrieve.
     * @return array The stage with the passed ID.
     */
    public static function getStage($stageID = 0) {
        return self::stages($stageID);
    }

    /**
     * Add or updates a tag in the Tag table. A Stage-type tag must be defined when inserting a new Stage.
     *
     * @param $name The name of Tag to add.
     * @param string $type The type of the tag.
     * @param bool $oldName The old name of the tag to update.
     * @return int The ID of the tag updated or inserted.
     */
    protected function defineTag($name, $type = 'Stage', $oldName = false) {
        $row = Gdn::sql()->getWhere('Tag', array('Name' => $name))->firstRow(DATASET_TYPE_ARRAY);

        if (!$row && $oldName) {
            $row = Gdn::sql()->getWhere('Tag', array('Name' => $oldName))->firstRow(DATASET_TYPE_ARRAY);
        }

        if (!$row) {
            $tagID = Gdn::sql()->insert('Tag', array(
                    'Name' => $name,
                    'FullName' => $name,
                    'Type' => 'Stage',
                    'InsertUserID' => Gdn::session()->UserID,
                    'DateInserted' => Gdn_Format::toDateTime())
            );
        } else {
            $tagID = $row['TagID'];
            if ($row['Type'] != $type || $row['Name'] != $name) {
                Gdn::sql()->put('Tag', array(
                    'Name' => $name,
                    'FullName' => $name,
                    'Type' => $type
                ), array('TagID' => $tagID));
            }
        }
        return $tagID;
    }

    /**
     * Retrieves the stage of a discussion with the given ID.
     *
     * @param int $discussionID The ID of the discussion.
     * @return array The stage of the discussion with the given ID.
     */
    public static function getStageByDiscussion($discussionID) {
        $tagModel = new TagModel();
        $tags = $tagModel->getDiscussionTags($discussionID);
        if (val('Stage', $tags)) {
            $tag = $tags['Stage'][0];
            return self::getStageByTagID(val('TagID', $tag));
        }

        return [];
    }

}
