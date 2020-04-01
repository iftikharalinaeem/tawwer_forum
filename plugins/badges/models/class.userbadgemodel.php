<?php
/**
 * @copyright 2011-2015 Vanilla Forums, Inc.
 * @package Badges
 */

/**
 * Deals with associating users with badges.
 */
class UserBadgeModel extends Gdn_Model {

    /** @var bool  */
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
     * @param int $userID
     * @param mixed $badgeID int or string identifier.
     * @param mixed $newTimestamp Unix timestamp or date string.
     * @return int Number of timestamps stored within $timeout seconds.
     */
    public function addTimeoutEvent($userID, $badgeID, $newTimestamp) {
        // Get badge
        $badge = $this->getBadgeID($badgeID, DATASET_TYPE_ARRAY);
        if (!$badge) {
            return false;
        }

        // Get user progress
        $userBadge = $this->getByUser($userID, $badgeID);

        // Grab relevant parameters
        $timeout = val('Timeout', $badge['Attributes'], 0);
        $threshold = val('Threshold', $badge, false);

        // Get new timestamp and add to events
        $events = val('Events', $userBadge['Attributes'], []);
        $newTimestamp = (is_numeric($newTimestamp)) ? $newTimestamp : strtotime($newTimestamp);
        $events[] = $newTimestamp;

        // Only keep events that happened within last $MaxSeconds from $NewTimestamp
        foreach ($events as $key => $timestamp) {
            if ($timestamp + $timeout < $newTimestamp) {
                unset($events[$key]);
            }
        }

        // Save new event list
        setValue('Events', $userBadge['Attributes'], $events);
        $this->save($userBadge);

        // If we've achieved threshold, give badge to user
        if ($threshold && count($events) >= $threshold) {
            $this->give($userID, $badgeID);
        }

        return count($events);
    }

    /**
     * Get number of badges this user has received.
     *
     * @since 1.0.0
     * @access public
     */
    public function badgeCount($userID = '') {
        return $this->getCount(['UserID' => $userID, 'DateCompleted is not null' => null]);
    }

    /**
     *
     *
     * @param $badge
     * @return string
     */
    public static function badgeName($badge) {
        $name = $badge['Name'];
        $threshold = (string)$badge['Threshold'];

        if (!$threshold) {
            return t($name);
        }

        $formattedThreshold = number_format($threshold);

        if (strpos($name, $formattedThreshold) !== false || strpos($name, $threshold) !==false) {
            $code = strpos($name, $formattedThreshold) !== false ? str_replace($formattedThreshold, '%s', $name) :
                str_replace($threshold, '%s', $name);
            if ($threshold == 1) {
                return plural($formattedThreshold, $code, $code.'s');
            } else {
                return plural($formattedThreshold, rtrim($code, 's'), $code);
            }
        } else {
            return t($name);
        }
    }

    /**
     *
     *
     * @param int $limit
     * @return int
     * @throws Exception
     */
    public function bombAnniversary($limit = 100) {
        // Make sure no one gets a notification.
        saveToConfig([
            'Preferences.Email.Badge' => false,
            'Preferences.Popup.Badge' => false
            ], '', false);

        $badgeModel = new BadgeModel();

        // Grab the first comment badge.
        $badge = $badgeModel->getID('anniversary');
        $badgeID = $badge['BadgeID'];

        // Grab all of the users that have been around for at least a year.
        $this->SQL->select('u.*')
            ->from('User u')
            ->join('UserBadge ub', "u.UserID = ub.UserID and ub.BadgeID = $badgeID", 'left')
            ->where('u.DateFirstVisit <=', Gdn_Format::toDateTime(strtotime('-1 year')))
            ->where('ub.BadgeID is null')
            ->limit($limit);

        $data = $this->SQL->get()->resultArray();

        $hooks = new BadgesHooks();

        $count = 0;
        foreach ($data as $row) {
//            $Args = array('UserID' => $Row['UserID'], 'Fields' => array('CountComments' => $Row['CountComments']));
            Gdn::session()->User = $row;
            $hooks->anniversaries($this, []);

            $count++;
        }
        return $count;
    }

