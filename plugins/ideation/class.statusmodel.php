<?php
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class StatusModel extends Gdn_Model {

    /**
     * @var array An array representation of all the statuses in the database.
     */
    private static $statuses;

    public function __construct() {
        parent::__construct('Status');
    }

    /**
     * Inserts or updates a status in the Status table.
     *
     * @param string $name The name of the status.
     * @param string $state Either 'Open' or 'Closed'.
     * @param string $description The global description for the status.
     * @param int $statusID The ID of the status. Use if updating.
     * @return bool|int The ID of the saved status.
     * @throws Exception
     */
    public function save($name, $state, $description = '', $statusID = 0) {
        // Put the data into a format that's savable.
        $this->defineSchema();
        $this->Validation->setSchema($this->Schema);

        $saveData = array(
            'Name' => $name,
            'State' => $state
        );

        if ($description) {
            $saveData['Description'] = $description;
        }

        if ($statusID) {
            $saveData['StatusID'] = $statusID;
        }

        $insert = true;

        // Grab the current status.
        if (isset($saveData['StatusID'])) {
            $primaryKeyVal = $saveData['StatusID'];
            $status = $this->SQL->getWhere('Status', array('StatusID' => $primaryKeyVal))->firstRow(DATASET_TYPE_ARRAY);
            if ($status) {
                $insert = false;
                $oldStatus = StatusModel::getStatus($saveData['StatusID']);
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
                $this->defineTag($name, 'Status', val('Name', $oldStatus));
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
     * Retrieves either the set of statuses or a specific status with the given ID.
     *
     * @param int $statusID The ID of the status to retrieve.
     * @return array A set of all the statuses or the specific status with the passed ID.
     */
    protected static function statuses($statusID = 0) {
        if (self::$statuses === null) {
            // Fetch statuses
            $statusModel = new StatusModel();
            $statuses = $statusModel->getWhere()->resultArray();
            self::$statuses = Gdn_DataSet::index($statuses, array('StatusID'));
        }

        if ($statusID) {
            return val($statusID, self::$statuses, NULL);
        } else {
            return self::$statuses;
        }
    }

    /**
     * Retrieves the status with the given tag ID.
     *
     * @param $tagID The ID of the tag to find the status from.
     * @return array The status with the given tag ID or an empty array.
     */
    public static function getStatusByTagID($tagID) {
        $statuses = self::statuses();
        foreach($statuses as $status) {
            if (val('TagID', $status) == $tagID) {
                return $status;
            }
        }
        return [];
    }

    /**
     * Retrieves statuses with open states.
     *
     * @return array The statuses with an open state.
     */
    public static function getOpenStatuses() {
        $statuses = self::statuses();
        $openStatuses = [];
        foreach($statuses as $status) {
            if (val('State', $status) == 'Open') {
                $openStatuses[] = $status;
            }
        }
        return $openStatuses;
    }

    /**
     * Retrieves statuses with closed states.
     *
     * @return array The statuses with a closed state.
     */
    public static function getClosedStatuses() {
        $statuses = self::statuses();
        $closedStatuses = array();
        foreach($statuses as $status) {
            if (val('State', $status) == 'Closed') {
                $closedStatuses[] = $status;
            }
        }
        return $closedStatuses;
    }

    /**
     * Returns all the statuses.
     *
     * @return array A set of all the statuses.
     */
    public static function getStatuses() {
        return self::statuses();
    }

    /**
     * Returns a status with the given ID.
     *
     * @param int $statusID The status ID of the status to retrieve.
     * @return array The status with the passed ID.
     */
    public static function getStatus($statusID = 0) {
        return self::statuses($statusID);
    }

    /**
     * Add or updates a tag in the Tag table. A Status-type tag must be defined when inserting a new Status.
     *
     * @param $name The name of Tag to add.
     * @param string $type The type of the tag.
     * @param bool $oldName The old name of the tag to update.
     * @return int The ID of the tag updated or inserted.
     */
    protected function defineTag($name, $type = 'Status', $oldName = false) {
        $row = Gdn::sql()->getWhere('Tag', array('Name' => $name))->firstRow(DATASET_TYPE_ARRAY);

        if (!$row && $oldName) {
            $row = Gdn::sql()->getWhere('Tag', array('Name' => $oldName))->firstRow(DATASET_TYPE_ARRAY);
        }

        if (!$row) {
            $tagID = Gdn::sql()->insert('Tag', array(
                    'Name' => $name,
                    'FullName' => $name,
                    'Type' => 'Status',
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
     * Retrieves the status of a discussion with the given ID.
     *
     * @param int $discussionID The ID of the discussion.
     * @return array The status of the discussion with the given ID.
     */
    public static function getStatusByDiscussion($discussionID) {
        $tagModel = new TagModel();
        $tags = $tagModel->getDiscussionTags($discussionID);
        if (val('Status', $tags)) {
            $tag = $tags['Status'][0];
            return self::getStatusByTagID(val('TagID', $tag));
        }

        return [];
    }

}
