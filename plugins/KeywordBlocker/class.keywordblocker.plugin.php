<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

$PluginInfo['KeywordBlocker'] = [
    'Name' => 'Keyword Blocker',
    'Description' => 'Block posts containing certain words and send them for review.',
    'Version' => '1.1',
    'RequiredApplications' => ['Vanilla' => '2.2'],
    'HasLocale' => false,
    'License' => 'GNU GPL2',
    'SettingsUrl' => '/settings/keywordBlocker',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => 'Alexandre (DaazKu) Chouinard',
    'AuthorEmail' => 'alexandre.c@vanillaforums.com',
    'AuthorUrl' => 'https://github.com/DaazKu',
];

/**
 * Class KeywordBlocker
 */
class KeywordBlockerPlugin extends Gdn_Plugin {

    const MODERATION_QUEUE = 'Pending';

    /**
     * Create a method called "keywordBlocker" on the SettingController.
     *
     * @param $sender Sending controller instance
     */
    public function settingsController_keywordBlocker_create($sender) {
        $sender->title(sprintf(t('%s settings'), t('Keyword Blocker')));
        $sender->addSideMenu('settings/keywordBlocker');

        $sender->Form = new Gdn_Form();

        $this->dispatch($sender, $sender->RequestArgs);
    }

    /**
     * Define what to do for the /index page of this plugin.
     *
     * @param $sender Sending controller instance
     */
    public function controller_index($sender) {
        // Prevent non-admins from accessing this page
        $sender->permission('Garden.Settings.Manage');
        $sender->setData('PluginDescription', $this->getPluginKey('Description'));

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField(array(
            'KeywordBlocker.Words',
        ));

        $sender->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            $sender->Form->setData($configurationModel->Data);
        } elseif ($sender->Form->save()) {
            $sender->StatusMessage = t('Your changes have been saved.');
        }

