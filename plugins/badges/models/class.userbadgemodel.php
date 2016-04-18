<?php
/**
 * @copyright 2011-2015 Vanilla Forums, Inc.
 * @package Badges
 */

// We can't rely on our autoloader in a plugin.
require_once(dirname(__FILE__).'/class.badgesappmodel.php');
 
/**
 * Deals with associating users with badges.
 */
class UserBadgeModel extends BadgesAppModel {
    public $NoSpam = true;

    /**
     * Class constructor. Defines the related database table name.
     *
     * @access public
     */
    public function __construct() {
        parent::__construct('UserBadge');
    }

    /**
     * Record progress on a Timeout badge (do X activity Y times within Z seconds).
     *
     * Stores an 'Events' array of timestamps in the UserBadge's attributes to track
     * how many times an event has occured within the Badge's Timeout attribute.
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $UserID
     * @param mixed $BadgeID int or string identifier.
     * @param mixed $NewTimestamp Unix timestamp or date string.
     * @return int Number of timestamps stored within $Timeout seconds.
     */
    public function addTimeoutEvent($UserID, $BadgeID, $NewTimestamp) {
        // Get badge
        $Badge = $this->getBadgeID($BadgeID, DATASET_TYPE_ARRAY);
        if (!$Badge) {
            return false;
        }

        // Get user progress
        $UserBadge = $this->getID($UserID, GetValue('BadgeID', $Badge));

        // Grab relevant parameters
        $Timeout = val('Timeout', $UserBadge['Attributes'], 0);
        $Threshold = val('Threshold', $Badge, false);

        // Get new timestamp and add to events
        $Events = val('Events', $UserBadge['Attributes'], array());
        $NewTimestamp = (is_numeric($NewTimestamp)) ? $NewTimestamp : strtotime($NewTimestamp);
        $Events[] = $NewTimestamp;

        // Only keep events that happened within last $MaxSeconds from $NewTimestamp
        foreach ($Events as $Key => $Timestamp) {
            if ($Timestamp + $Timeout < $NewTimestamp) {
                unset($Events[$Key]);
            }
        }

        // Save new event list
        setValue('Events', $UserBadge['Attributes'], $Events);
        $this->Save($UserBadge);

        // If we've achieved threshold, give badge to user
        if ($Threshold && count($Events) >= $Threshold) {
            $this->give($UserID, $BadgeID);
        }

        return count($Events);
    }

    /**
     * Get number of badges this user has received.
     *
     * @since 1.0.0
     * @access public
     */
    public function badgeCount($UserID = '') {
        return $this->getCount(array('UserID' => $UserID, 'DateCompleted is not null' => null));
    }

    /**
     *
     *
     * @param $Badge
     * @return string
     */
    public static function badgeName($Badge) {
        $Name = $Badge['Name'];
        $Threshold = $Badge['Threshold'];

        if (!$Threshold) {
            return t($Name);
        }

        if (strpos($Name, $Threshold) !== false) {
            $Code = str_replace($Threshold, '%s', $Name);

            if ($Threshold == 1) {
                return plural($Threshold, $Code, $Code.'s');
            } else {
                return plural($Threshold, rtrim($Code, 's'), $Code);
            }
        } else {
            return t($Name);
        }
    }

    /**
     *
     *
     * @param int $Limit
     * @return int
     * @throws Exception
     */
    public function bombAnniversary($Limit = 100) {
        // Make sure no one gets a notification.
        saveToConfig(array(
            'Preferences.Email.Badge' => false,
            'Preferences.Popup.Badge' => false
            ), '', false);

        $BadgeModel = new BadgeModel();

        // Grab the first comment badge.
        $Badge = $BadgeModel->getID('anniversary');
        $BadgeID = $Badge['BadgeID'];

        // Grab all of the users that have been around for at least a year.
        $this->SQL->select('u.*')
            ->from('User u')
            ->join('UserBadge ub', "u.UserID = ub.UserID and ub.BadgeID = $BadgeID", 'left')
            ->where('u.DateFirstVisit <=', Gdn_Format::toDateTime(strtotime('-1 year')))
            ->where('ub.BadgeID is null')
            ->limit($Limit);

        $Data = $this->SQL->get()->resultArray();

        $Hooks = new BadgesHooks();

        $Count = 0;
        foreach ($Data as $Row) {
//            $Args = array('UserID' => $Row['UserID'], 'Fields' => array('CountComments' => $Row['CountComments']));
            Gdn::session()->User = $Row;
            $Hooks->anniversaries($this, array());

            $Count++;
        }
        return $Count;
    }

