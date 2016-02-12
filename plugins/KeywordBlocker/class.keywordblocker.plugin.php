<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

$PluginInfo['KeywordBlocker'] = array(
    'Name' => 'Keyword Blocker',
    'Description' => 'Block posts containing certain words and send them for review.',
    'Version' => '1.0',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'HasLocale' => false,
    'License' => 'GNU GPL2',
    'SettingsUrl' => '/settings/keywordBlocker',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => 'Alexandre (DaazKu) Chouinard',
    'AuthorEmail' => 'alexandre.c@vanillaforums.com'
);

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
    public function settingsController_keywordBLocker_create($sender) {

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
     * Hook on BeforeDiscussionSave event.
     *
     * Checks discussion cleanliness and send it for review if it is dirty.
     *
     * @param $sender Sending controller instance.
     * @param $args Event arguments.
     */
    public function base_beforeDiscussionSave_handler($sender, $args) {
        $this->reviewPostCleaniness($sender, 'Discussion', $args['DiscussionData']);
    }

    /**
     * Hook on BeforeDiscussionSave event.
     *
     * @param $sender Sending controller instance.
     * @param $args Event arguments.
     */
    public function base_beforeCommentSave_handler($sender, $args) {
        $this->reviewPostCleaniness($sender, 'Comment', $args['CommentData']);
    }

    /**
     * Hook on BeforeRestore (LogModel) event.
     *
     * @param $sender Sending controller instance.
     * @param $args Event arguments.
     */
    public function base_beforeRestore_handler($sender, $args) {
        if (isset($sender->EventArguments['Log']['Data']['KeywordBlocker'])) {
            if ($sender->EventArguments['Handled']) {
                trace('That particular log should not have been handled by another plugin.', TRACE_WARNING);
                return;
            }

            $sender->EventArguments['Handled'] = true;
            $this->restorePostFromLog($sender->EventArguments['Log']);
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

        $oldData = $this->getOldPostData($tableName, $postData[$tableName.'ID']);

        // Do not update if the post was deleted or if the log was done before the last valid update of the post.
        if (!$oldData || strtotime($oldData['DateUpdated']) > strtotime($log['DateInserted'])) {
            return;
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
                    $value = serialize($value);
                }
                $set[$key] = $value;
            } else {
                unset($set[$key]);
            }
        }

        Gdn::sql()->Options('Replace', true)->insert($tableName, $set);
    }

    /**
     * Checks post cleanliness and send it for review if it is dirty.
     *
     * @param $sender Sending controller instance.
     * @param $postType Type of post being inspected. (Comment, Discussion...)
     * @param $postData Content of the post.
     */
    protected function reviewPostCleaniness($sender, $postType, $postData) {

        // If the post is already flagged as invalid let's abort :D
        if (!$sender->EventArguments['IsValid']) {
            return;
        }

        $isPostClean = $this->isPostClean($postType, $postData);
        if (!$isPostClean) {
            $sender->EventArguments['IsValid'] = false;
            $sender->EventArguments['InvalidReturnType'] = UNAPPROVED;

            // Set some information about the user in the data.
            touchValue('InsertUserID', $postData, Gdn::session()->UserID);

            $user = Gdn::userModel()->getID(val('InsertUserID', $postData), DATASET_TYPE_ARRAY);
            if ($user) {
                touchValue('Username', $postData, $user['Name']);
                touchValue('Email', $postData, $user['Email']);
                touchValue('IPAddress', $postData, $user['LastIPAddress']);
            }

            // Set custom flag to handle log restoration later on
            $postData['KeywordBlocker'] = true;

            // Update :D
            if (isset($postData[$postType.'ID'])) {
                // Clean up logs in case a user edit the post multiple times
                $logModel = new LogModel();
                $rows = $logModel->getWhere(array(
                    'Operation' => self::MODERATION_QUEUE,
                    'RecordType' => $postType,
                    'RecordID' => $postData[$postType.'ID'],
                    'RecordUserID' => Gdn::session()->UserID,
                ));

                $logIDs = array();
                foreach($rows as $row) {
                    $logIDs[] = $row['LogID'];
                }
                if (!empty($logIDs)) {
                    Gdn::sql()->whereIn('LogID', $logIDs)->delete('Log');
                }

                $oldData = $this->getOldPostData($postType, $postData[$postType.'ID']);

                // Preserve fields for restoration.
                $postData = array_merge($oldData, $postData);

                // Show diff on post review.
                $postData['_New'] = array();

                if ($postType === 'Discussion') {
                    $postData['_New']['Name'] = $postData['Name'];
                    $postData['Name'] = $oldData['Name'];
                }

                $postData['_New']['Body'] = $postData['Body'];
                $postData['Body'] = $oldData['Body'];
            }

            LogModel::insert(self::MODERATION_QUEUE, $postType, $postData);
        }
    }

    /**
     * Check if a post is clean from those nasty blocked words.
     *
     * @param $recordType Type of record being checked. (Comment, Discussion...)
     * @param $recordData Content of the record.
     * @return bool True if clean, false otherwise.
     */
    protected function isPostClean($recordType, $recordData) {
        $words = $this->getBlockedWords();

        foreach($words as $word) {

            $toTest = $recordData['Body'];
            if ($recordType === 'Discussion') {
                $toTest = $recordData['Name']."\n".$toTest;
            }

            if (preg_match('#\b'.preg_quote($word, '#').'\b#i', $toTest) === 1) {
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

            $wordsString = c('KeywordBlocker.Words', null);
            if ($wordsString !== null) {

                $explodedWords = explode(';', $wordsString);
                foreach ($explodedWords as &$word) {
                    $word = trim($word);
                }
                unset($word);

                $words = $explodedWords;
            }
        }

        return $words;
    }

    /**
     * Retrieve the data of the post that will be updated.
     *
     * @param $postType Type of record (Comment, Discussion...)
     * @param $postID Record ID
     * @return array Post data
     */
    protected function getOldPostData($postType, $postID) {
        if ($postType === 'Comment') {
            $model = new CommentModel();
        } else {
            $model = new DiscussionModel();
        }

        return $model->getID($postID, DATASET_TYPE_ARRAY);
    }
}
