<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Handles data access for warnings.
 */
class WarningModel extends UserNoteModel {

    /// Properties ///

    /**
     * @var array See {@link WarningModel::special()}.
     */
    protected static $special;

    /**
     * Let the warned user know who warned them.
     *
     * @var bool
     */
    public $HideWarnerIdentity = false;

    /**
     * Send the warning to the user's inbox.
     *
     * @var bool
     */
    public $NotifyWithMessage = true;

    /// Methods ///

    /**
     * Initialize an instance of the {@link WarningModel}.
     */
    public function __construct() {
        parent::__construct();
        $this->fireEvent('Init');
    }

    /**
     * Notify a user of a warning with an activity.
     *
     * @param array|int $warning The warning to notify the user about.
     * @return int
     */
    protected function notifyWithActivity($warning) {
        if (!is_array($warning)) {
            $warning = $this->getID($warning);
        }

        $session = Gdn::session();

        // Let the warned user know who warned them, or not.
        $warnerIdentity = $session->UserID;

        // Use plugin icon as photo.
        $warnings2IconPath = preg_replace('/https?\:/i', '', asset('/plugins/Warnings2/icon.png', true));

        $activity = [
            'ActivityType' => 'Warning',
            'ActivityUserID' => $warnerIdentity,
            'HeadlineFormat' => t('HeadlineFormat.Warning.ToUser.Popup', 'You\'ve been <a href="{Url,html}" class="Popup">warned</a>.'),
            'RecordType' => $warning['RecordType'],
            'RecordID' => $warning['RecordID'],
            'Story' => $warning['Body'],
            'Format' => $warning['Format'],
            'Route' => "/profile/viewnote/{$warning['WarningID']}",
            'NotifyUserID' => $warning['UserID'],
            'Notified' => true,
            'Photo' => $warnings2IconPath
        ];

        $activityModel = new ActivityModel();
        $result = $activityModel->save($activity, false, ['Force' => true]);

        $savedActivityID = null;
        if (isset($result['ActivityID'])) {
            $savedActivityID = $result['ActivityID'];
        }

        return $savedActivityID;
    }

    /**
     * Notify a warned user with a private message.
     *
     * @param array|int $warning The warning to notify the user about.
     *
     * @return bool|int
     * @throws Gdn_UserException Throws an exception when there was an error creating the private conversation.
     */
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
        $row = [
            'Subject' => t('HeadlineFormat.Warning.ToUser', "You've been warned."),
            'Type' => 'warning',
            'ForeignID' => "warning-{$warningID}",
            'Body' => $warning['Body'],
            'Format' => $warning['Format'],
            'RecipientUserID' => (array)$warning['UserID']
        ];

        $conversationID = $model->save($row, $messageModel);

