<?php
/**
 *
 * * Changes:
 *  0.0.1alpha  Initial release
 *  1.0         Add Cleanspeak API Key
 *
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
// Define the plugin:
$PluginInfo['Cleanspeak'] = array(
    'Name' => 'Cleanspeak',
    'Description' => 'Cleanspeak integration for Vanilla.',
    'Version' => '1.1.0',
    'RequiredApplications' => array('Vanilla' => '2.0.18'),
    'SettingsUrl' => '/settings/cleanspeak',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => 'John Ashton',
    'AuthorEmail' => 'john@vanillaforums.com',
    'AuthorUrl' => 'http://www.github.com/John0x00'
);

class CleanspeakPlugin extends Gdn_Plugin {


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
            if (!val('Name', $args['Data'])) {
                $content['content']['Name'] = 'Re: ' . val('Name', $discussion);
            }
        } else {
            //if content has the same 'empty' location its being grouped together.
            $content['content']['location'] = mt_rand();
        }

        $UUID = $cleanSpeak->getRandomUUID($args['Data']);

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

        // Content is in Pre Moderation Queue
        if (GetValue('requiresApproval', $result) == 'requiresApproval'
            || GetValue('contentAction', $result) == 'queuedForApproval') {
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
     * Report some content to cleanspeak.
     *
     * @param array $row The row to report.
     * @param string $uuid The cleanspeak ID of the content.
     */
    public function reportContent($row, $uuid) {
        $cleanSpeak = Cleanspeak::instance();
        $foreignUser = Gdn::UserModel()->GetID($row['ForeignUserID'], DATASET_TYPE_ARRAY);
//        if (!$foreignUser) {
//            throw new Gdn_UserException('Can not find user.');
//        }

        $content = array(
            'content' => array(
                'applicationId' => C('Plugins.Cleanspeak.ApplicationID'),
                'createInstant' => Gdn_Format::ToTimestamp($row['DateInserted']) * 1000,
                'parts' => $cleanSpeak->getParts($row),
                'senderDisplayName' => $foreignUser['Name'],
                'senderId' => $cleanSpeak->getUserUUID($row['InsertUserID'])
            )
        );
        if (valr('Attributes.ForeignDateInserted', $row)) {
            $content['content']['createInstant'] = Gdn_Format::ToTimestamp($row['Attributes']['ForeignDateInserted']) * 1000;
        }
        if (val('DiscussionID', $row)) {
            $discussionID = $row['DiscussionID'];
        } elseif (valr('Attributes.DiscussionID', $row)) {
            $discussionID = $row['Attributes']['DiscussionID'];
        }

        if (!empty($discussionID)) {
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->GetID($row['DiscussionID']);
            $content['content']['location'] = md5(DiscussionUrl($discussion));
        } else {
            //if content has the same 'empty' location its being grouped together.
            $content['content']['location'] = mt_rand();
        }
        $UUID = $uuid;

        // Make an api request to cleanspeak.
        try {
            $result = $cleanSpeak->moderation($UUID, $content, true);
        } catch (CleanspeakException $e) {
        }
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
     * Set Moderator information from post.
     *
     * @param ModController $sender Sending controller.
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
            return $this->getUserID();
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
                $sender->Permission(array('Garden.Moderation.Manage','Garden.Users.Edit','Moderation.Users.Ban'), FALSE);
                $this->BanUser($UUID);
                break;
            case 'unban':
                $sender->Permission(array('Garden.Moderation.Manage','Garden.Users.Edit','Moderation.Users.Ban'), FALSE);
                $this->BanUser($UUID, true);
                break;
            default:
                throw new Gdn_UserException('Unknown UserAction: ' . $action);
        }

    }

    /**
     * Ban/Unban a user.
     *
     * @param string $UUID Unique User ID.
     * @param bool $unBan Set to true to un-ban a user.
     * @return bool user was ban/unbanned.
     * @throws Exception User not found, Attempt to remove system acccount.
     */
    protected function banUser($UUID, $unBan = false) {

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
            $deleteContent = (CheckPermission('Garden.Moderation.Manage')) ? $deleteContent : FALSE;
            $userModel->Ban($userID, array('Reason' => $reason, 'DeleteContent' => $deleteContent));
        }

    }

    /**
     * Warn a user.
     *
     * @param string $UUID Unique user identification
     * @param string $reason
     * @throws Gdn_UserException Error sending message to user.
     */
    protected function warnUser($UUID, $reason = '') {
        $cleanspeak = Cleanspeak::Instance();
        $userID = $cleanspeak->getUserIDFromUUID($UUID);
        $user = Gdn::UserModel()->GetID($userID);
        if (!$user) {
            throw new Gdn_UserException('User not found: '. $UUID);
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
            $userID = Gdn::SQL()->Insert('User', array(
                'Name' => 'Cleanspeak',
                'Password' => RandomString('20'),
                'HashMethod' => 'Random',
                'Email' => 'cleanspeak@domain.com',
                'DateInserted' => Gdn_Format::ToDateTime(),
                'Admin' => '2'
            ));
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
        return C('Plugins.Cleanspeak.UserID', NULL);
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
        $configurationModel->SetField(array(
                'ApiUrl',
                'ApplicationID',
                'AccessToken'
            ));
        // Set the model on the form.
        $sender->Form->SetModel($configurationModel);

        if ($sender->Form->AuthenticatedPostBack() === FALSE) {
            // Apply the config settings to the form.
            $sender->Form->SetData($configurationModel->Data);
        } else {
            $FormValues = $sender->Form->FormValues();
            if ($sender->Form->IsPostBack()) {
                $sender->Form->ValidateRule('ApplicationID', 'function:ValidateRequired', 'Application ID is required');
                $sender->Form->ValidateRule('ApiUrl', 'function:ValidateRequired', 'Api Url is required');

                if ($sender->Form->ErrorCount() == 0) {
                    SaveToConfig(array(
                            'Plugins.Cleanspeak.ApplicationID' => $FormValues['ApplicationID'],
                            'Plugins.Cleanspeak.ApiUrl' => $FormValues['ApiUrl'],
                            'Plugins.Cleanspeak.AccessToken' => val('AccessToken', $FormValues, null)
                        ));
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

    public function getPostBackURL($multiSite = false) {
        if (!Gdn::PluginManager()->CheckPlugin('SimpleAPI')) {
            return false;
        }

        $URL = Url('/mod/cleanspeakpostback.json', true);

        if ($multiSite) {
            $URL = C('Hub.Url', Gdn::Request()->Domain().'/hub') . '/multisites/cleanspeakproxy.json';
        }


        if (strstr($URL, '?')) {
            $URL .= '&';
        } else {
            $URL .= '?';
        }
        $URL .= 'access_token=' . C('Plugins.SimpleAPI.AccessToken');

        return $URL;
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
     * This event is called when the number of reports has been met.
     * Content will be sent to Cleanspeak for moderation.
     * Content will be removed until its been approved.
     * Content that had already been premoderated will not be handled.
     *
     * @param QueueModel $sender
     * @param $args
     */
//    public function queueModel_reportRemoval_handler($sender, &$args) {
//        // send reports to cleanspeak
//        $cleanspeak = Cleanspeak::Instance();
//        $args['ForeignID'] = $cleanspeak->getRandomUUID();
//
//        $foreignUser = Gdn::UserModel()->GetID($args['QueueRow']['ForeignUserID'], DATASET_TYPE_ARRAY);
//        $content = array(
//            'content' => array(
//                'applicationId' => C('Plugins.Cleanspeak.ApplicationID'),
//                'createInstant' => time(),
//                'parts' => $cleanspeak->getParts($args['QueueRow']),
//                'senderDisplayName' => $foreignUser['Name'],
//                'senderId' => $cleanspeak->getUserUUID($args['QueueRow']['ForeignUserID'])
//            )
//        );
//        $cleanspeak->moderation($args['ForeignID'], $content, true);
//
//        $args['ReportHandled'] = true;
//
//    }

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
    * @return bool
    */
   public function logModel_afterInsert_handler($sender, $args) {
      $log = $args['Log'];
      if ($log['Operation'] === 'Moderate') {

         $queue = QueueModel::Instance();
         $record = unserialize($log['Data']);

         // Find content and; Get UID from the queue.
         $ForeignID = $queue->generateForeignID($record);
         $queueRow = $queue->GetWhere(array('ForeignID' => $ForeignID))->FirstRow(DATASET_TYPE_ARRAY);
         $cleanspeakID = $queueRow['CleanspeakID'];
         $cleanspeak = Cleanspeak::instance();

         $flag = array(
            'flag' => array(
               'reporterId' => $cleanspeak->getUserUUID(Gdn::Session()->UserID),
               'createInstant' => Gdn_Format::ToTimestamp($queueRow['DateInserted']) * 1000,
   //            'reason' => 'Flagged'
   //            'comment' => ''
            )
         );

         // Make an api request to cleanspeak.
         // Send to Cleanspeak user alerts.
         $response = $cleanspeak->flag($cleanspeakID, $flag);

         return true;

      }
   }

}