    /**
     *
     *
     * @param int $limit
     * @return int
     * @throws Exception
     */
    public function bombComment($limit = 100) {
        // Make sure no one gets a notification.
        saveToConfig([
            'Preferences.Email.Badge' => false,
            'Preferences.Popup.Badge' => false
            ], '', false);

        $badgeModel = new BadgeModel();

        // Grab the first comment badge.
        $badge = $badgeModel->getID('comment');
        $badgeID = $badge['BadgeID'];

        // Grab all of the users that have at least one comment, but don't have this badge.
        $data = $this->SQL->select('u.*')
            ->from('User u')
            ->join('UserBadge ub', "u.UserID = ub.UserID and ub.BadgeID = $badgeID", 'left')
            ->where('u.CountComments >=', 1)
            ->where('ub.BadgeID is null')
            ->limit($limit)
            ->get()->resultArray();

        $hooks = new BadgesHooks();

        $count = 0;
        foreach ($data as $row) {
            $args = ['UserID' => $row['UserID'], 'Fields' => ['CountComments' => $row['CountComments']]];
            $hooks->userModel_afterSetField_handler($this, $args);
            $count++;
        }
        return $count;
    }

    /**
     * Decline a user's badge request.
     *
     * @since 1.1
     * @access public
     */
    public function declineRequest($userID, $badgeID) {
        $userBadge = $this->getID($userID, $badgeID);
        setValue('Declined', $userBadge, 1);
        setValue('Status', $userBadge, 'declined');
        $this->save($userBadge);
    }