        if (!$conversationID) {
            throw new Gdn_UserException($model->Validation->resultsText());
        }
        return $conversationID;
    }

    /**
     * Process all of the pending warnings.
     *
     * @return array Returns an array about the process actions.
     */
    public function processAllWarnings() {
        $alerts = $this->SQL->getWhere('UserAlert', ['TimeExpires <' => time()])->resultArray();

        $result = [];
        foreach ($alerts as $alert) {
            $userID = $alert['UserID'];
            $processed = $this->processWarnings($alert);
            $result[$userID] = $processed;
        }
        return $result;
    }

    /**
     * Process all of the warnings for a single user.
     *
     * @param array|int $userID The user to process the warnings for.
     *
     * @return array Returns an array of processing information.
     */
    public function processWarnings($userID) {
        $alertModel = new UserAlertModel();

        if (is_array($userID)) {
            if (array_key_exists('WarningLevel', $userID)) {
                $alert = $userID;
                $userID = $alert['UserID'];
            }
        }

        // Grab the user's current alert level.
        if (!isset($alert)) {
            $alert = $alertModel->getID($userID);
        }

        if (!$alert) {
            return [];
        }
        unset($alert['DateInserted']); // not updating this column

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
        $banned = false;
        if ($warningLevel >= 5) {
            // The user is banned.
            $banned = true;
        }

        $punished = 0;
        if (!$banned && $warningLevel >= 3) {
            // The user is punished (jailed).
            $punished = 1;
        }

        $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);

        $set = [];
        if (BanModel::isBanned($user['Banned'], BanModel::BAN_WARNING) !== $banned) {
            $set['Banned'] = BanModel::setBanned($user['Banned'], $banned, BanModel::BAN_WARNING);
        }
        if ($user['Punished'] != $punished) {
            $set['Punished'] = $punished;
        }

        if (!empty($set)) {
            Gdn::userModel()->setField($userID, $set);
        }

        return ['WarnLevel' => $warningLevel, 'Set' => $set];
    }

    /**
     * Checks record type and returns Model object representative of RecordType.
     *
     * Returns false if RecordType is not discussion or comment.
     *
     * @param string $recordType The type of record to get the model for.
     *
     * @return Gdn_Model Returns the model that handles {@link $recordType}.
     */
    public function getModel($recordType) {
        if ($recordType === 'discussion') {
            return new DiscussionModel();
        } elseif ($recordType === 'comment') {
            return new CommentModel();
        }
        return null;
    }

    /**
     * Reverse a warning.
     *
     * @param array|int $warning The warning to reverse.
     *
     * @return boolean Whether the warning was reversed.
     */
    public function reverse($warning) {
        if (!is_array($warning)) {
            $warning = $this->getID($warning);
        }

        if (!$warning) {
            throw notFoundException('Warning');
        }

        if (val('Reversed', $warning)) {
            $this->Validation->addValidationResult('Reversed', 'The warning was already reversed.');
            return false;
        }

        // First, reverse the warning.
        $this->setField($warning['UserNoteID'], 'Reversed', true);

        $model = $this->getModel($warning['RecordType']);
        if ($model instanceof Gdn_Model) {
            $record = $model->getID($warning['RecordID']);

            if (isset($record->Attributes['WarningID'])) {
                $model->saveToSerializedColumn('Attributes', $warning['RecordID'], 'WarningID', false);
            }
        }

        // Reverse the amount of time on the warning and its points.
        $expiresTimespan = val('ExpiresTimespan', $warning, '0');
        $points = val('Points', $warning, 0);

        $alertModel = new UserAlertModel();
        $alert = $alertModel->getID($warning['UserID']);
        if ($alert) {
            unset($alert['DateInserted']);
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
                $this->Validation->addValidationResult($alertModel->validationResults());
            } else {
                $this->processWarnings($alert);
            }
        }
        return true;
    }

    /**
     * Save a warning.
     *
     * @param array $data The warning data to save.
     *
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
     * @param array|false $settings Additional settings to modify the save behaviour.
     *
     * @return int|false Returns the ID of the warning or false if there was a problem saving it.
     */
    public function save($data, $settings = false) {
        $userID = val('UserID', $data);
        unset($data['AttachRecord']);

        // Coerce the data.
        $data['Type'] = 'warning';
        if (isset($data['WarningTypeID'])) {
            $warningType = $this->SQL->getWhere('WarningType', ['WarningTypeID' => $data['WarningTypeID']])->firstRow(DATASET_TYPE_ARRAY);
            if (!$warningType) {
                $this->Validation->addValidationResult('WarningTypeID', 'Invalid warning type');
            } else {
                touchValue('Points', $data, $warningType['Points']);
                if ($warningType['ExpireNumber'] > 0) {
                    touchValue(
                        'ExpiresString',
                        $data,
                        plural(
                            $warningType['ExpireNumber'],
                            '%s '.rtrim($warningType['ExpireType'], 's'),
                            '%s '.$warningType['ExpireType']
                        )
                    );
                    $seconds = strtotime($warningType['ExpireNumber'].' '.$warningType['ExpireType'], 0);
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
            } elseif (!validateRequired(val('ExpiresString', $data))) {
                $days = round($data['ExpiresTimespan'] / strtotime('1 day', 0));
                touchValue('ExpiresString', $data, plural($days, '%s day', '%s days'));
            }
        }

        // First we save the warning.
        $iD = (int)parent::save($data, $settings);
        if (!$iD) {
            return false;
        }
        $data['WarningID'] = $iD;

        $event = [
            'Warning' => $data,
            'WarningID' => $iD
        ];

        // Attach the warning to the source record.
        $recordType = ucfirst(val('RecordType', $data));
        $recordID = val('RecordID', $data);
        if (in_array($recordType, ['Discussion', 'Comment', 'Activity']) && $recordID) {
            $modelClass = $recordType.'Model';
            /* @var Gdn_Model $model */
            $model = new $modelClass;
            $model->saveToSerializedColumn('Attributes', $recordID, 'WarningID', $iD);

            $event = array_merge($event, [
                'RecordType' => $recordType,
                'RecordID' => $recordID
            ]);
        }

        if ($this->NotifyWithMessage && class_exists('ConversationModel')) {
            // Send the private message.
            $conversationID = $this->notifyWithMessage($data);
            if ($conversationID) {
                // Save the conversation link back to the warning.
                $this->setField($iD, ['ConversationID' => $conversationID]);
                $event['ConversationID'] = $conversationID;
            }
        } else {
            $activityID = $this->notifyWithActivity($data);
            if ($activityID) {
                $this->setField($iD, ['ActivityID' => $activityID]);
                $event['ActivityID'] = $activityID;
            }
        }

        // Increment the user's alert level.
        $alertModel = new UserAlertModel();
        $alert = $alertModel->getID($userID);
        if (!$alert) {
            $alert = ['UserID' => $userID];
        } else {
            unset($alert['DateInserted']);
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
            $alertModel->insert($set);
        }

        $event['Alert'] = $alert;

        // Process this user's warnings.
        $processed = $this->processWarnings($userID);

        if (BanModel::isBanned(valr('Set.Banned', $processed), BanModel::BAN_WARNING)) {
            // Update the user note to indicate the ban.
            $this->saveToSerializedColumn('Attributes', $iD, 'Banned', true);
            $event['Banned'] = true;
        }

        // Do we allow hooking for this save?
        if (val('Event', $settings, true)) {
            $this->EventArguments = array_merge($this->EventArguments, $event);
            $this->fireEvent('WarningAdded');
        }

        return $iD;
    }

    /**
     * Return the information for special meanings of total warning level values.
     *
     * @return array Returns an array in the form `[level => ['Label' => value, 'Title' => value]]`.
     */
    public static function special() {
        if (self::$special === null) {
            self::$special = [
                3 => ['Label' => t('Jail'), 'Title' => t('Jailed users have reduced abilities.')],
                5 => ['Label' => t('Ban'), 'Title' => t('Banned users can no longer access the site.')]
            ];
        }
        return self::$special;
    }
}