<?php
/**
 * UserBadge Model.
 *
 * @package Reputation
 */

// We can't rely on our autoloader in a plugin.
require_once(dirname(__FILE__).'/class.badgesappmodel.php');
 
/**
 * Deals with associating users with badges.
 *
 * @package Reputation
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
    public function AddTimeoutEvent($UserID, $BadgeID, $NewTimestamp) {
        // Get badge
        $Badge = $this->GetBadgeID($BadgeID, DATASET_TYPE_ARRAY);
        if (!$Badge) {
            return false;
        }

        // Get user progress
        $UserBadge = $this->GetID($UserID, GetValue('BadgeID', $Badge));

        // Grab relevant parameters
        $Timeout = GetValue('Timeout', $UserBadge['Attributes'], 0);
        $Threshold = GetValue('Threshold', $Badge, false);

        // Get new timestamp and add to events
        $Events = GetValue('Events', $UserBadge['Attributes'], array());
        $NewTimestamp = (is_numeric($NewTimestamp)) ? $NewTimestamp : strtotime($NewTimestamp);
        $Events[] = $NewTimestamp;

        // Only keep events that happened within last $MaxSeconds from $NewTimestamp
        foreach ($Events as $Key => $Timestamp) {
            if ($Timestamp + $Timeout < $NewTimestamp) {
                unset($Events[$Key]);
            }
        }

        // Save new event list
        SetValue('Events', $UserBadge['Attributes'], $Events);
        $this->Save($UserBadge);

        // If we've achieved threshold, give badge to user
        if ($Threshold && count($Events) >= $Threshold) {
            $this->Give($UserID, $BadgeID);
        }

        return count($Events);
    }

    /**
     * Get number of badges this user has received.
     *
     * @since 1.0.0
     * @access public
     */
    public function BadgeCount($UserID = '') {
        return $this->GetCount(array('UserID' => $UserID, 'DateCompleted is not null' => null));
    }

    public static function BadgeName($Badge) {
        $Name = $Badge['Name'];
        $Threshold = $Badge['Threshold'];

        if (!$Threshold) {
            return T($Name);
        }

        if (strpos($Name, $Threshold) !== false) {
            $Code = str_replace($Threshold, '%s', $Name);

            if ($Threshold == 1) {
                return Plural($Threshold, $Code, $Code.'s');
            } else {
                return Plural($Threshold, rtrim($Code, 's'), $Code);
            }
        } else {
            return T($Name);
        }
    }

    public function BombAnniversary($Limit = 100) {
        // Make sure no one gets a notification.
        SaveToConfig(array(
            'Preferences.Email.Badge' => false,
            'Preferences.Popup.Badge' => false
            ), '', false);

        $BadgeModel = new BadgeModel();

        // Grab the first comment badge.
        $Badge = $BadgeModel->GetID('anniversary');
        $BadgeID = $Badge['BadgeID'];

        // Grab all of the users that have been around for at least a year.
        $this->SQL->Select('u.*')
            ->From('User u')
            ->Join('UserBadge ub', "u.UserID = ub.UserID and ub.BadgeID = $BadgeID", 'left')
            ->Where('u.DateFirstVisit <=', Gdn_Format::ToDateTime(strtotime('-1 year')))
            ->Where('ub.BadgeID is null')
            ->Limit($Limit);

        $Data = $this->SQL->Get()->ResultArray();

        $Hooks = new BadgesHooks();

        $Count = 0;
        foreach ($Data as $Row) {
//            $Args = array('UserID' => $Row['UserID'], 'Fields' => array('CountComments' => $Row['CountComments']));
            Gdn::Session()->User = $Row;
            $Hooks->Anniversaries($this, array());

            $Count++;
        }
        return $Count;
    }

    public function BombComment($Limit = 100) {
        // Make sure no one gets a notification.
        SaveToConfig(array(
            'Preferences.Email.Badge' => false,
            'Preferences.Popup.Badge' => false
            ), '', false);

        $BadgeModel = new BadgeModel();

        // Grab the first comment badge.
        $Badge = $BadgeModel->GetID('comment');
        $BadgeID = $Badge['BadgeID'];

        // Grab all of the users that have at least one comment, but don't have this badge.
        $Data = $this->SQL->Select('u.*')
            ->From('User u')
            ->Join('UserBadge ub', "u.UserID = ub.UserID and ub.BadgeID = $BadgeID", 'left')
            ->Where('u.CountComments >=', 1)
            ->Where('ub.BadgeID is null')
            ->Limit($Limit)
            ->Get()->ResultArray();

        $Hooks = new BadgesHooks();

        $Count = 0;
        foreach ($Data as $Row) {
            $Args = array('UserID' => $Row['UserID'], 'Fields' => array('CountComments' => $Row['CountComments']));

            $Hooks->UserModel_AfterSetField_Handler($this, $Args);

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
    public function DeclineRequest($UserID, $BadgeID) {
        $UserBadge = $this->GetID($UserID, $BadgeID);
        SetValue('Declined', $UserBadge, 1);
        $this->Save($UserBadge);
    }

    /**
     * Get badges for a single user.
     *
     * @since 1.0.0
     * @access public
     */
    public function GetBadges($UserID = '') {
        return $this->SQL
            ->Select('b.*')
            ->Select('ub.Reason')
            ->Select('ub.ShowReason')
            ->Select('ub.DateCompleted')
            ->From('UserBadge ub')
            ->Join('Badge b', 'b.BadgeID = ub.BadgeID', 'left')
            ->Where('ub.UserID', $UserID)
            ->Where('ub.DateCompleted is not null')
            ->OrderBy('ub.DateCompleted', 'desc')
            ->Get();
    }

    /**
     * Get badge data for single user/badge association.
     *
     * @since 1.0.0
     * @access public
     */
    public function GetID($UserID, $BadgeID) {
        $BadgeID = $this->GetBadgeID($BadgeID);

        $Result = $this->SQL->GetWhere('UserBadge', array('UserID' => $UserID, 'BadgeID' => $BadgeID))->FirstRow(DATASET_TYPE_ARRAY);

        if (!$Result) {
            $Result = array('UserID' => $UserID, 'BadgeID' => $BadgeID, '_New' => true);
        } else {
            $Result['_New'] = false;
        }

        $Attributes = GetValue('Attributes', $Result);
        if ($Attributes) {
            $Attributes = @unserialize($Attributes);
        } else {
            $Attributes = array();
        }
        SetValue('Attributes', $Result, $Attributes);
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
    protected function GetBadgeID($BadgeID, $Send = false) {
        if ($Send) {
            $BadgeModel = new BadgeModel();
            $Badge = $BadgeModel->GetID($BadgeID);

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
            $Badge = $BadgeModel->GetID($BadgeID);

            return $Badge['BadgeID'];
        }
    }

    /**
     * Get all current badge requests.
     *
     * @since 1.1
     * @access public
     */
    public function GetRequests() {
        return $this->SQL
            ->Select('b.*')
            ->Select('b.Name', '', 'BadgeName')
            ->Select('ub.UserID')
            ->Select('ub.RequestReason')
            ->Select('ub.DateRequested')
            ->From('UserBadge ub')
            ->Join('Badge b', 'b.BadgeID = ub.BadgeID', 'left')
            ->Where('ub.Declined', 0)
            ->Where('ub.DateCompleted is null')
            ->Where('ub.DateRequested is not null')
            ->OrderBy('ub.DateRequested', 'asc')
            ->Get();
    }

    /**
     * Get users who have an badge.
     *
     * @since 1.0.0
     * @access public
     */
    public function GetUsers($BadgeID, $Options = array()) {
        // Get numeric ID
        $BadgeID = $this->GetBadgeID($BadgeID);
        if (!$BadgeID) {
            return false;
        }

        // Get query options
        $Limit = GetValue('Limit', $Options, 5);

        return $this->SQL
            ->Select('u.*')
            ->Select('ub.DateCompleted')
            ->From('UserBadge ub')
            ->Join('User u', 'u.UserID = ub.UserID', 'left')
            ->Where('ub.BadgeID', $BadgeID)
            ->Where('ub.DateCompleted is not null')
            ->OrderBy('ub.DateCompleted', 'desc')
            ->Limit($Limit)
            ->Get();
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
    public function Give($UserID, $BadgeID, $Reason = '') {
        if (C('Badges.Disabled')) {
            return false;
        }

        static $BadgeGiven = false;

        $Badge = $this->GetBadgeID($BadgeID, DATASET_TYPE_ARRAY);
        $BadgeID = $Badge['BadgeID'];

        $UserBadge = $this->GetID($UserID, GetValue('BadgeID', $Badge));

        // Allow badges to be disabled
        if (!GetValue('Active', $Badge)) {
            return false;
        }

        if (GetValue('DateCompleted', $UserBadge, null) != null) {
            $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
            $this->Validation->AddValidationResult('BadgeID', '@'.sprintf(T('The %s badge has already been given to %s.'), $Badge['Name'], $User['Name']));

            return false;
        }

        $UserBadge['Reason'] = $Reason;
        $UserBadge['DateCompleted'] = Gdn_Format::ToDateTime();

        $PointsText = '';
        $Saved = $this->Save($UserBadge);
        if ($Saved) {
            $Points = $Badge['Points'];
            if ($Points != 0) {
                $PointsText = ' '.Plural($Points, '%+d point', '%+d points');
                self::GivePoints($UserID, $Points, 'Badges');
            }

            // Update the user's count.
            $CountBadges = $this->BadgeCount($UserID);
            Gdn::UserModel()->SetField($UserID, 'CountBadges', $CountBadges);

            // Update the badge's count.
            $RecipientCount = $this->RecipientCount($Badge['BadgeID']);
            $this->SQL->Update('Badge')
                ->Set('CountRecipients', $RecipientCount)
                ->Where('BadgeID', $Badge['BadgeID'])
                ->Put();

            // Notify people of the badge.
            $HeadlineFormat = T('HeadlineFormat.Badge', '{ActivityUserID,You} earned the <a href="{Url,html}">{Data.Name,text}</a> badge.');
            if (StringBeginsWith(Gdn::Locale()->Locale, 'en', true)) {
                $BadgeBody = GetValue('Body', $Badge);
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
                 'Data' => array('Name' => self::BadgeName($Badge))
            );

            // Photo optional
            if ($Photo = GetValue('Photo', $Badge)) {
                SetValue('Photo', $Activity, Gdn_Upload::Url($Photo));
            }

            $ActivityModel = new ActivityModel();

            if (!$this->NoSpam || !$BadgeGiven) {
                // Notify the user of their badge.
                $ActivityModel->Queue($Activity, 'Badge', array('Force' => true));
            }

            // Notify everyone else of your badge.
            $Activity['NotifyUserID'] = ActivityModel::NOTIFY_PUBLIC;
            $Activity['Story'] = $Badge['Body'];
            $ActivityModel->Queue($Activity, false, array('GroupBy' => array('ActivityTypeID', 'RecordID', 'RecordType')));

            $ActivityModel->SaveQueue();
            $BadgeGiven = true;

            // Hook
            $this->EventArguments['UserBadge'] = $UserBadge;
            $this->FireEvent('AfterGive');
        }

        return $Saved;
    }

    /**
     * Add points to a user's total. Alias since 1.2.
     *
     * @since 1.0.0
     * @access public
     */
    public static function GivePoints($UserID, $Points, $Source = 'Other', $Timestamp = false) {
        UserModel::GivePoints($UserID, $Points, $Source, $Timestamp);
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
    public function Increment($UserID, $BadgeID, $ColumnName, $Inc = 1) {
        $BadgeID = $this->GetBadgeID($BadgeID);
        $UserBadge = $this->GetID($UserID, $BadgeID);
        $Curr = GetValue($ColumnName, $UserBadge['Attributes'], 0);
        $Curr += $Inc;
        SetValue($ColumnName, $UserBadge['Attributes'], $Curr);
        $this->Save($UserBadge);
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
    public function RecipientCount($BadgeID = '') {
        $BadgeID = $this->GetBadgeID($BadgeID);
        return $this->GetCount(array('BadgeID' => $BadgeID, 'DateCompleted is not null' => null));
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
    public function Request($UserID, $BadgeID, $Reason = '') {
        $UserBadge = $this->GetID($UserID, $BadgeID);
        $Badge = $this->GetBadgeID($BadgeID, DATASET_TYPE_ARRAY);
        $New = true;

        // Check if request is already pending
        if (GetValue('DateRequested', $UserBadge) && !GetValue('Declined', $UserBadge)) {
            $New = false;
        }

        // Check for declined requests in cooldown period
        $CoolDownDays = C('Reputation.Badges.RequestCoolDownDays', 30);
        $CooledDown = (strtotime(GetValue('DateRequested', $UserBadge)) > strtotime($CoolDownDays.' days ago'));
        if (!$CooledDown && GetValue('Declined', $UserBadge)) {
            $New = false;
        }

        if ($New) {
            // Create the request
            SetValue('DateRequested', $UserBadge, Gdn_Format::ToDateTime());
            SetValue('RequestReason', $UserBadge, $Reason);
            SetValue('Declined', $UserBadge, 0);
            $this->Save($UserBadge);

            // Prep activity
            $ActivityModel = new ActivityModel();
            $HeadlineFormat = T('HeadlineFormat.BadgeRequest', '{ActivityUserID,You} requested the <a href="{Url,html}">{Data.Name,text}</a> badge.');
            $Activity = array(
                 'ActivityType' => 'BadgeRequest',
                 'ActivityUserID' => $UserID,
                 'HeadlineFormat' => $HeadlineFormat,
                 'Story' => GetValue('Body', $Badge),
                 'RecordType' => 'BadgeRequest',
                 'RecordID' => $BadgeID,
                 'Route' => "/badge/requests",
                 'Data' => array('Name' => $Badge['Name'])
            );

            // Optional photo
            if ($Photo = GetValue('Photo', $Badge)) {
                SetValue('Photo', $Activity, Gdn_Upload::Url($Photo));
            }

            // Grab all of the users that need to be notified.
            $Data = $this->SQL
                ->WhereIn('Name', array('Preferences.Email.BadgeRequest', 'Preferences.Popup.BadgeRequest'))
                ->Get('UserMeta')->ResultArray();

            // Build our notification queue
            $NotifyUsers = array();
            foreach ($Data as $Row) {
                $UserID = GetValue('UserID', $Row);
                $Name = GetValue('Name', $Row);
                if (strpos($Name, '.Email.') !== false) {
                    $NotifyUsers[$UserID]['Emailed'] = ActivityModel::SENT_PENDING;
                } elseif (strpos($Name, '.Popup.') !== false) {
                    $NotifyUsers[$UserID]['Notified'] = ActivityModel::SENT_PENDING;
                }
            }

            // Dispatch notifications
            foreach ($NotifyUsers as $UserID => $Prefs) {
                $Activity['NotifyUserID'] = $UserID;
                $Activity['Emailed'] = GetValue('Emailed', $Prefs, false);
                $Activity['Notified'] = GetValue('Notified', $Prefs, false);
                $ActivityModel->Queue($Activity);
            }
            $ActivityModel->SaveQueue();
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
    public function Revoke($UserID, $BadgeID) {
        $Badge = $this->GetBadgeID($BadgeID, DATASET_TYPE_ARRAY);
        if (!$Badge) {
            return false;
        }

        // Delete it.
        $this->Delete(array('UserID' => $UserID, 'BadgeID' => $BadgeID));

        // Adjust user's badge count
        $BadgeCount = $this->BadgeCount($UserID);
        Gdn::UserModel()->SetField($UserID, 'CountBadges', $BadgeCount);

        // Adjust's badge's recipient count
        $RecipientCount = $this->RecipientCount($BadgeID);
        $this->SQL->Update('Badge')
            ->Set('CountRecipients', $RecipientCount)
            ->Where('BadgeID', $BadgeID)
            ->Put();

        $Points = $Badge['Points'];
        if ($Points != 0) {
            self::GivePoints($UserID, -$Points, 'Badges');
        }

        return $UserID;
    }

    /**
     * Save given user badge.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $FormPostValues Values submitted via form.
     * @return bool Whether save was successful.
     */
    public function Save($FormPostValues) {
        $Session = Gdn::Session();

        // Define the primary key in this model's table.
        $this->DefineSchema();

        // Add & apply any extra validation rules
        //$this->Validation->ApplyRule('BadgeID', 'Integer');

        // Make sure that there is at least one recipient
//        $this->Validation->AddRule('OneOrMoreArrayItemRequired', 'function:ValidateOneOrMoreArrayItemRequired');
//        $this->Validation->ApplyRule('RecipientUserID', 'OneOrMoreArrayItemRequired');

        // Add insert/update fields.

        //$this->AddUpdateFields($FormPostValues);

        // Validate the form posted values.
        $this->Validation->Results(true);

        if ($this->Validate($FormPostValues)) {
            // Get the form field values.
            $Fields = $this->Validation->ValidationFields();
            $Current = $this->GetID($Fields['UserID'], $Fields['BadgeID']);

            if (isset($Fields['Attributes']) && is_array($Fields['Attributes'])) {
                $Fields['Attributes'] = serialize($Fields['Attributes']);
            }

            if ($Current['_New']) {
                $this->AddInsertFields($Fields);
                $this->SQL->Insert($this->Name, $Fields);
            } else {
                $Where = array(
                    'UserID' => $Fields['UserID'],
                    'BadgeID' => $Fields['BadgeID']);
                $this->SQL->Put($this->Name, $Fields, $Where);
            }

            // Update the cached recipient count on the badge
            $RecipientCount = $this->RecipientCount($Fields['BadgeID']);
            $this->SQL->Update('Badge')
                ->Set('CountRecipients', $RecipientCount)
                ->Where('BadgeID', $Fields['BadgeID'])
                ->Put();
        } else {
            $Message = "Couldn't save UserBadge ".print_r($FormPostValues, true).' '.$this->Validation->ResultsText();
            LogException(new Exception($Message));

            return false;
        }

        return true;
    }
}
