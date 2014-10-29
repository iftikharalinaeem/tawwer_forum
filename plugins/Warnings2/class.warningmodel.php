<?php

/**
 * @copyright 2010-2014 Vanilla Forums Inc
 * @license Proprietary
 */
class WarningModel extends UserNoteModel {

    /// Properties ///

    protected static $_Special;

    /**
     * Let the warned user know who warned them.
     *
     * @var bool
     */
    public $HideWarnerIdentity = FALSE;

    /**
     * Send the warning to the user's inbox.
     *
     * @var bool
     */
    public $NotifyWithMessage = TRUE;

    /// Methods ///

    public function __construct() {
        parent::__construct();

        $this->FireEvent('Init');
    }

    protected function notifyWithActivity($warning) {
        if (!is_array($warning)) {
            $warning = $this->getID($warning);
        }

        $Session = Gdn::Session();

        // Let the warned user know who warned them, or not.
        $WarnerIdentity = $Session->UserID;

        // Use plugin icon as photo.
        $Warnings2IconPath = preg_replace('/https?\:/i', '', Asset('/plugins/Warnings2/icon.png', true));

        $Activity = array(
            'ActivityType' => 'Warning',
            'ActivityUserID' => $WarnerIdentity,
            'HeadlineFormat' => T('HeadlineFormat.Warning.ToUser', 'You\'ve been <a href="{Url,html}" class="Popup">warned</a>.'),
            'RecordType' => $warning['RecordType'],
            'RecordID' => $warning['RecordID'],
            'Story' => $warning['Body'],
            'Format' => $warning['Format'],
            'Route' => "/profile/viewnote/{$warning['WarningID']}",
            'NotifyUserID' => $warning['UserID'],
            'Notified' => TRUE,
            'Photo' => $Warnings2IconPath
        );

        $ActivityModel = new ActivityModel();
        $Result = $ActivityModel->Save($Activity, FALSE, array('Force' => TRUE));

        $SavedActivityID = null;
        if (isset($Result['ActivityID'])) {
            $SavedActivityID = $Result['ActivityID'];
        }

        return $SavedActivityID;
    }

    protected function notifyWithMessage($warning) {
        if (!is_array($warning)) {
            $warning = $this->getID($warning);
        }

        if (!class_exists('ConversationModel')) {
            return false;
        }

        // Send a message from the moderator to the person being warned.
        $model = new ConversationModel();
        $messageModel = new ConversationMessageModel();

        $warningID = $warning['WarningID'];
        $row = array(
            'Subject' => T('HeadlineFormat.Warning.ToUser', "You've been warned."),
            'Type' => 'warning',
            'ForeignID' => "warning-{$warningID}",
            'Body' => $warning['Body'],
            'Format' => $warning['Format'],
            'RecipientUserID' => (array)$warning['UserID']
        );

        $conversationID = $model->save($row, $messageModel);

        if (!$conversationID) {
            throw new Gdn_UserException($model->Validation->resultsText());
        }
        return $conversationID;
    }

    public function ProcessAllWarnings() {
        $alerts = $this->SQL->GetWhere('UserAlert', array('TimeExpires <' => time()))->resultArray();

        $result = array();
        foreach ($alerts as $alert) {
            $userID = $alert['UserID'];
            $processed = $this->processWarnings($alert);
            $result[$userID] = $processed;
        }
        return $result;
    }

    public function processWarnings($userID) {
        $alertModel = new UserAlertModel();

        if (is_array($userID)) {
            if (array_key_exists('WarningLevel', $userID)) {
                $alert = $userID;
            }
            $userID = $alert['UserID'];
        }

        // Grab the user's current alert level.
        if (!isset($alert)) {
            $alert = $alertModel->getID($userID);
        }

        if (!$alert) {
            return;
        }

        $now = time();

        // See if the warnings have expired.
        if ($alert['TimeWarningExpires'] < $now) {
            $alert['WarningLevel'] = 0;
            $alert['TimeWarningExpires'] = null;

            $alertModel->setTimeExpires($alert);
            $alertModel->save($alert);
        }

        $warningLevel = $alert['WarningLevel'];

        // See if there's something special to do.
        $punished = 0;
        if ($warningLevel >= 3) {
            // The user is punished (jailed).
            $punished = 1;
        }
        $banned = 0;
        if ($warningLevel >= 5) {
            // The user is banned.
            $banned = 1;
        }

        $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);