    /**
     *
     *
     * @param int $Limit
     * @return int
     * @throws Exception
     */
    public function bombComment($Limit = 100) {
        // Make sure no one gets a notification.
        saveToConfig(array(
            'Preferences.Email.Badge' => false,
            'Preferences.Popup.Badge' => false
            ), '', false);

        $BadgeModel = new BadgeModel();

        // Grab the first comment badge.
        $Badge = $BadgeModel->getID('comment');
        $BadgeID = $Badge['BadgeID'];

        // Grab all of the users that have at least one comment, but don't have this badge.
        $Data = $this->SQL->select('u.*')
            ->from('User u')
            ->join('UserBadge ub', "u.UserID = ub.UserID and ub.BadgeID = $BadgeID", 'left')
            ->where('u.CountComments >=', 1)
            ->where('ub.BadgeID is null')
            ->limit($Limit)
            ->get()->resultArray();

        $Hooks = new BadgesHooks();

        $Count = 0;
        foreach ($Data as $Row) {
            $Args = array('UserID' => $Row['UserID'], 'Fields' => array('CountComments' => $Row['CountComments']));
            $Hooks->userModel_afterSetField_handler($this, $Args);
            $Count++;
        }
        return $Count;
    }

    /**
     * Decline a user's badge request.
     *
     * @since 1.1
     * @access public
     */
    public function declineRequest($UserID, $BadgeID) {
        $UserBadge = $this->getID($UserID, $BadgeID);
        setValue('Declined', $UserBadge, 1);
        $this->save($UserBadge);
    }

