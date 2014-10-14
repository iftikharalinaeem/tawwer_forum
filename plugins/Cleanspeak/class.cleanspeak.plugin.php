<?php
/**
 *
 *  Changes:
 *  0.0.1alpha  Initial release
 *  1.0         Add Cleanspeak API Key
 *  1.2.0       Change report to use content flagging.
 *  1.2.1       Add reject handling.
 *
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
// Define the plugin:
$PluginInfo['Cleanspeak'] = array(
    'Name' => 'Cleanspeak',
    'Description' => 'Cleanspeak integration for Vanilla.',
    'Version' => '1.2.0',
    'RequiredApplications' => array('Vanilla' => '2.0.18'),
    'SettingsUrl' => '/settings/cleanspeak',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => 'John Ashton',
    'AuthorEmail' => 'john@vanillaforums.com',
    'AuthorUrl' => 'http://www.github.com/John0x00'
);

class CleanspeakPlugin extends Gdn_Plugin {

    // Methods.

    /**
     * Set Moderator information from post.
     *
     * @param ModController $sender Sending controller.
     * @return int Moderator ID.
     * @throws Gdn_UserException Moderator not found.
     */
    protected function setModerator($sender) {
        $post = Gdn::Request()->Post();
        $queueModel = QueueModel::Instance();
        $moderatorUserID = $this->getModeratorUserID(
            array(
                "moderatorId" => $post['moderatorId'],
                "moderatorEmail" => $post['moderatorEmail'],
                "moderatorExternalId" => GetValue('moderatorExternalId', $post)
            )
        );
        if (!$moderatorUserID) {
            // Not able to relate moderator to vanilla user id.
            // User Cleanspeak user id instead.
            $moderatorUserID = $this->getUserID();
        }
        $sender->SetData('ModeratorUserID', $moderatorUserID);
        $queueModel->setModerator($moderatorUserID);

    }

    /**
     * Handle content approval post back notification.
     *
     * @param PluginController $sender
     * @throws Gdn_UserException if unknown action.
     */
    protected function contentApproval($sender) {
        $post = Gdn::Request()->Post();

        // Content Approval
        $queueModel = QueueModel::Instance();
        $this->setModerator($sender);

        foreach ($post['approvals'] as $UUID => $action) {
            switch ($action) {
                case 'approved':
                    $result = $queueModel->approveOrDenyWhere(array('CleanspeakID' => $UUID), 'approve', $sender);
                    break;
                case 'dismissed':
                    $queueModel->approveOrDenyWhere(array('CleanspeakID' => $UUID), 'deny', $sender);
                    break;
                case 'rejected':
                    $queueModel->approveOrDenyWhere(array('CleanspeakID' => $UUID), 'deny', $sender);
                    break;
                default:
                    throw new Gdn_UserException('Unknown action.');
            }
        }

        if (!$result) {
            $sender->SetData('Errors', $queueModel->ValidationResults());
        }
    }

    /**
     * Handle content removal post back notification.
     *
     * @param PluginController $sender
     */
    protected function contentDelete($sender) {
        $post = Gdn::Request()->Post();

        $queueModel = QueueModel::Instance();
        $this->setModerator($sender);
        $id = $post['id'];
        $deleted = $queueModel->approveOrDenyWhere(array('CleanspeakID' => $id), 'deny');
        if ($deleted) {
            $sender->setData('Success', true);
        } else {
            $sender->SetData('Errors', 'Error deleting content.');
        }
    }

    /**
     * Handle User action post back notification.
     *
     * @param PluginController $sender
     * @throws Gdn_UserException On unknown user action
     */
    protected function userAction($sender) {
        $post = Gdn::Request()->Post();

        $this->setModerator($sender);
        $action = $post['action'];
        $UUID = $post['userId'];
        switch (strtolower($action)) {
            case 'warn':
                $this->warnUser($UUID);
                break;
            case 'ban':
                $sender->Permission(
                    array('Garden.Moderation.Manage', 'Garden.Users.Edit', 'Moderation.Users.Ban'),
                    false
                );
                $this->BanUser($UUID);
                break;
            case 'unban':
                $sender->Permission(
                    array('Garden.Moderation.Manage', 'Garden.Users.Edit', 'Moderation.Users.Ban'),
                    false
                );
                $this->BanUser($UUID, true);
                break;
            default:
                throw new Gdn_UserException('Unknown UserAction: ' . $action);
        }

    }

    /**
     *
     *      THIS END POINT IS NOT IN USE.
     *
     * Ban/Unban a user.
     *
     * @param string $UUID Unique User ID.
     * @param bool $unBan Set to true to un-ban a user.
     * @return bool user was ban/unbanned.
     * @throws Exception User not found, Attempt to remove system acccount.
     */
    protected function banUser($UUID, $unBan = false) {

        return;

        $userID = Cleanspeak::getUserIDFromUUID($UUID);
        $restoreContent = true;
        $deleteContent = true;

        //@todo Use cleanspeak reason.
        $reason = 'Cleanspeak: Moderator Banned.';

        $user = Gdn::UserModel()->GetID($userID, DATASET_TYPE_ARRAY);
        if (!$user) {
            throw NotFoundException('User');
        }

        $userModel = Gdn::UserModel();

        // Block banning the superadmin or System accounts
        $user = $userModel->GetID($userID);
        if (GetValue('Admin', $user) == 2) {
            throw ForbiddenException("@You may not ban a System user.");
        } elseif (GetValue('Admin', $user)) {
            throw ForbiddenException("@You may not ban a user with the Admin flag set.");
        }


        if ($unBan) {
            $userModel->Unban($userID, array('RestoreContent' => $restoreContent));
        } else {
            // Just because we're banning doesn't mean we can nuke their content
            $deleteContent = (CheckPermission('Garden.Moderation.Manage')) ? $deleteContent : false;
            $userModel->Ban($userID, array('Reason' => $reason, 'DeleteContent' => $deleteContent));
        }

    }

    /**
     *      THIS END POINT IS NOT IN USE.
     *
     * Warn a user.
     *
     * @param string $UUID Unique user identification
     * @param string $reason
     * @throws Gdn_UserException Error sending message to user.
     */
    protected function warnUser($UUID, $reason = '') {

        return;

        $cleanspeak = Cleanspeak::Instance();
        $userID = $cleanspeak->getUserIDFromUUID($UUID);
        $user = Gdn::UserModel()->GetID($userID);
        if (!$user) {
            throw new Gdn_UserException('User not found: ' . $UUID);
        }

        // Send a message to the person being warned.
        $model = new ConversationModel();
        $messageModel = new ConversationMessageModel();

        switch ($reason) {
            default:
                $body = T('You have been warned.');
        }

        $row = array(
            'Subject' => T('HeadlineFormat.Warning.ToUser', "You've been warned."),
            'Type' => 'warning',
            'Body' => $body,
            'Format' => C('Garden.InputFormatter'),
            'RecipientUserID' => (array)$userID
        );

        $conversationID = $model->Save($row, $messageModel);
        if ($conversationID) {
            throw new Gdn_UserException('Error sending message to user');
        }


    }

    /**
     * Setup the plugin.
     */
    public function setup() {
        $this->structure();
    }

    public function structure() {
        // Get a user for operations.
        $userID = Gdn::SQL()->GetWhere('User', array('Name' => 'Cleanspeak', 'Admin' => 2))->Value('UserID');

        if (!$userID) {
            $userID = Gdn::SQL()->Insert(
                'User',
                array(
                    'Name' => 'Cleanspeak',
                    'Password' => RandomString('20'),
                    'HashMethod' => 'Random',
                    'Email' => 'cleanspeak@domain.com',
                    'DateInserted' => Gdn_Format::ToDateTime(),
                    'Admin' => '2'
                )
            );
        }
        SaveToConfig('Plugins.Cleanspeak.UserID', $userID);

        // Add the cleanspeakID to the queue table.
        if (Gdn::Structure()->TableExists('Queue')) {
            Gdn::Structure()->Table('Queue')
                ->Column('CleanspeakID', 'varchar(50)', true, 'index')
                ->Set();
        }
    }

    /**
     * Get cleanspeak UserID from config.
     * @return int Int or NULL.
     */
    public function getUserID() {
        return C('Plugins.Cleanspeak.UserID', null);
    }

    /**
     * Check to see if plugin is configured.
     * @return bool
     */
    public function isConfigured() {

        return (C('Plugins.Cleanspeak.ApplicationID')
            && C('Plugins.Cleanspeak.UserID')
            && C('Plugins.Cleanspeak.ApiUrl')
        );
    }

    /**
     * Get the moderator user id.
     *
     * @param array $moderator Moderator information from postback
     *  [moderatorID]
     *  [moderatorEmail]
     *  [moderatorExternalId]
     *
     * @return bool
     */
    public function getModeratorUserID($moderator) {
        $userID = false;

        $id = GetValue('moderatorId', $moderator);
        if ($id) {
            $userAuth = Gdn::SQL()->GetWhere(
                'UserAuthentication',
                array('ForeignUserKey' => $moderator['moderatorId'], 'ProviderKey' => 'cleanspeak')
            )->FirstRow(DATASET_TYPE_ARRAY);
            if ($userAuth) {
                return $userAuth['ForeignUserKey'];
            }
        }


        $externalID = GetValue('moderatorExternalId', $moderator);
        if ($id) {
            $user = Gdn::UserModel()->GetID($externalID, DATASET_TYPE_ARRAY);
            if ($user) {
                $userID = $user['UserID'];
            }
        }

        $email = GetValue('moderatorEmail', $moderator);
        if ($email) {
            $user = Gdn::UserModel()->GetWhere(array('Email' => $email))->ResultArray();
            if (sizeof($user) == 1) {
                $userID = $user[0]['UserID'];
            }
        }

        return $userID;
    }


    /**
     * @param bool $multiSite default false.
     * @return bool|string false if SimpleAPU is disabled.
     */
    public function getPostBackURL($multiSite = false) {
        if (!Gdn::PluginManager()->CheckPlugin('SimpleAPI')) {
            return false;
        }

        $URL = Url('/mod/cleanspeakpostback.json', true);

        if ($multiSite) {
            $URL = C('Hub.Url', Gdn::Request()->Domain() . '/hub') . '/multisites/cleanspeakproxy.json';
        }

        if (strstr($URL, '?')) {
            $URL .= '&';
        } else {
            $URL .= '?';
        }
        $URL .= 'access_token=' . C('Plugins.SimpleAPI.AccessToken');

        return $URL;
    }

    // Event Handlers.

    /**
     * Check if content requires premoderation.
     *
     * @param QueueModel $sender
     * @param array $args
     *  [Premoderate] bool True if to be premoderated.
     *  [Queue] array Fields to add to the queue role.
     *  [InsertUserID] int  InsertUserID in the queue.
     * @throws Gdn_UserException
     */
    public function queueModel_checkpremoderation_handler($sender, $args) {
        $cleanSpeak = Cleanspeak::instance();
        $args['Premoderate'] = false;

        if (!$this->isConfigured()) {
            throw new Gdn_UserException('Cleanspeak is not configured.');
            return;
        }

        // Moderators don't go through cleanspeak.
        if (Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
            return;
        }

        // Prepare Data.
        $foreignUser = Gdn::UserModel()->GetID($args['Data']['InsertUserID'], DATASET_TYPE_ARRAY);
        if (!$foreignUser) {
            throw new Gdn_UserException('Can not find user.');
        }
        $content = array(
            'content' => array(
                'applicationId' => C('Plugins.Cleanspeak.ApplicationID'),
                'createInstant' => Gdn_Format::ToTimestamp($args['Data']['DateInserted']) * 1000,
                'parts' => $cleanSpeak->getParts($args['Data']),
                'senderDisplayName' => $foreignUser['Name'],
                'senderId' => $cleanSpeak->getUserUUID($args['Data']['InsertUserID'])
            )
        );
        if (GetValue('DiscussionID', $args['Data'])) {
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->GetID($args['Data']['DiscussionID']);
            $content['content']['location'] = md5(DiscussionUrl($discussion));
        } else {
            //if content has the same 'empty' location its being grouped together.
            $content['content']['location'] = mt_rand();
        }

        $UUID = $cleanSpeak->getRandomUUID($args['Data']);

        // Set the CleanspeakID on the form so we can save it later using model_*Save*_Handlers.
        $Form = Gdn::Controller()->Form;
        $Form->SetFormValue('CleanspeakID', $UUID);

        // Make an api request to cleanspeak.
        try {
            $result = $cleanSpeak->moderation($UUID, $content, C('Plugins.Cleanspeak.ForceModeration'));
        } catch (CleanspeakException $e) {

            // Error communicating with cleanspeak
            // Content will go into premoderation queue
            // InsertUserID will not be updated.
            $args['Queue']['CleanspeakID'] = $UUID;
            $args['Premoderate'] = true;
            return;
        }

        // Content is allowed
        if (GetValue('contentAction', $result) == 'allow') {
            return;
        }

        // Rejected content.
        if (val('contentAction', $result) == 'reject') {

            $queueRow = $sender->convertToQueueRow($args['RecordType'], $args['Data']);
            $queueRow['Status'] = 'denied';
            $queueRow['CleanspeakID'] = $UUID;
            $sender->Save($queueRow);

            // Allow users to edit the post and resubmit.
            if ($args['RecordType'] == 'Activity' || $args['RecordType'] == 'ActivityComment') {

                // Not able to get the errors to attached to the forms for activities.
                // @TODO Make this work like comments and discussions.
                throw new Gdn_UserException('This post has been rejected.  Please try again.');

            } else {
                // Comments / discussions.
                $Form->AddError('This post has been rejected.  Please edit and try again.');
            }

            return;
        }

        // Content is in Pre Moderation Queue
        if (GetValue('requiresApproval', $result) == 'requiresApproval'
            || GetValue('contentAction', $result) == 'queuedForApproval'
        ) {
            $args['Premoderate'] = true;
            $args['Queue']['CleanspeakID'] = $UUID;
            $args['InsertUserID'] = $this->getUserID();
            return;
        }

        //if not handled by above; then add to queue for premoderation.
        $args['Premoderate'] = true;
        return;

    }


    /**
     * Handle Postbacks from Cleanspeak or Hub.
     *
     * Examples:
     *
     * Postback URL:
     *
     * http://localhost/api/v1/mod.json/cleanspeakPostback/?access_token=d7db8b7f0034c13228e4761bf1bfd434
     *
     *    {
     *     "type" : "contentApproval",
     *     "approvals" : {
     *     "8207bc26-f048-478d-8945-84f236cb5637" : "approved",
     *     "86d9e3e1-5752-41dc-aa55-2a832728ec33" : "dismissed",
     *     "a1fca416-5573-4662-a31a-a4ff808c34dd" : "rejected",
     *     "af777ea8-1874-463c-a97c-a1f9e494bee1" : "approved",
     *     "73031050-2016-44fc-b8f6-b97184793587" : "approved"
     *     },
     *     "moderatorId": "b00916ba-f647-4e9f-b2a6-537f69f89b87",
     *     "moderatorEmail" : "catherine@email.com",
     *     "moderatorExternalId": "foo-bar-baz"
     *    }
     *
     *    {
     *     "type" : "contentDelete",
     *     "applicationId" : "63d797d4-0603-48f7-8fef-5008edc670dd",
     *     "id" : "3f8f66cb-d933-4e5e-a76d-5b3a4d9209cd",
     *     "moderatorId": "b00916ba-f647-4e9f-b2a6-537f69f89b87",
     *     "moderatorEmail" : "catherine@email.com",
     *     "moderatorExternalId": "foo-bar-baz"
     *     }
     *
     *    {
     *     "type" : "userAction",
     *     "action" : "Warn",
     *     "applicationIds" : [ "2c84ed53-6b75-4bef-ab68-eddb9ee253b4" ],
     *     "comment" : "a comment",
     *     "key" : "Language",
     *     "userId" : "f9caf789-b316-4233-bd62-19f8fb649275",
     *     "moderatorId": "b00916ba-f647-4e9f-b2a6-537f69f89b87",
     *     "moderatorEmail" : "catherine@email.com",
     *     "moderatorExternalId": "foo-bar-baz"
     *     }
     *
     * @param PluginController $sender
     * @throws Gdn_UserException
     */
    public function modController_cleanspeakPostback_create($sender) {

        // Minimum Permissions needed
        $sender->Permission('Garden.Moderation.Manage');

        if (Gdn::Request()->RequestMethod() != 'POST') {
            Logger::event(
                'postback_error',
                Logger::ERROR,
                'Invalid request method: {method}',
                array('method' => Gdn::Request()->RequestMethod())
            );
            throw new Gdn_UserException('Invalid Request Type');
        }

        $post = Gdn::Request()->Post();
        if (!$post) {
            Logger::event('postback_error', Logger::ERROR, 'Error in POST', $post);
            throw new Gdn_UserException('Error in POST');
        }

        $type = $post['type'];
        switch ($type) {
            case 'contentApproval':
                $this->contentApproval($sender);
                break;
            case 'contentDelete':
                $this->contentDelete($sender);
                break;
            case 'userAction':
                $this->userAction($sender);
                break;
            default:
                $context['Post'] = $post;
                Logger::event('cleanspeak_error', Logger::ERROR, 'Unknown Type.', $context);
        }

        $sender->Render('blank', 'utility', 'dashboard');

    }

    /**
     * Plugin settings page.
     *
     * @param SettingsController $sender Sending Controller,
     * @param array $args Sending Arguments
     */
    public function settingsController_cleanspeak_create($sender, $args) {
        $sender->Permission('Garden.Settings.Manage');
        $sender->Title('Cleanspeak');
        $sender->AddSideMenu('plugin/Cleanspeak');
        $sender->Form = new Gdn_Form();

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->SetField(
            array(
                'ApiUrl',
                'ApplicationID',
                'AccessToken'
            )
        );
        // Set the model on the form.
        $sender->Form->SetModel($configurationModel);

        if ($sender->Form->AuthenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $sender->Form->SetData($configurationModel->Data);
        } else {
            $FormValues = $sender->Form->FormValues();
            if ($sender->Form->IsPostBack()) {
                $sender->Form->ValidateRule('ApplicationID', 'function:ValidateRequired', 'Application ID is required');
                $sender->Form->ValidateRule('ApiUrl', 'function:ValidateRequired', 'Api Url is required');

                if ($sender->Form->ErrorCount() == 0) {
                    SaveToConfig(
                        array(
                            'Plugins.Cleanspeak.ApplicationID' => $FormValues['ApplicationID'],
                            'Plugins.Cleanspeak.ApiUrl' => $FormValues['ApiUrl'],
                            'Plugins.Cleanspeak.AccessToken' => val('AccessToken', $FormValues, null)
                        )
                    );
                    $sender->InformMessage(T('Settings updated.'));
                } else {
                    $sender->InformMessage(T("Error saving settings to config."));
                }


            }
        }

        $sender->Form->SetValue('ApplicationID', C('Plugins.Cleanspeak.ApplicationID'));
        $sender->Form->SetValue('ApiUrl', C('Plugins.Cleanspeak.ApiUrl'));
        $sender->Form->SetValue('AccessToken', C('Plugins.Cleanspeak.AccessToken'));

        $sender->SetData('Enabled', C('Plugins.Cleanspeak.Enabled'));
        $sender->SetData('IsConfigured', $this->isConfigured());

        $sender->SetData('PostBackURL', $this->getPostBackURL());
        if (Gdn::PluginManager()->CheckPlugin('sitenode')) {

            $sender->SetData('PostBackURL', $this->getPostBackURL(true));
        }
        $sender->Render($this->GetView('settings.php'));


    }


    /**
     * @param SettingsController $sender Sending controller.
     * @param array $args Sending arguments.
     */
    public function settingsController_cleanspeakToggle_create($sender, $args) {

        if (C('Plugins.Cleanspeak.Enabled')) {
            SaveToConfig('Plugins.Cleanspeak.Enabled', false);
            $buttonText = T('Enable');
        } else {
            SaveToConfig('Plugins.Cleanspeak.Enabled', true);
            $buttonText = T('Disable');
        }
        $sender->InformMessage(T('Changes Saved'));
        $sender->JsonTarget("#cstoggle", $buttonText);
        $sender->Render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * @param MultisitesController $sender
     */
    public function multisitesController_nodeConfig_render($sender) {
        TouchValue('Plugins.Cleanspeak.ApplicationID', $sender->Data['Config'], C('Plugins.Cleanspeak.ApplicationID'));
        TouchValue('Plugins.Cleanspeak.ApiUrl', $sender->Data['Config'], C('Plugins.Cleanspeak.ApiUrl'));
    }

    /**
     * Flag content in Cleanspeak.
     *
     * @param $sender
     * @param $args
     */
    public function logModel_afterInsert_handler($sender, $args) {
        $log = $args['Log'];
        if ($log['Operation'] === 'Moderate') {

            $record = unserialize($log['Data']);
            $cleanspeakID = valr('Attributes.CleanspeakID', $record);
            if (!$cleanspeakID) {
                $cleanspeakID = valr('Data.CleanspeakID', $record);
            }
            if (!$cleanspeakID) {
                return;
            }
            $cleanspeak = Cleanspeak::instance();

            $flag = array(
                'flag' => array(
                    'reporterId' => $cleanspeak->getUserUUID(Gdn::Session()->UserID),
                    'createInstant' => Gdn_Format::ToTimestamp() * 1000,
                )
            );

            // Send to Cleanspeak user alerts.
            try {
                $cleanspeak->flag($cleanspeakID, $flag);
            } catch (Exception $e) {
                // Error communicating with Cleanspeak.
                // Content will not be flagged. Error can be seen in EventLog.
            }

        }
    }

    /**
     * Save the CleanspeakID to the record attributes.  We will need this for reporting.
     *
     * @param $sender
     * @param $args
     */
    public function CommentModel_AfterSaveComment_Handler($sender, $args) {
        /**
         * @var $Form Gdn_Form
         */
        $form = Gdn::Controller()->Form;
        $cleanspeakID = $form->GetValue('CleanspeakID');
        if (!$cleanspeakID) {
            return;
        }
        $commentModel = new CommentModel();
        $comment = $commentModel->GetID($args['CommentID'], DATASET_TYPE_ARRAY);
        if (val('Attributes', $comment)) {
            $attributes = unserialize($comment['Attributes']);
        }
        $attributes['CleanspeakID'] = $cleanspeakID;
        $commentModel->SetField($args['CommentID'], 'Attributes', serialize($attributes));

    }

    /**
     * Save the CleanspeakID to the record attributes.  We will need this for reporting.
     *
     * @param DiscussionModel $sender
     * @param array $args
     */
    public function DiscussionModel_AfterSaveDiscussion_Handler($sender, $args) {
        /**
         * @var $form Gdn_Form
         */
        $form = Gdn::Controller()->Form;
        $cleanspeakID = $form->GetValue('CleanspeakID');
        if (!$cleanspeakID) {
            return;
        }
        $discussionModel = new DiscussionModel();
        $discussion = $discussionModel->GetID($args['DiscussionID']);
        if (val('Attributes', $discussion)) {
            $attributes = unserialize($discussion['Attributes']);
        }
        $attributes['CleanspeakID'] = $cleanspeakID;
        $discussionModel->SetField($args['DiscussionID'], 'Attributes', serialize($attributes));

    }

    /**
     * Save the CleanspeakID to the record Data.  We will need this for reporting.
     *
     * @param ActivityModel $sender
     * @param $args
     */
    public function ActivityModel_AfterSave_Handler($sender, $args) {

        $form = Gdn::Controller()->Form;
        $cleanspeakID = $form->GetValue('CleanspeakID');
        if (!$cleanspeakID) {
            return;
        }
        $activityModel = new ActivityModel();
        $activity = $activityModel->GetID($args['Activity']['ActivityID']);
        if (val('Data', $activity)) {
            $data = unserialize($activity['Activity']['Data']);
        }
        $data['CleanspeakID'] = $cleanspeakID;
        $activityModel->SetField($args['Activity']['ActivityID'], 'Data', serialize($data));
    }

    /**
     * Add CleanspeakID to the queue if present on record attributes.
     *
     * @param queueModel $sender Sending controller.
     * @param array $args sending arguments.
     */
    public function queueModel_AfterConvertToQueueRow_Handler($sender, $args) {
        if (valr('Data.Attributes.CleanspeakID', $args)) {
            $args['QueueRow']['CleanspeakID'] = $args['Data']['Attributes']['CleanspeakID'];
        } elseif (valr('Data.Data.CleanspeakID', $args)) {
            $args['QueueRow']['CleanspeakID'] = $args['Data']['Data']['CleanspeakID'];
        }

    }

}