    /**
     * Get badges for a single user.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $userID
     * @param int $limit
     */
    public function getBadges($userID = '', int $limit = null) {
        return Gdn::sql()->select('b.*')
            ->select('ub.Reason')
            ->select('ub.ShowReason')
            ->select('ub.DateCompleted')
            ->from('UserBadge ub')
            ->join('Badge b', 'b.BadgeID = ub.BadgeID', 'left')
            ->where('b.Active', 1)
            ->where('ub.UserID', $userID)
            ->where('ub.DateCompleted is not null')
            ->limit($limit ?? false)
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
    public function getByUser($userID, $badgeID) {
        $badgeID = $this->getBadgeID($badgeID);

        $result = $this->SQL->getWhere('UserBadge', ['UserID' => $userID, 'BadgeID' => $badgeID])->firstRow(DATASET_TYPE_ARRAY);

        if (!$result) {
            $result = ['UserID' => $userID, 'BadgeID' => $badgeID, '_New' => true];
        } else {
            $result['_New'] = false;
        }

        $attributes = val('Attributes', $result);
        if ($attributes) {
            $attributes = dbdecode($attributes);
        } else {
            $attributes = [];
        }
        setValue('Attributes', $result, $attributes);

        return $result;
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
     * @param mixed $badgeID string (slug) or int (id).
     * @param string $send What data to return. Valid options: 'Object'.
     * @return mixed BadgeID (default) or Badge dataset if $send == 'Object'.
     */
    protected function getBadgeID($badgeID, $send = false) {
        if ($send) {
            $badgeModel = new BadgeModel();
            $badge = $badgeModel->getID($badgeID);

            if ($send == DATASET_TYPE_OBJECT) {
                $badge = (object)$badge;
            }

            return $badge;
        }

        if (is_numeric($badgeID)) {
            return $badgeID;
        } elseif (is_array($badgeID))
            return $badgeID['BadgeID'];
        else {
            $badgeModel = new BadgeModel();
            $badge = $badgeModel->getID($badgeID);

            return $badge['BadgeID'];
        }
    }

    /**
     * Get all current badge requests.
     *
     * @return Gdn_DataSet
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
            ->where('ub.Status', 'pending')
            ->orderBy('ub.DateRequested', 'asc')
            ->get();
    }

    /**
     * Returns badge request count.
     *
     * @return Gdn_Dataset
     */
    public function getBadgeRequestCount() {
        return $this->getCount([
            'Status' => 'pending'
        ]);
    }

    /**
     * Get users who have an badge.
     *
     * @since 1.0.0
     * @access public
     */
    public function getUsers($badgeID, $options = []) {
        // Get numeric ID
        $badgeID = $this->getBadgeID($badgeID);
        if (!$badgeID) {
            return false;
        }

        // Get query options
        $limit = val('Limit', $options, 5);
        $offset = val('Offset', $options, 0);

        return $this->SQL
            ->select('u.*')
            ->select('ub.*')
            ->from('UserBadge ub')
            ->join('User u', 'u.UserID = ub.UserID', 'left')
            ->where('ub.BadgeID', $badgeID)
            ->where('ub.DateCompleted is not null')
            ->orderBy('ub.DateCompleted', 'desc')
            ->limit($limit, $offset)
            ->get();
    }

    /**
     * Associate a badge with a user. A badge cannot be given to a user more than once.
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $userID.
     * @param mixed $badgeID Int (id) or string (slug).
     * @param string $reason Optional explanation of why they received the badge.
     * @return bool
     */
    public function give($userID, $badgeID, $reason = '') {
        if (c('Badges.Disabled')) {
            $this->Validation->addValidationResult('BadgeID', '@'.t('Badges are globally disabled.'));

            return false;
        }

        static $badgeGiven = false;

        $badge = $this->getBadgeID($badgeID, DATASET_TYPE_ARRAY);
        $badgeID = $badge['BadgeID'];

        // Allow badges to be disabled
        if (!val('Active', $badge)) {
            $this->Validation->addValidationResult('BadgeID', '@'.sprintf(t('The %s badge is disabled.'), $badge['Name']));

            return false;
        }

        $userBadge = $this->getByUser($userID, $badgeID);

        if (val('DateCompleted', $userBadge, null) != null) {
            $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
            $this->Validation->addValidationResult(
                'BadgeID',
                '@'.sprintf(t('The %s badge has already been given to %s.'), htmlspecialchars($badge['Name']), htmlspecialchars($user['Name']))
            );

            return false;
        }

        $userBadge['Reason'] = $reason;
        $userBadge['Status'] = 'given';
        $userBadge['DateCompleted'] = Gdn_Format::toDateTime();

        $pointsText = '';
        $saved = $this->save($userBadge);
        if ($saved) {
            $points = $badge['Points'];
            if ($points != 0) {
                $pointsText = ' '.plural($points, '%+d point', '%+d points');
                self::givePoints($userID, $points, 'Badges');
            }

            // Update the user's count.
            $countBadges = $this->badgeCount($userID);
            Gdn::userModel()->setField($userID, 'CountBadges', $countBadges);

            // Update the badge's count.
            $recipientCount = $this->recipientCount($badge['BadgeID']);
            $this->SQL->update('Badge')
                ->set('CountRecipients', $recipientCount)
                ->where('BadgeID', $badge['BadgeID'])
                ->put();

            // Notify people of the badge.
            $headlineFormat = t('HeadlineFormat.Badge', '{ActivityUserID,You} earned the <a href="{Url,html}">{Data.Name,text}</a> badge.');
            if (stringBeginsWith(Gdn::locale()->Locale, 'en', true)) {
                $badgeBody = val('Body', $badge);
            } else {
                $badgeBody = '';
            }

            $activity = [
                 'ActivityType' => 'Badge',
                 'ActivityUserID' => $userID,
                 'NotifyUserID' => $userID,
                 'HeadlineFormat' => $headlineFormat,
                 'Story' => $badgeBody.$pointsText,
                 'RecordType' => 'Badge',
                 'RecordID' => $badgeID,
                 'Route' => "/badge/{$badge['Slug']}",
                 'Data' => ['Name' => self::badgeName($badge)]
            ];

            // Photo optional
            if ($photo = val('Photo', $badge)) {
                setValue('Photo', $activity, Gdn_Upload::url($photo));
            }

            $activityModel = new ActivityModel();

            if (!$this->NoSpam || !$badgeGiven) {
                // Notify the user of their badge.
                $activityModel->queue($activity, 'Badge', ['Force' => true]);
            }

            // Notify everyone else of your badge.
            $activity['NotifyUserID'] = ActivityModel::NOTIFY_PUBLIC;
            $activity['Story'] = $badge['Body'];
            $activityModel->queue($activity, false, ['GroupBy' => ['ActivityTypeID', 'RecordID', 'RecordType']]);

            $activityModel->saveQueue();
            $badgeGiven = true;

            // Hook
            $this->EventArguments['UserBadge'] = $userBadge;
            $this->fireEvent('AfterGive');
        }

        return $saved;
    }

    /**
     * Add points to a user's total. Alias since 1.2.
     *
     * @since 1.0.0
     * @access public
     */
    public static function givePoints($userID, $points, $source = 'Other', $timestamp = false) {
        UserModel::givePoints($userID, $points, $source, $timestamp);
    }

    /**
     * Increment the progress on a badge.
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $userID
     * @param mixed $badgeID Int (id) or string (slug).
     * @param string $columnName Key in attributes array to increment.
     * @param int $inc
     * @return array
     */
    public function increment($userID, $badgeID, $columnName, $inc = 1) {
        $badgeID = $this->getBadgeID($badgeID);
        $userBadge = $this->getID($userID, $badgeID);
        $curr = val($columnName, $userBadge['Attributes'], 0);
        $curr += $inc;
        setValue($columnName, $userBadge['Attributes'], $curr);
        $this->save($userBadge);

        return $userBadge;
    }

    /**
     * Get number of users who have this badge.
     *
     * @since 1.0.0
     * @access public
     * @todo Only count unique UserIDs
     *
     * @param mixed $badgeID Int (id) or string (slug).
     * @return int
     */
    public function recipientCount($badgeID = '') {
        $badgeID = $this->getBadgeID($badgeID);
        return $this->getCount(['BadgeID' => $badgeID, 'DateCompleted is not null' => null]);
    }

    /**
     * Add a user's badge request to the queue.
     *
     * Existing, unevaluated requests cannot be repeated.
     * Declined requests cannot be repeated for X days.
     *
     * @since 1.1
     * @access public
     * @param int $userID Unique.
     * @param int $badgeID Unique.
     * @param string $reason
     * @return bool Whether this is a new, valid request.
     */
    public function request($userID, $badgeID, $reason = '') {
        $userBadge = $this->getByUser($userID, $badgeID);
        $badge = $this->getBadgeID($badgeID, DATASET_TYPE_ARRAY);
        $new = true;

        // Check if request is already pending
        if (val('DateRequested', $userBadge) && !val('Declined', $userBadge)) {
            $new = false;
        }

        // Check for declined requests in cooldown period
        $coolDownDays = c('Reputation.Badges.RequestCoolDownDays', 30);
        $cooledDown = (strtotime(val('DateRequested', $userBadge)) > strtotime($coolDownDays.' days ago'));
        if (!$cooledDown && val('Declined', $userBadge)) {
            $new = false;
        }

        if ($new) {
            // Create the request
            setValue('DateRequested', $userBadge, Gdn_Format::toDateTime());
            setValue('RequestReason', $userBadge, $reason);
            setValue('Declined', $userBadge, 0);
            setValue('Status', $userBadge, 'pending');
            $this->save($userBadge);

            // Prep activity
            $activityModel = new ActivityModel();
            $headlineFormat = t('HeadlineFormat.BadgeRequest', '{ActivityUserID,You} requested the <a href="{Url,html}">{Data.Name,text}</a> badge.');
            $activity = [
                 'ActivityType' => 'BadgeRequest',
                 'ActivityUserID' => $userID,
                 'HeadlineFormat' => $headlineFormat,
                 'Story' => val('Body', $badge),
                 'RecordType' => 'BadgeRequest',
                 'RecordID' => $badgeID,
                 'Route' => "/badge/requests",
                 'Data' => ['Name' => $badge['Name']]
            ];

            // Optional photo
            if ($photo = val('Photo', $badge)) {
                setValue('Photo', $activity, Gdn_Upload::url($photo));
            }

            // Grab all of the users that need to be notified.
            $data = $this->SQL
                ->whereIn('Name', ['Preferences.Email.BadgeRequest', 'Preferences.Popup.BadgeRequest'])
                ->get('UserMeta')->resultArray();

            // Build our notification queue
            $notifyUsers = [];
            foreach ($data as $row) {
                $userID = val('UserID', $row);
                $name = val('Name', $row);
                if (strpos($name, '.Email.') !== false) {
                    $notifyUsers[$userID]['Emailed'] = ActivityModel::SENT_PENDING;
                } elseif (strpos($name, '.Popup.') !== false) {
                    $notifyUsers[$userID]['Notified'] = ActivityModel::SENT_PENDING;
                }
            }

            // Dispatch notifications
            foreach ($notifyUsers as $userID => $prefs) {
                $activity['NotifyUserID'] = $userID;
                $activity['Emailed'] = val('Emailed', $prefs, false);
                $activity['Notified'] = val('Notified', $prefs, false);
                $activityModel->queue($activity);
            }
            $activityModel->saveQueue();
        }

        return $new;
    }

    /**
     * Revoke a badge from a user.
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $userID
     * @param mixed $badgeID Int (id) or string (slug).
     */
    public function revoke($userID, $badgeID) {
        $badge = $this->getBadgeID($badgeID, DATASET_TYPE_ARRAY);
        if (!$badge) {
            return false;
        }

        // Delete it.
        $this->delete(['UserID' => $userID, 'BadgeID' => $badgeID]);

        // Adjust user's badge count
        $badgeCount = $this->badgeCount($userID);
        Gdn::userModel()->setField($userID, 'CountBadges', $badgeCount);

        // Adjust's badge's recipient count
        $recipientCount = $this->recipientCount($badgeID);
        $this->SQL->update('Badge')
            ->set('CountRecipients', $recipientCount)
            ->where('BadgeID', $badgeID)
            ->put();

        $points = $badge['Points'];
        if ($points != 0) {
            self::givePoints($userID, -$points, 'Badges');
        }

        return $userID;
    }

    /**
     * Save given user badge.
     *
     * @param array $formPostValues Values submitted via form.
     * @param array $settings Not used.
     * @return bool Whether save was successful.
     */
    public function save($formPostValues, $settings = []) {
        // Define the primary key in this model's table.
        $this->defineSchema();

        // Add & apply any extra validation rules
        //$this->Validation->applyRule('BadgeID', 'Integer');

        // Make sure that there is at least one recipient
//        $this->Validation->addRule('OneOrMoreArrayItemRequired', 'function:ValidateOneOrMoreArrayItemRequired');
//        $this->Validation->applyRule('RecipientUserID', 'OneOrMoreArrayItemRequired');

        // Add insert/update fields.

        //$this->addUpdateFields($FormPostValues);

        // Validate the form posted values.
        $this->Validation->results(true);

        if ($this->validate($formPostValues)) {
            // Get the form field values.
            $fields = $this->Validation->validationFields();
            $current = $this->getByUser($fields['UserID'], $fields['BadgeID']);

            if (isset($fields['Attributes']) && is_array($fields['Attributes'])) {
                $fields['Attributes'] = dbencode($fields['Attributes']);
            }

            if ($current['_New']) {
                $this->addInsertFields($fields);
                $fieldExist = $this->Schema->fieldExists($this->Name, $this->InsertUserID);
                if (!isset($fields['InsertUserID']) && $fieldExist) {
                    $fields['InsertUserID'] = $fields['UserID'];
                }
                $this->SQL->insert($this->Name, $fields);
            } else {
                $where = [
                    'UserID' => $fields['UserID'],
                    'BadgeID' => $fields['BadgeID']];
                $this->SQL->put($this->Name, $fields, $where);
            }

            // Update the cached recipient count on the badge
            $recipientCount = $this->recipientCount($fields['BadgeID']);
            $this->SQL->update('Badge')
                ->set('CountRecipients', $recipientCount)
                ->where('BadgeID', $fields['BadgeID'])
                ->put();
        } else {
            $message = "Couldn't save UserBadge ".print_r($formPostValues, true).' '.$this->Validation->resultsText();
            logException(new Exception($message));

            return false;
        }

        return true;
    }

    /**
     * Delete a badge request.
     *
     * @param $userID
     * @param $badgeID
     */
    public function deleteRequest($userID, $badgeID) {
        $this->delete([
            'DateCompleted is null' => null,
            'DateRequested is not null' => null,
            'UserID' => $userID,
            'BadgeID' => $badgeID
        ]);
    }
}