    /**
     * Get badges for a single user.
     *
     * @since 1.0.0
     * @access public
     */
    public function getBadges($UserID = '') {
        return $this->SQL
            ->select('b.*')
            ->select('ub.Reason')
            ->select('ub.ShowReason')
            ->select('ub.DateCompleted')
            ->from('UserBadge ub')
            ->join('Badge b', 'b.BadgeID = ub.BadgeID', 'left')
            ->where('ub.UserID', $UserID)
            ->where('ub.DateCompleted is not null')
            ->orderBy('ub.DateCompleted', 'desc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getID($id, $dataSetType = false, $options = []) {
        if (is_numeric($id) && is_numeric($dataSetType)) {
            deprecated('UserBadgeModel->getID(int, int)', 'UserBadgeModel->getByUser()');
            return $this->getByUser($id, $dataSetType);
        }
        return parent::getID($id, $dataSetType, $options);
    }

    /**
     * Get badge data for single user/badge association.
     *
     */
    public function getByUser($UserID, $BadgeID) {
        $BadgeID = $this->getBadgeID($BadgeID);

        $Result = $this->SQL->getWhere('UserBadge', array('UserID' => $UserID, 'BadgeID' => $BadgeID))->firstRow(DATASET_TYPE_ARRAY);

        if (!$Result) {
            $Result = array('UserID' => $UserID, 'BadgeID' => $BadgeID, '_New' => true);
        } else {
            $Result['_New'] = false;
        }

        $Attributes = val('Attributes', $Result);
        if ($Attributes) {
            $Attributes = dbdecode($Attributes);
        } else {
            $Attributes = array();
        }
        setValue('Attributes', $Result, $Attributes);

        return $Result;
    }

    /**
     * Verify we have a numeric BadgeID. Optionally return entire badge object.
     *
     * A utility method to stop the insanity of deciding whether public methods
     * are getting a numeric ID or slug.
     *
     * @since 1.0.0
     * @access protected
     *
     * @param mixed $BadgeID string (slug) or int (id).
     * @param string $Send What data to return. Valid options: 'Object'.
     * @return mixed BadgeID (default) or Badge dataset if $Send == 'Object'.
     */
    protected function getBadgeID($BadgeID, $Send = false) {
        if ($Send) {
            $BadgeModel = new BadgeModel();
            $Badge = $BadgeModel->getID($BadgeID);

            if ($Send == DATASET_TYPE_OBJECT) {
                $Badge = (object)$Badge;
            }

            return $Badge;
        }

        if (is_numeric($BadgeID)) {
            return $BadgeID;
        } elseif (is_array($BadgeID))
            return $BadgeID['BadgeID'];
        else {
            $BadgeModel = new BadgeModel();
            $Badge = $BadgeModel->getID($BadgeID);

            return $Badge['BadgeID'];
        }
    }

    /**
     * Get all current badge requests.
     *
     * @since 1.1
     * @access public
     */
    public function getRequests() {
        return $this->SQL
            ->select('b.*')
            ->select('b.Name', '', 'BadgeName')
            ->select('ub.UserID')
            ->select('ub.RequestReason')
            ->select('ub.DateRequested')
            ->from('UserBadge ub')
            ->join('Badge b', 'b.BadgeID = ub.BadgeID', 'left')
            ->where('ub.Declined', 0)
            ->where('ub.DateCompleted is null')
            ->where('ub.DateRequested is not null')
            ->orderBy('ub.DateRequested', 'asc')
            ->get();
    }

    /**
     * Get users who have an badge.
     *
     * @since 1.0.0
     * @access public
     */
    public function getUsers($BadgeID, $Options = array()) {
        // Get numeric ID
        $BadgeID = $this->getBadgeID($BadgeID);
        if (!$BadgeID) {
            return false;
        }

        // Get query options
        $Limit = val('Limit', $Options, 5);

        return $this->SQL
            ->select('u.*')
            ->select('ub.DateCompleted')
            ->from('UserBadge ub')
            ->join('User u', 'u.UserID = ub.UserID', 'left')
            ->where('ub.BadgeID', $BadgeID)
            ->where('ub.DateCompleted is not null')
            ->orderBy('ub.DateCompleted', 'desc')
            ->limit($Limit)
            ->get();
    }

    /**
     * Associate a badge with a user. A badge cannot be given to a user more than once.
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $UserID.
     * @param mixed $BadgeID Int (id) or string (slug).
     * @param string $Reason Optional explanation of why they received the badge.
     */
    public function give($UserID, $BadgeID, $Reason = '') {
        if (c('Badges.Disabled')) {
            return false;
        }

        static $BadgeGiven = false;

        $Badge = $this->getBadgeID($BadgeID, DATASET_TYPE_ARRAY);
        $BadgeID = $Badge['BadgeID'];

        $UserBadge = $this->getID($UserID, val('BadgeID', $Badge));

        // Allow badges to be disabled
        if (!val('Active', $Badge)) {
            return false;
        }

        if (val('DateCompleted', $UserBadge, null) != null) {
            $User = Gdn::userModel()->getID($UserID, DATASET_TYPE_ARRAY);
            $this->Validation->addValidationResult('BadgeID', '@'.sprintf(t('The %s badge has already been given to %s.'), $Badge['Name'], $User['Name']));

            return false;
        }

        $UserBadge['Reason'] = $Reason;
        $UserBadge['DateCompleted'] = Gdn_Format::toDateTime();

        $PointsText = '';
        $Saved = $this->save($UserBadge);
        if ($Saved) {
            $Points = $Badge['Points'];
            if ($Points != 0) {
                $PointsText = ' '.Plural($Points, '%+d point', '%+d points');
                self::givePoints($UserID, $Points, 'Badges');
            }

            // Update the user's count.
            $CountBadges = $this->badgeCount($UserID);
            Gdn::userModel()->setField($UserID, 'CountBadges', $CountBadges);

            // Update the badge's count.
            $RecipientCount = $this->recipientCount($Badge['BadgeID']);
            $this->SQL->update('Badge')
                ->set('CountRecipients', $RecipientCount)
                ->where('BadgeID', $Badge['BadgeID'])
                ->put();

            // Notify people of the badge.
            $HeadlineFormat = t('HeadlineFormat.Badge', '{ActivityUserID,You} earned the <a href="{Url,html}">{Data.Name,text}</a> badge.');
            if (StringBeginsWith(Gdn::locale()->Locale, 'en', true)) {
                $BadgeBody = val('Body', $Badge);
            } else {
                $BadgeBody = '';
            }

            $Activity = array(
                 'ActivityType' => 'Badge',
                 'ActivityUserID' => $UserID,
                 'NotifyUserID' => $UserID,
                 'HeadlineFormat' => $HeadlineFormat,
                 'Story' => $BadgeBody.$PointsText,
                 'RecordType' => 'Badge',
                 'RecordID' => $BadgeID,
                 'Route' => "/badge/{$Badge['Slug']}",
                 'Data' => array('Name' => self::badgeName($Badge))
            );

            // Photo optional
            if ($Photo = val('Photo', $Badge)) {
                setValue('Photo', $Activity, Gdn_Upload::url($Photo));
            }

            $ActivityModel = new ActivityModel();

            if (!$this->NoSpam || !$BadgeGiven) {
                // Notify the user of their badge.
                $ActivityModel->queue($Activity, 'Badge', array('Force' => true));
            }

            // Notify everyone else of your badge.
            $Activity['NotifyUserID'] = ActivityModel::NOTIFY_PUBLIC;
            $Activity['Story'] = $Badge['Body'];
            $ActivityModel->queue($Activity, false, array('GroupBy' => array('ActivityTypeID', 'RecordID', 'RecordType')));

            $ActivityModel->saveQueue();
            $BadgeGiven = true;

            // Hook
            $this->EventArguments['UserBadge'] = $UserBadge;
            $this->fireEvent('AfterGive');
        }

        return $Saved;
    }

    /**
     * Add points to a user's total. Alias since 1.2.
     *
     * @since 1.0.0
     * @access public
     */
    public static function givePoints($UserID, $Points, $Source = 'Other', $Timestamp = false) {
        UserModel::givePoints($UserID, $Points, $Source, $Timestamp);
    }

    /**
     * Increment the progress on a badge.
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $UserID
     * @param mixed $BadgeID Int (id) or string (slug).
     * @param string $ColumnName Key in attributes array to increment.
     * @param int $Inc
     * @return array
     */
    public function increment($UserID, $BadgeID, $ColumnName, $Inc = 1) {
        $BadgeID = $this->getBadgeID($BadgeID);
        $UserBadge = $this->getID($UserID, $BadgeID);
        $Curr = val($ColumnName, $UserBadge['Attributes'], 0);
        $Curr += $Inc;
        setValue($ColumnName, $UserBadge['Attributes'], $Curr);
        $this->save($UserBadge);

        return $UserBadge;
    }

    /**
     * Get number of users who have this badge.
     *
     * @since 1.0.0
     * @access public
     * @todo Only count unique UserIDs
     *
     * @param mixed $BadgeID Int (id) or string (slug).
     * @return int
     */
    public function recipientCount($BadgeID = '') {
        $BadgeID = $this->getBadgeID($BadgeID);
        return $this->getCount(array('BadgeID' => $BadgeID, 'DateCompleted is not null' => null));
    }

    /**
     * Add a user's badge request to the queue.
     *
     * Existing, unevaluated requests cannot be repeated.
     * Declined requests cannot be repeated for X days.
     *
     * @since 1.1
     * @access public
     * @param int $UserID Unique.
     * @param int $BadgeID Unique.
     * @param string $Reason
     * @return bool Whether this is a new, valid request.
     */
    public function request($UserID, $BadgeID, $Reason = '') {
        $UserBadge = $this->getID($UserID, $BadgeID);
        $Badge = $this->getBadgeID($BadgeID, DATASET_TYPE_ARRAY);
        $New = true;

        // Check if request is already pending
        if (val('DateRequested', $UserBadge) && !val('Declined', $UserBadge)) {
            $New = false;
        }

        // Check for declined requests in cooldown period
        $CoolDownDays = c('Reputation.Badges.RequestCoolDownDays', 30);
        $CooledDown = (strtotime(val('DateRequested', $UserBadge)) > strtotime($CoolDownDays.' days ago'));
        if (!$CooledDown && val('Declined', $UserBadge)) {
            $New = false;
        }

        if ($New) {
            // Create the request
            setValue('DateRequested', $UserBadge, Gdn_Format::toDateTime());
            setValue('RequestReason', $UserBadge, $Reason);
            setValue('Declined', $UserBadge, 0);
            $this->save($UserBadge);

            // Prep activity
            $ActivityModel = new ActivityModel();
            $HeadlineFormat = t('HeadlineFormat.BadgeRequest', '{ActivityUserID,You} requested the <a href="{Url,html}">{Data.Name,text}</a> badge.');
            $Activity = array(
                 'ActivityType' => 'BadgeRequest',
                 'ActivityUserID' => $UserID,
                 'HeadlineFormat' => $HeadlineFormat,
                 'Story' => val('Body', $Badge),
                 'RecordType' => 'BadgeRequest',
                 'RecordID' => $BadgeID,
                 'Route' => "/badge/requests",
                 'Data' => array('Name' => $Badge['Name'])
            );

            // Optional photo
            if ($Photo = val('Photo', $Badge)) {
                setValue('Photo', $Activity, Gdn_Upload::url($Photo));
            }

            // Grab all of the users that need to be notified.
            $Data = $this->SQL
                ->whereIn('Name', array('Preferences.Email.BadgeRequest', 'Preferences.Popup.BadgeRequest'))
                ->get('UserMeta')->resultArray();

            // Build our notification queue
            $NotifyUsers = array();
            foreach ($Data as $Row) {
                $UserID = val('UserID', $Row);
                $Name = val('Name', $Row);
                if (strpos($Name, '.Email.') !== false) {
                    $NotifyUsers[$UserID]['Emailed'] = ActivityModel::SENT_PENDING;
                } elseif (strpos($Name, '.Popup.') !== false) {
                    $NotifyUsers[$UserID]['Notified'] = ActivityModel::SENT_PENDING;
                }
            }

            // Dispatch notifications
            foreach ($NotifyUsers as $UserID => $Prefs) {
                $Activity['NotifyUserID'] = $UserID;
                $Activity['Emailed'] = val('Emailed', $Prefs, false);
                $Activity['Notified'] = val('Notified', $Prefs, false);
                $ActivityModel->queue($Activity);
            }
            $ActivityModel->saveQueue();
        }

        return $New;
    }

    /**
     * Revoke a badge from a user.
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $UserID
     * @param mixed $BadgeID Int (id) or string (slug).
     */
    public function revoke($UserID, $BadgeID) {
        $Badge = $this->getBadgeID($BadgeID, DATASET_TYPE_ARRAY);
        if (!$Badge) {
            return false;
        }

        // Delete it.
        $this->delete(array('UserID' => $UserID, 'BadgeID' => $BadgeID));

        // Adjust user's badge count
        $BadgeCount = $this->badgeCount($UserID);
        Gdn::userModel()->setField($UserID, 'CountBadges', $BadgeCount);

        // Adjust's badge's recipient count
        $RecipientCount = $this->recipientCount($BadgeID);
        $this->SQL->update('Badge')
            ->set('CountRecipients', $RecipientCount)
            ->where('BadgeID', $BadgeID)
            ->put();

        $Points = $Badge['Points'];
        if ($Points != 0) {
            self::givePoints($UserID, -$Points, 'Badges');
        }

        return $UserID;
    }

    /**
     * Save given user badge.
     *
     * @param array $FormPostValues Values submitted via form.
     * @param array $Settings Not used.
     * @return bool Whether save was successful.
     */
    public function save($FormPostValues, $Settings = []) {
        $Session = Gdn::session();

        // Define the primary key in this model's table.
        $this->defineSchema();

        // Add & apply any extra validation rules
        //$this->Validation->ApplyRule('BadgeID', 'Integer');

        // Make sure that there is at least one recipient
//        $this->Validation->AddRule('OneOrMoreArrayItemRequired', 'function:ValidateOneOrMoreArrayItemRequired');
//        $this->Validation->ApplyRule('RecipientUserID', 'OneOrMoreArrayItemRequired');

        // Add insert/update fields.

        //$this->AddUpdateFields($FormPostValues);

        // Validate the form posted values.
        $this->Validation->results(true);

        if ($this->validate($FormPostValues)) {
            // Get the form field values.
            $Fields = $this->Validation->validationFields();
            $Current = $this->getID($Fields['UserID'], $Fields['BadgeID']);

            if (isset($Fields['Attributes']) && is_array($Fields['Attributes'])) {
                $Fields['Attributes'] = dbencode($Fields['Attributes']);
            }

            if ($Current['_New']) {
                $this->addInsertFields($Fields);
                $this->SQL->insert($this->Name, $Fields);
            } else {
                $Where = array(
                    'UserID' => $Fields['UserID'],
                    'BadgeID' => $Fields['BadgeID']);
                $this->SQL->put($this->Name, $Fields, $Where);
            }

            // Update the cached recipient count on the badge
            $RecipientCount = $this->recipientCount($Fields['BadgeID']);
            $this->SQL->update('Badge')
                ->set('CountRecipients', $RecipientCount)
                ->where('BadgeID', $Fields['BadgeID'])
                ->put();
        } else {
            $Message = "Couldn't save UserBadge ".print_r($FormPostValues, true).' '.$this->Validation->resultsText();
            LogException(new Exception($Message));

            return false;
        }

        return true;
    }
}