        $sender->render($this->getView('configuration.php'));
    }

    /**
     * Add a link to the dashboard menu.
     *
     * @param $sender Sending controller instance.
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->addLink('Add-ons', t('Keyword Blocker'), 'settings/keywordBlocker', 'Garden.Settings.Manage');
    }

    /**
     * Hook on AfterValidateDiscussion event.
     *
     * Checks discussion cleanliness and send it for review if it is dirty.
     *
     * @param $sender Sending controller instance.
     * @param $args Event's arguments.
     */
    public function discussionModel_afterValidateDiscussion_handler($sender, $args) {
        $this->reviewRecordCleanliness($sender, 'Discussion', $args['DiscussionData']);
    }

    /**
     * Hook on AfterValidateComment event.
     *
     * @param $sender Sending controller instance.
     * @param $args Event's arguments.
     */
    public function commentModel_afterValidateComment_handler($sender, $args) {
        $this->reviewRecordCleanliness($sender, 'Comment', $args['CommentData']);
    }

    /**
     * Hook on AfterValidateGroup event.
     *
     * @param $sender Sending controller instance.
     * @param $args Event's arguments.
     */
    public function groupModel_afterValidateGroup_handler($sender, $args) {
        $this->reviewRecordCleanliness($sender, 'Group', $args['Fields']);
    }

    /**
     * Hook on AfterValidateGroup event.
     *
     * @param $sender Sending controller instance.
     * @param $args Event's arguments.
     */
    public function eventModel_afterValidateEvent_handler($sender, $args) {
        $this->reviewRecordCleanliness($sender, 'Event', $args['Fields']);
    }

    /**
     * Hook on BeforeRestore event.
     *
     * @param $sender Sending controller instance.
     * @param $args Event arguments.
     */
    public function logModel_beforeRestore_handler($sender, $args) {
        if (isset($sender->EventArguments['Log']['Data']['KeywordBlocker'])) {
            if ($sender->EventArguments['Handled']) {
                trace('That particular log should not have been handled by another plugin.', TRACE_WARNING);
                return;
            }

            $sender->EventArguments['Handled'] = true;
            $this->restorePostFromLog($sender->EventArguments['Log']);
            // LogModel::delete() is not used here because that function delete the linked record.
            Gdn::sql()->where('LogID', $sender->EventArguments['Log']['LogID'])->delete('Log');
        }
    }

    /**
     * Restore a post that was sent for review and that is now approved.
     *
     * @param $log Log containing the data to be restored
     */
    protected function restorePostFromLog($log) {
        $tableName = $log['RecordType'];
        $postData = $log['Data'];


        if (!empty($log['RecordID'])) {
            $oldData = $this->getRecordData($tableName, $postData[$tableName.'ID']);

            // Do not update if the post was deleted or if the log was done before the last valid update of the post.
            if (!$oldData || strtotime($oldData['DateUpdated']) > strtotime($log['DateInserted'])) {
                return;
            }
        }

        if (!isset($columns[$tableName])) {
            $columns[$tableName] = Gdn::sql()->fetchColumns($tableName);
        }

        $set = array_flip($columns[$tableName]);
        // Set the sets from the data.
        foreach ($set as $key => $value) {
            if (isset($postData[$key])) {
                if (isset($postData['_New'][$key])) {
                    $value = $postData['_New'][$key];
                } else {
                    $value = $postData[$key];
                }

                if (is_array($value)) {
                    $value = dbencode($value);
                }
                $set[$key] = $value;
            } else {
                unset($set[$key]);
            }
        }

        Gdn::sql()->options('Replace', true)->insert($tableName, $set);
    }

    /**
     * Checks record cleanliness and send it for review if it is dirty.
     *
     * @param Gdn_Model $sender Sending model instance.
     * @param $recordType Type of post being inspected. (Comment, Discussion...)
     * @param $recordData Content of the post.
     */
    protected function reviewRecordCleanliness($sender, $recordType, $recordData) {

        // If the post is already flagged as invalid let's abort :D
        if (!$sender->EventArguments['IsValid']) {
            return;
        }

        $isRecordClean = $this->isRecordClean($recordType, $recordData);
        if (!$isRecordClean) {
            $sender->EventArguments['IsValid'] = false;

            // Set some information about the user in the data.
            touchValue('InsertUserID', $recordData, Gdn::session()->UserID);

            $user = Gdn::userModel()->getID(val('InsertUserID', $recordData), DATASET_TYPE_ARRAY);
            if ($user) {
                touchValue('Username', $recordData, $user['Name']);
                touchValue('Email', $recordData, $user['Email']);
                touchValue('IPAddress', $recordData, $user['LastIPAddress']);
            }

            // Set custom flag to handle log restoration later on
            $recordData['KeywordBlocker'] = true;

            // Update :D
            if (isset($recordData[$recordType.'ID'])) {
                // Clean up logs in case a user edit the post multiple times
                $logModel = new LogModel();
                $rows = $logModel->getWhere(array(
                    'Operation' => self::MODERATION_QUEUE,
                    'RecordType' => $recordType,
                    'RecordID' => $recordData[$recordType.'ID'],
                    'RecordUserID' => Gdn::session()->UserID,
                ));

                $logIDs = array();
                foreach($rows as $row) {
                    $logIDs[] = $row['LogID'];
                }
                if (!empty($logIDs)) {
                    Gdn::sql()->whereIn('LogID', $logIDs)->delete('Log');
                }

                $oldData = $this->getRecordData($recordType, $recordData[$recordType.'ID']);

                // Preserve fields for restoration.
                $recordData = array_merge($oldData, $recordData);

                // Show diff on post review.
                $recordData['_New'] = array();

                if (in_array($recordType, ['Discussion', 'Group', 'Event'])) {
                    $recordData['_New']['Name'] = $recordData['Name'];
                    $recordData['Name'] = $oldData['Name'];
                }

                if (in_array($recordType, ['Discussion', 'Comment', 'Event'])) {
                    $recordContentField = 'Body';
                } else {
                    $recordContentField = 'Description';
                }
                $recordData['_New'][$recordContentField] = $recordData[$recordContentField];
                $recordData[$recordContentField] = $oldData[$recordContentField];

                if ($recordType === 'Event') {
                    $recordData['_New']['Location'] = $recordData['Location'];
                    $recordData['Location'] = $oldData['Location'];
                }
            }

            LogModel::insert(self::MODERATION_QUEUE, $recordType, $recordData);

            if (in_array($recordType, ['Discussion', 'Comment'])) {
                $sender->EventArguments['InvalidReturnType'] = UNAPPROVED;
            } else {
                $controller = Gdn::controller();
                if ($recordType === 'Event') {
                    $group = $this->getRecordData('Group', $recordData['GroupID']);
                    $controller->setData('GroupUrl', groupUrl($group));
                }
                $controller->render('moderation', '', 'plugins/KeywordBlocker');
                exit();
            }
        }
    }

    /**
     * Check if a record is clean from those nasty blocked words.
     *
     * @param $recordType Type of record being checked. (Comment, Discussion...)
     * @param $recordData Content of the record.
     * @return bool True if clean, false otherwise.
     */
    protected function isRecordClean($recordType, $recordData) {
        $words = $this->getBlockedWords();

        if ($recordType === 'Group') {
            $redordContentField = 'Description';
        } else {
            $redordContentField = 'Body';
        }

        foreach($words as $word) {

            $toTest = $recordData[$redordContentField];
            if (in_array($recordType, ['Discussion', 'Group', 'Event'])) {
                $toTest = $recordData['Name']."\n".$toTest;
            }
            if ($recordType === 'Event') {
                $toTest = $recordData['Location']."\n".$toTest;
            }

            if (preg_match('#\b'.preg_quote($word, '#').'\b#iu', $toTest) === 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the list of blocked words
     *
     * @return array
     */
    protected function getBlockedWords() {
        static $words = null;

        if ($words === null) {
            $words = array();

            $wordsString = c('KeywordBlocker.Words', '');
            if (strlen($wordsString)) {

                $explodedWords = explode(';', $wordsString);
                foreach ($explodedWords as $index => $word) {
                    $word = trim($word);

                    if (strlen($word)) {
                        $explodedWords[$index] = $word;
                    } else {
                        unset($explodedWords[$index]);
                    }
                }

                $words = $explodedWords;
            }
        }

        return $words;
    }

    /**
     * Retrieve the data of the post that will be updated.
     *
     * @param $recordType Type of record (Comment, Discussion...)
     * @param $recordID Record ID
     *
     * @throws Gdn_ErrorException
     *
     * @return array Post data
     */
    protected function getRecordData($recordType, $recordID) {
        switch($recordType) {
            case 'Comment':
                $model = new CommentModel();
                break;
            case 'Discussion':
                $model = new DiscussionModel();
                break;
            case 'Group':
                $model = new GroupModel();
                break;
            case 'Event':
                $model = new EventModel();
                break;
            default:
                throw new Gdn_ErrorException('Unsupported record type.');
        }

        return $model->getID($recordID, DATASET_TYPE_ARRAY);
    }
}