        $set = array();
        if ($user['Banned'] != $banned) {
            $set['Banned'] = $banned;
        }
        if ($user['Punished'] != $punished) {
            $set['Punished'] = $punished;
        }

        if (!empty($set)) {
            Gdn::userModel()->setField($userID, $set);
        }

        return array('WarnLevel' => $warningLevel, 'Set' => $set);
    }

    /**
     *
     * Checks record type and returns Model object representative of RecordType.
     * Returns false if RecordType is not discussion or comment.
     *
     * @param string $RecordType
     * @return Model Object
     */
    public function GetModel($RecordType) {
        if ($RecordType === 'discussion') {
            return new DiscussionModel();
        } elseif ($RecordType === 'comment') {
            return new CommentModel();
        }
        return null;
    }

    /**
     * Reverse a warning.
     *
     * @param array|int $warning The warning to reverse.
     * @return boolean Whether the warning was reversed.
     */
    public function reverse($warning) {
        if (!is_array($warning)) {
            $warning = $this->getID($warning);
        }

        if (!$warning) {
            throw NotFoundException('Warning');
        }

        if (val('Reversed', $warning)) {
            $this->Validation->addValidationResult('Reversed', 'The warning was already reversed.');
            return false;
        }

        // First, reverse the warning.
        $this->setField($warning['UserNoteID'], 'Reversed', true);

        $Model = $this->GetModel($warning['RecordType']);
        if (!$Model) {
            return false;
        }

        $Record = $Model->GetID($warning['RecordID']);

        if (isset($Record->Attributes['WarningID'])) {
            $Model->saveToSerializedColumn('Attributes', $warning['RecordID'], 'WarningID', false);
        }

        // Reverse the amount of time on the warning and its points.
        $expiresTimespan = val('ExpiresTimespan', $warning, '0');
        $points = val('Points', $warning, 0);

        $alertModel = new UserAlertModel();
        $alert = $alertModel->getID($warning['UserID']);
        if ($alert) {
            $newWarningLevel = $alert['WarningLevel'] - $points;
            if ($newWarningLevel < 0) {
                $newWarningLevel = 0;
            }
            $alert['WarningLevel'] = $newWarningLevel;

            $newTimeWarningExpires = $alert['TimeWarningExpires'] - $expiresTimespan;
            if ($newTimeWarningExpires <= time()) {
                $newTimeWarningExpires = null;
            }
            $alert['TimeWarningExpires'] = $newTimeWarningExpires;
            $alertModel->setTimeExpires($alert);
            if (!$alertModel->save($alert)) {
                $this->Validation->addValidationResult($alertModel->balidationResults());
            } else {
                $this->processWarnings($alert);
            }
        }
        return true;
    }

    /**
     * @param array $data The warning data to save.
     *  - UserID: The user being warned.
     *  - Body: A private message to the user being warned.
     *  - Format: The format of the body.
     *
     *  **The following**
     *  - Points: The number of warning points.
     *  - ExpiresString: A string used for the expiry. (ex. 1 week, 3 days, etc)
     *  - ExpiresTimespan: The number of seconds until expiry.
     *
     *  **Or**
     *  - WarningTypeID: The type of warning given.
     *
     * @return type
     */
    public function save($data, $settings = false) {
        $userID = val('UserID', $data);
        unset($data['AttachRecord']);

        // Coerce the data.
        $data['Type'] = 'warning';
        if (isset($data['WarningTypeID'])) {
            $warningType = $this->SQL->getWhere('WarningType', array('WarningTypeID' => $data['WarningTypeID']))->firstRow(DATASET_TYPE_ARRAY);
            if (!$warningType) {
                $this->Validation->addValidationResult('WarningTypeID', 'Invalid warning type');
            } else {
                touchValue('Points', $data, $warningType['Points']);
                if ($warningType['ExpireNumber'] > 0) {
                    touchValue('ExpiresString', $data, Plural($warningType['ExpireNumber'], '%s ' . rtrim($warningType['ExpireType'], 's'), '%s ' . $warningType['ExpireType']));
                    $seconds = strtotime($warningType['ExpireNumber'] . ' ' . $warningType['ExpireType'], 0);
                    touchValue('ExpiresTimespan', $data, $seconds);
                }
            }
        }

        if (!isset($data['Points'])) {
            $this->Validation->addValidationResult('Points', 'ValidateRequired');
        } elseif ($data['Points']) {
            if (!validateRequired(val('ExpiresString', $data)) && !validateRequired(val('ExpiresTimespan', $data))) {
                $this->Validation->addValidationResult('ExpiresString/ExpiresNumber', 'ValidateRequired');
            } elseif (!validateRequired(val('ExpiresTimespan', $data))) {
                // Calculate the seconds from the string.
                $seconds = strtotime(val('ExpiresString', $data), 0);
                touchValue('ExpiresTimespan', $data, $seconds);
            } elseif (!ValidateRequired(val('ExpiresString', $data))) {
                $days = round($data['ExpiresTimespan'] / strtotime('1 day', 0));
                touchValue('ExpiresString', $data, plural($days, '%s day', '%s days'));
            }
        }

        // First we save the warning.
        $ID = parent::save($data, $settings);
        if (!$ID) {
            return false;
        }
        $data['WarningID'] = $ID;

        $event = array(
            'Warning' => $data,
            'WarningID' => $ID
        );

        // Attach the warning to the source record.
        $recordType = ucfirst(val('RecordType', $data));
        $recordID = val('RecordID', $data);
        if (in_array($recordType, array('Discussion', 'Comment', 'Activity')) && $recordID) {
            $modelClass = $recordType . 'Model';
            $model = new $modelClass;
            $model->saveToSerializedColumn('Attributes', $recordID, 'WarningID', $ID);

            $event = array_merge($event, array(
                    'RecordType' => $recordType,
                    'RecordID' => $recordID
                ));
        }

        if ($this->NotifyWithMessage && class_exists('ConversationModel')) {
            // Send the private message.
            $conversationID = $this->notifyWithMessage($data);
            if ($conversationID) {
                // Save the conversation link back to the warning.
                $this->setField($ID, array('ConversationID' => $conversationID));
                $event['ConversationID'] = $conversationID;
            }
        } else {
            $activityID = $this->notifyWithActivity($data);
            if ($activityID) {
                $this->setField($ID, array('ActivityID' => $activityID));
                $event['ActivityID'] = $activityID;
            }
        }

        // Increment the user's alert level.
        $alertModel = new UserAlertModel();
        $alert = $alertModel->getID($userID);
        if (!$alert) {
            $alert = array('UserID' => $userID);
        }

        if ($data['Points']) {
            $alert['WarningLevel'] = val('WarningLevel', $alert, 0) + $data['Points'];

            $now = time();

            $expires = val('TimeWarningExpires', $alert, 0);
            if ($expires < $now) {
                $expires = $now;
            }

            $expires += $data['ExpiresTimespan'];
            $alert['TimeWarningExpires'] = $expires;

            $alertModel->setTimeExpires($alert);
        }

        if ($alert) {
            $alertModel->save($alert);
        } else {
            $set['UserID'] = $data['UserID'];
            $alertModel->Insert($set);
        }

        $event['Alert'] = $alert;

        // Process this user's warnings.
        $processed = $this->ProcessWarnings($userID);

        if (valr('Set.Banned', $processed)) {
            // Update the user note to indicate the ban.
            $this->saveToSerializedColumn('Attributes', $ID, 'Banned', true);
            $event['Banned'] = true;
        }

        // Do we allow hooking for this save?
        if (val('Event', $settings, true)) {
            $this->EventArguments = array_merge($this->EventArguments, $event);
            $this->fireEvent('WarningAdded');
        }

        return $ID;
    }

    public static function special() {
        if (self::$_Special === null) {
            self::$_Special = array(
                3 => array('Label' => T('Jail'), 'Title' => T('Jailed users have reduced abilities.')),
                5 => array('Label' => T('Ban'), 'Title' => T("Banned users can no longer access the site."))
            );
        }
        return self::$_Special;
    }

}
