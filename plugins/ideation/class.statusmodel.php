<?php
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class StatusModel extends Gdn_Model {

    const STATUS_TAG_ID_CACHE_KEY = 'statusTagsIDs';

    /**
     * @var array An array representation of all the statuses in the database.
     */
    protected $statuses;

    /**
     * @var StatusModel An instance of this class. Use this instead of instantiating a new class.
     */
    protected static $instance;

    public function __construct() {
        parent::__construct('Status');
    }

    /**
     * Returns an instance of this StatusModel class.
     *
     * @return StatusModel An instance of this class.
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new StatusModel();
        }
        return self::$instance;
    }

    /**
     * Update or insert a status in the Status table.
     *
     * @param string $name The name of the status.
     * @param string $state Either 'Open' or 'Closed'.
     * @param int $isDefault Whether the status is default or not.
     * @param int $statusID The ID of the status. Use if updating.
     * @return bool|int The ID of the saved status.
     * @throws Exception
     */
    public function upsert($name, $state, $isDefault = 0, $statusID = 0) {
        // Put the data into a format that's savable.
        $this->defineSchema();
        $this->Validation->setSchema($this->Schema);

        $saveData = [
            'Name' => $name,
            'State' => $state
        ];

        if ($statusID) {
            $saveData['StatusID'] = $statusID;
        }

        if ($isDefault == 1) {
            $isDefault = '1';
            $this->update(['IsDefault' => '0'], ['IsDefault' => '1']);
        } else {
            $isDefault = '0';
        }

        $saveData['IsDefault'] = $isDefault;

        $insert = true;

        // Grab the current status.
        if (isset($saveData['StatusID'])) {
            $primaryKeyVal = $saveData['StatusID'];
            $status = $this->SQL->getWhere('Status', ['StatusID' => $primaryKeyVal])->firstRow(DATASET_TYPE_ARRAY);
            if ($status) {
                $insert = false;
                $oldStatus = StatusModel::instance()->getStatus($saveData['StatusID']);
            }
        } else {
            $primaryKeyVal = false;
        }

        // Validate the form posted values.
        if ($this->validate($saveData, $insert) === true) {
            $fields = $this->Validation->validationFields();

            if ($insert === false) {
                unset($fields[$this->PrimaryKey]); // Don't try to update the primary key
                $this->update($fields, [$this->PrimaryKey => $primaryKeyVal]);
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
     * Retrieves the default status.
     *
     * @return int|string The default status.
     */
    public function getDefaultStatus() {
        $statuses = self::statuses();
        foreach($statuses as $status) {
            if (val('IsDefault', $status)) {
                return $status;
            }
        }
        return [];
    }

    /**
     * Retrieves either the set of statuses or a specific status with the given ID.
     *
     * @param int $statusID The ID of the status to retrieve.
     * @return array A set of all the statuses or the specific status with the passed ID.
     */
    protected function statuses($statusID = 0) {
        if ($this->statuses === null) {
            // Fetch statuses
            $this->statuses = Gdn::cache()->get(self::STATUS_TAG_ID_CACHE_KEY);
            if ($this->statuses === Gdn_Cache::CACHEOP_FAILURE) {
                $statusModel = new StatusModel();
                $statuses = $statusModel->getWhere()->resultArray();
                $this->statuses = Gdn_DataSet::index($statuses, ['StatusID']);
                Gdn::cache()->store(self::STATUS_TAG_ID_CACHE_KEY,  $this->statuses);
            }
        }

        if ($statusID) {
            return val($statusID, $this->statuses, NULL);
        } else {
            return $this->statuses;
        }
    }

    /**
     * Clear cache for idea statuses.
     * This function should be called on add/edit/delete of idea statuses to make sure the UI represents reality.
     */
    public function clearStatusesCache() {
        Gdn::cache()->remove(self::STATUS_TAG_ID_CACHE_KEY);
    }

    /**
     * Retrieves the status with the given tag ID.
     *
     * @param $tagID The ID of the tag to find the status from.
     * @return array The status with the given tag ID or an empty array.
     */
    public function getStatusByTagID($tagID) {
        $statuses = $this->statuses();
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
    public function getOpenStatuses() {
        $statuses = $this->statuses();
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
    public function getClosedStatuses() {
        $statuses = $this->statuses();
        $closedStatuses = [];
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
    public function getStatuses() {
        return $this->statuses();
    }

    /**
     * Returns a status with the given ID.
     *
     * @param int $statusID The status ID of the status to retrieve.
     * @return array The status with the passed ID.
     */
    public function getStatus($statusID = 0) {
        return $this->statuses($statusID);
    }

    /**
     * Add or updates a tag in the Tag table. A Status-type tag must be defined when inserting a new Status.
     *
     * @param string $name The name of Tag to add.
     * @param string $type The type of the tag.
     * @param bool $oldName The old name of the tag to update.
     * @return int The ID of the tag updated or inserted.
     */
    protected function defineTag($name, $type = 'Status', $oldName = false) {
        $row = Gdn::sql()->getWhere('Tag', ['Name' => $name])->firstRow(DATASET_TYPE_ARRAY);

        if (!$row && $oldName) {
            $row = Gdn::sql()->getWhere('Tag', ['Name' => $oldName])->firstRow(DATASET_TYPE_ARRAY);
        }

        if (!$row) {
            $tagID = Gdn::sql()->insert('Tag', [
                    'Name' => $name,
                    'FullName' => $name,
                    'Type' => 'Status',
                    'InsertUserID' => Gdn::session()->UserID,
                    'DateInserted' => Gdn_Format::toDateTime()]
            );
        } else {
            $tagID = $row['TagID'];
            if ($row['Type'] != $type || $row['Name'] != $name) {
                Gdn::sql()->put('Tag', [
                    'Name' => $name,
                    'FullName' => $name,
                    'Type' => $type
                ], ['TagID' => $tagID]);
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
    public function getStatusByDiscussion($discussionID) {
        $tagModel = new TagModel();
        $tags = $tagModel->getDiscussionTags($discussionID);
        if (val('Status', $tags)) {
            $tag = $tags['Status'][0];
            return $this->getStatusByTagID(val('TagID', $tag));
        }

        return [];
    }

}
