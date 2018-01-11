<?php
/**
 *
 *  Changes:
 *  0.0.1alpha  Initial release
 *  1.0         Add Cleanspeak API Key
 *  1.2.0       Change report to use content flagging.
 *  1.2.1       Add reject handling.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
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
        $post = Gdn::request()->post();
        $queueModel = QueueModel::instance();
        $moderatorUserID = $this->getModeratorUserID(
            [
                "moderatorId" => $post['moderatorId'],
                "moderatorEmail" => $post['moderatorEmail'],
                "moderatorExternalId" => getValue('moderatorExternalId', $post)
            ]
        );
        if (!$moderatorUserID) {
            // Not able to relate moderator to vanilla user id.
            // User Cleanspeak user id instead.
            $moderatorUserID = $this->getUserID();
        }
        $sender->setData('ModeratorUserID', $moderatorUserID);
        $queueModel->setModerator($moderatorUserID);

    }

    /**
     * Handle content approval post back notification.
     *
     * @param PluginController $sender
     * @throws Gdn_UserException if unknown action.
     */
    protected function contentApproval($sender) {
        $post = Gdn::request()->post();

        // Content Approval
        $queueModel = QueueModel::instance();
        $this->setModerator($sender);

        foreach ($post['approvals'] as $uUID => $action) {
            switch ($action) {
                case 'approved':
                    $result = $queueModel->approveOrDenyWhere(['CleanspeakID' => $uUID], 'approve', $sender);
                    break;
                case 'dismissed':
                    $queueModel->approveOrDenyWhere(['CleanspeakID' => $uUID], 'deny', $sender);
                    break;
                case 'rejected':
                    $queueModel->approveOrDenyWhere(['CleanspeakID' => $uUID], 'deny', $sender);
                    break;
                default:
                    throw new Gdn_UserException('Unknown action.');
            }
        }

        if (!$result) {
            $sender->setData('Errors', $queueModel->validationResults());
        }
    }

    /**
     * Handle content removal post back notification.
     *
     * @param PluginController $sender
     */
    protected function contentDelete($sender) {
        $post = Gdn::request()->post();

        $queueModel = QueueModel::instance();
        $this->setModerator($sender);
        $id = $post['id'];
        $deleted = $queueModel->approveOrDenyWhere(['CleanspeakID' => $id], 'deny', $sender);
        if ($deleted) {
            $sender->setData('Success', true);
        } else {
            $sender->setData('Errors', 'Error deleting content.');
        }
    }

    /**
     * Handle User action post back notification.
     *
     * @param PluginController $sender
     * @throws Gdn_UserException On unknown user action
     */
    protected function userAction($sender) {
        $post = Gdn::request()->post();

        $this->setModerator($sender);
        $action = $post['action'];
        $uUID = $post['userId'];
        switch (strtolower($action)) {
            case 'warn':
                $this->warnUser($uUID);
                break;
            case 'ban':
                $sender->permission(
                    ['Garden.Moderation.Manage', 'Garden.Users.Edit', 'Moderation.Users.Ban'],
                    false
                );
                $this->banUser($uUID);
                break;
            case 'unban':
                $sender->permission(
                    ['Garden.Moderation.Manage', 'Garden.Users.Edit', 'Moderation.Users.Ban'],
                    false
                );
                $this->banUser($uUID, true);
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
     * @param string $uUID Unique User ID.
     * @param bool $unBan Set to true to un-ban a user.
     * @return bool user was ban/unbanned.
     * @throws Exception User not found, Attempt to remove system acccount.
     */
    protected function banUser($uUID, $unBan = false) {

        return;

        $userID = Cleanspeak::getUserIDFromUUID($uUID);
        $restoreContent = true;
        $deleteContent = true;

        //@todo Use cleanspeak reason.
        $reason = 'Cleanspeak: Moderator Banned.';

        $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
        if (!$user) {
            throw notFoundException('User');
        }

        $userModel = Gdn::userModel();

        // Block banning the superadmin or System accounts
        $user = $userModel->getID($userID);
        if (getValue('Admin', $user) == 2) {
            throw forbiddenException("@You may not ban a System user.");
        } elseif (getValue('Admin', $user)) {
            throw forbiddenException("@You may not ban a user with the Admin flag set.");
        }


        if ($unBan) {
            $userModel->unban($userID, ['RestoreContent' => $restoreContent]);
        } else {
            // Just because we're banning doesn't mean we can nuke their content
            $deleteContent = (checkPermission('Garden.Moderation.Manage')) ? $deleteContent : false;
            $userModel->ban($userID, ['Reason' => $reason, 'DeleteContent' => $deleteContent]);
        }

    }

    /**
     *      THIS END POINT IS NOT IN USE.
     *
     * Warn a user.
     *
     * @param string $uUID Unique user identification
     * @param string $reason
     * @throws Gdn_UserException Error sending message to user.
     */
    protected function warnUser($uUID, $reason = '') {

        return;

        $cleanspeak = Cleanspeak::instance();
        $userID = $cleanspeak->getUserIDFromUUID($uUID);
        $user = Gdn::userModel()->getID($userID);
        if (!$user) {
            throw new Gdn_UserException('User not found: ' . $uUID);
        }

        // Send a message to the person being warned.
        $model = new ConversationModel();
        $messageModel = new ConversationMessageModel();

        switch ($reason) {
            default:
                $body = t('You have been warned.');
        }

        $row = [
            'Subject' => t('HeadlineFormat.Warning.ToUser', "You've been warned."),
            'Type' => 'warning',
            'Body' => $body,
            'Format' => c('Garden.InputFormatter'),
            'RecipientUserID' => (array)$userID
        ];

        $conversationID = $model->save($row, $messageModel);
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
        $userID = Gdn::sql()->getWhere('User', ['Name' => 'Cleanspeak', 'Admin' => 2])->value('UserID');

        if (!$userID) {
            $userID = Gdn::sql()->insert(
                'User',
                [
                    'Name' => 'Cleanspeak',
                    'Password' => randomString('20'),
                    'HashMethod' => 'Random',
                    'Email' => 'cleanspeak@domain.com',
                    'DateInserted' => Gdn_Format::toDateTime(),
                    'Admin' => '2'
                ]
            );
        }
        saveToConfig('Plugins.Cleanspeak.UserID', $userID);

        // Add the cleanspeakID to the queue table.
        if (Gdn::structure()->tableExists('Queue')) {
            Gdn::structure()->table('Queue')
                ->column('CleanspeakID', 'varchar(50)', true, 'index')
                ->set();
        }
    }

    /**
     * Get cleanspeak UserID from config.
     * @return int Int or NULL.
     */
    public function getUserID() {
        return c('Plugins.Cleanspeak.UserID', null);
    }

    /**
     * Check to see if plugin is configured.
     * @return bool
     */
    public function isConfigured() {

        return (c('Plugins.Cleanspeak.ApplicationID')
            && c('Plugins.Cleanspeak.UserID')
            && c('Plugins.Cleanspeak.ApiUrl')
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

        $id = getValue('moderatorId', $moderator);
        if ($id) {
            $userAuth = Gdn::sql()->getWhere(
                'UserAuthentication',
                ['ForeignUserKey' => $moderator['moderatorId'], 'ProviderKey' => 'cleanspeak']
            )->firstRow(DATASET_TYPE_ARRAY);
            if ($userAuth) {
                return $userAuth['ForeignUserKey'];
            }
        }


        $externalID = getValue('moderatorExternalId', $moderator);
        if ($id) {
            $user = Gdn::userModel()->getID($externalID, DATASET_TYPE_ARRAY);
            if ($user) {
                $userID = $user['UserID'];
            }
        }

        $email = getValue('moderatorEmail', $moderator);
        if ($email) {
            $user = Gdn::userModel()->getWhere(['Email' => $email])->resultArray();
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
        if (!Gdn::addonManager()->isEnabled('SimpleAPI', \Vanilla\Addon::TYPE_ADDON)) {
            return false;
        }

        $uRL = url('/mod/cleanspeakpostback.json', true);

        if ($multiSite) {
            $uRL = c('Hub.Url', Gdn::request()->domain() . '/hub') . '/multisites/cleanspeakproxy.json';
        }

        if (strstr($uRL, '?')) {
            $uRL .= '&';
        } else {
            $uRL .= '?';
        }
        $uRL .= 'access_token=' . c('Plugins.SimpleAPI.AccessToken');

        return $uRL;
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
        Logger::event('cleanspeak_checkpremoderation', Logger::DEBUG, 'Cleanspeak queueModel_checkpremoderation.');
        $mediaIDs = valr('Options.MediaIDs', $args);

        // Moderators don't go through cleanspeak.
        if (Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
            $args['Premoderate'] = false;
            return;
        }

        // Call to cleanspeak is done in the BeforeSave methods and results is stored in controller data.
        $result = Gdn::controller()->data('Result');
        if ($result === false) {
            // Error communicating with cleanspeak
            // Content will go into premoderation queue
            // InsertUserID will not be updated.
            $args['Premoderate'] = true;
            Logger::event('cleanspeak_checkpremoderation', Logger::DEBUG, 'Cleanspeak premoderate.');
            return;
        }


        // Content is allowed
        if (val('contentAction', $result) == 'allow') {
            $args['Premoderate'] = false;
            return;
        }

        // Rejected content.
        if (val('contentAction', $result) == 'reject') {

            $args['Options']['Rejected'] = true;
            $args['Premoderate'] = false;

            $queueRow = $sender->convertToQueueRow($args['RecordType'], $args['Data']);
            $queueRow['Status'] = 'denied';
            $queueRow['CleanspeakID'] = $result['content']['id'];
            $sender->save($queueRow);

            return;
        }

        // Content is in Pre Moderation Queue
        if (val('requiresApproval', $result) == 'requiresApproval'
            || val('contentAction', $result) == 'queuedForApproval'
        ) {
            $args['Premoderate'] = true;
            $args['Queue']['CleanspeakID'] = $result['content']['id'];
            if ($mediaIDs) {
                $args['Queue']['MediaIDs'] = $mediaIDs;
            }

            $args['InsertUserID'] = $this->getUserID();
            Logger::event('cleanspeak_checkpremoderation', Logger::DEBUG, 'Cleanspeak premoderate.');
            return;
        }

        //if not handled by above; then add to queue for premoderation.
        $args['Premoderate'] = true;
        Logger::event('cleanspeak_checkpremoderation', Logger::DEBUG, 'Cleanspeak premoderate.');
        return;

    }

    public function discussionModel_beforeSaveDiscussion_handler($sender, $args) {
        $this->beforeSave($sender, $args);
    }

    public function commentModel_beforeSaveComment_handler($sender, $args) {
        $this->beforeSave($sender, $args);
    }

    public function activityModel_beforeSave_handler($sender, $args) {
        $args['FormPostValues'] = $args['Activity'];
        $this->beforeSave($sender, $args);
    }

    public function activityModel_beforeSaveComment_handler($sender, $args) {
        $args['FormPostValues'] = $args['Comment'];
        $this->beforeSave($sender, $args);

    }

    /**
     * Call to cleanspeak is made before save and data stored into controller data.  This allows for us to add
     * an error to the form; Which is used for content rejection.
     *
     * @param ActivityModel|CommentModel|DiscussionModel $sender Sending contgroller.
     * @param $args Sending arguments.
     * @throws Gdn_UserException
     */
    protected function beforeSave($sender, $args) {

        // When approve() is called; if content needs to go online; Save is called
        // This then triggers this event.  If we are coming from mod controller..
        // we dont want to call cleanspeak again; and need to pass along cleanspeak id.
        if (valr('FormPostValues.Approved', $args)) {
            return;
        }

        // Allow for content edit.
        if (val('DiscussionID', $args, 0) > 0) {
            return;
        }

        $cleanSpeak = Cleanspeak::instance();

        $body = valr('FormPostValues.Body', $args);
        $story = valr('FormPostValues.Story', $args);
        // No need to check if no content.
        if (empty($body) && empty($story)) {
            return;
        }

        if (!$this->isConfigured()) {
            throw new Gdn_UserException('Cleanspeak is not configured.');
            return;
        }

        // Moderators don't go through cleanspeak.
        if (Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
            return;
        }

        // Prepare Data.
        $foreignUser = Gdn::userModel()->getID($args['FormPostValues']['InsertUserID'], DATASET_TYPE_ARRAY);
        if (!$foreignUser) {
            throw new Gdn_UserException('Can not find user.');
        }
        $content = [
            'content' => [
                'applicationId' => c('Plugins.Cleanspeak.ApplicationID'),
                'createInstant' => Gdn_Format::toTimestamp($args['FormPostValues']['DateInserted']) * 1000,
                'parts' => $cleanSpeak->getParts($args['FormPostValues']),
                'senderDisplayName' => $foreignUser['Name'],
                'senderId' => $cleanSpeak->getUserUUID($args['FormPostValues']['InsertUserID'])
            ]
        ];
        if (getValue('DiscussionID', $args['FormPostValues'])) {
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID($args['FormPostValues']['DiscussionID']);
            $content['content']['location'] = md5(discussionUrl($discussion));
        } else {
            //if content has the same 'empty' location its being grouped together.
            $content['content']['location'] = mt_rand();
        }

        $uUID = $cleanSpeak->getRandomUUID($args['FormPostValues']);

        // Set the CleanspeakID on the form so we can save it later using model_*Save*_Handlers.
        $form = Gdn::controller()->Form;
        $form->setFormValue('CleanspeakID', $uUID);

        // Make an api request to cleanspeak.
        try {
            $result = $cleanSpeak->moderation($uUID, $content, c('Plugins.Cleanspeak.ForceModeration'));

            if (!is_array($result)) {
                Logger::warning("Cleanspeak API did not return an array.");
            } elseif (val('contentAction', $result) == 'reject') {

                /** @var $Validation Gdn_Validation */
                $validation = $sender->Validation;
                $validation->addValidationResult('Body', 'Your message has been prevented from submission because of inappropriate content. '
                    . 'Please modify your message to be appropriate before attempting to submit it again.');


                if (valr('FormPostValues.Post_Discussion', $args)) {
                    $contentType = 'Discussion';
                } elseif (valr('FormPostValues.DiscussionID', $args)) {
                    $contentType = 'Comment';
                } elseif (val('Activity', $args) && val('ActivityID', $args) == null) {
                    $contentType = 'Activity';
                } elseif (valr('FormPostValues.ActivityID', $args)) {
                    $contentType = 'ActivityComment';
                }

                $queueModel = QueueModel::instance();
                $queueRow = $queueModel->convertToQueueRow($contentType, $args['FormPostValues']);
                $queueRow['Status'] = 'denied';
                $queueRow['CleanspeakID'] = $result['content']['id'];
                $queueModel->save($queueRow);

            }

            Gdn::controller()->setData('Result', $result);

        } catch (CleanspeakException $e) {

            Gdn::controller()->setData('Result', false);

        }
    }

    /**
     * Add existing CleanspeakID to the SaveData so it will be saved in attributes.
     *
     * @param QueueModel $sender
     * @param array $args Sending arguments.
     */
    public function queueModel_beforeApproveSave_handler($sender, $args) {
        $activityType = valr('QueueItem.ForeignType', $args);
        if ($activityType == 'ActivityComment') {
            // There was no attributes or data fof activity comments at time of writing.
            // Activity comments can not be flagged; Instead would need fo flag the parent
            // activity.
            return;
        }
        if ($activityType == 'Activity') {
            $args['SaveData']['Data']['CleanspeakID'] = $args['QueueItem']['CleanspeakID'];
            return;
        }
        $args['SaveData']['CleanspeakID'] = $args['QueueItem']['CleanspeakID'];
    }

    /**
     * Update media ids if present on queue row.
     *
     * @param queueModel $sender QueueModel.
     * @param $args Sending arguments.
     */
    public function queueModel_afterApproveSave_handler($sender, $args) {
        $mediaIDs = valr('QueueItem.MediaIDs', $args, []);
        $foreignTable = false;

        if (valr('QueueItem.ForeignType', $args, false) == 'Discussion') {
            $foreignTable = 'discussion';
        }
        if (valr('QueueItem.ForeignType', $args, false) == 'Comment') {
            $foreignTable = 'comment';
        }
        if ($foreignTable && is_array($mediaIDs)) {
            $mediaModel = new Gdn_Model('Media');
            $mediaModel->update(
                [
                    'ForeignTable' => $foreignTable,
                    'ForeignID' => $args['ID']
                ],
                ['MediaID' => $mediaIDs]
            );
        }

    }

    /**
     * Handle Postbacks from Cleanspeak or Hub.
     *
     * Examples:
     *
     * Postback URL:
     *
     * http://localhost/api/v1/mod/cleanspeakPostback.json?access_token=d7db8b7f0034c13228e4761bf1bfd434
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
        $sender->permission('Garden.Moderation.Manage');

        if (Gdn::request()->requestMethod() != 'POST') {
            Logger::event(
                'postback_error',
                Logger::ERROR,
                'Invalid request method: {method}',
                ['method' => Gdn::request()->requestMethod()]
            );
            throw new Gdn_UserException('Invalid Request Type');
        }

        $post = Gdn::request()->post();
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

        $sender->render('blank', 'utility', 'dashboard');

    }

    /**
     * Plugin settings page.
     *
     * @param SettingsController $sender Sending Controller,
     * @param array $args Sending Arguments
     */
    public function settingsController_cleanspeak_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');
        $sender->title('Cleanspeak');
        $sender->addSideMenu('plugin/Cleanspeak');
        $sender->Form = new Gdn_Form();

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField(
            [
                'ApiUrl',
                'ApplicationID',
                'AccessToken'
            ]
        );
        // Set the model on the form.
        $sender->Form->setModel($configurationModel);

        if ($sender->Form->authenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $sender->Form->setData($configurationModel->Data);
        } else {
            $formValues = $sender->Form->formValues();
            if ($sender->Form->isPostBack()) {
                $sender->Form->validateRule('ApplicationID', 'function:ValidateRequired', 'Application ID is required');
                $sender->Form->validateRule('ApiUrl', 'function:ValidateRequired', 'Api Url is required');

                if ($sender->Form->errorCount() == 0) {
                    saveToConfig(
                        [
                            'Plugins.Cleanspeak.ApplicationID' => $formValues['ApplicationID'],
                            'Plugins.Cleanspeak.ApiUrl' => $formValues['ApiUrl'],
                            'Plugins.Cleanspeak.AccessToken' => val('AccessToken', $formValues, null)
                        ]
                    );
                    $sender->informMessage(t('Settings updated.'));
                } else {
                    $sender->informMessage(t("Error saving settings to config."));
                }


            }
        }

        $sender->Form->setValue('ApplicationID', c('Plugins.Cleanspeak.ApplicationID'));
        $sender->Form->setValue('ApiUrl', c('Plugins.Cleanspeak.ApiUrl'));
        $sender->Form->setValue('AccessToken', c('Plugins.Cleanspeak.AccessToken'));

        $sender->setData('Enabled', c('Plugins.Cleanspeak.Enabled'));
        $sender->setData('IsConfigured', $this->isConfigured());

        $sender->setData('PostBackURL', $this->getPostBackURL());
        if (Gdn::addonManager()->isEnabled('sitenode', \Vanilla\Addon::TYPE_ADDON)) {

            $sender->setData('PostBackURL', $this->getPostBackURL(true));
        }
        $sender->render($this->getView('settings.php'));


    }


    /**
     *
     * @param SettingsController $sender Sending controller.
     * @param array $args Sending arguments.
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function settingsController_cleanspeakToggle_create($sender, $args) {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        if (Gdn::session()->checkPermission('Garden.Community.Manage')) {
            if (c('Plugins.Cleanspeak.Enabled')) {
                saveToConfig('Plugins.Cleanspeak.Enabled', false);
                $newToggle = wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/settings/cleanspeaktoggle', 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-off"]);
            } else {
                saveToConfig('Plugins.Cleanspeak.Enabled', true);
                $newToggle = wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/settings/cleanspeaktoggle', 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-on"]);
            }
            $sender->informMessage(t('Changes Saved'));
            $sender->jsonTarget("#cstoggle", $newToggle);
        }
        $sender->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * @param MultisitesController $sender
     */
    public function multisitesController_nodeConfig_render($sender) {
        touchValue('Plugins.Cleanspeak.ApplicationID', $sender->Data['Config'], c('Plugins.Cleanspeak.ApplicationID'));
        touchValue('Plugins.Cleanspeak.ApiUrl', $sender->Data['Config'], c('Plugins.Cleanspeak.ApiUrl'));
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

            $record = dbdecode($log['Data']);
            $cleanspeakID = valr('Attributes.CleanspeakID', $record);
            if (!$cleanspeakID) {
                $cleanspeakID = valr('Data.CleanspeakID', $record);
            }
            if (!$cleanspeakID) {
                return;
            }
            $cleanspeak = Cleanspeak::instance();

            $flag = [
                'flag' => [
                    'reporterId' => $cleanspeak->getUserUUID(Gdn::session()->UserID),
                    'createInstant' => Gdn_Format::toTimestamp() * 1000,
                ]
            ];

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
     * Send reports to cleanspeak if flagged by a another user.
     *
     * @param logModel $sender
     * @param array $args Sending arguments.
     */
    public function logModel_afterUpdate_handler($sender, $args) {
        $log = $args['LogRow2'];
        if ($log['Operation'] === 'Moderate') {

            // Check to see if count is inc.  If so its a new user adding a report
            if ($args['Update']['CountGroup'] > $log['CountGroup']) {

                $record = getRecord($log['RecordType'], $log['RecordID']);
                $cleanspeakID = valr('Attributes.CleanspeakID', $record);
                $cleanspeak = Cleanspeak::instance();
                $flag = [
                    'flag' => [
                        'reporterId' => $cleanspeak->getUserUUID(Gdn::session()->UserID),
                        'createInstant' => time() * 1000,
                    ]
                ];

                // Send to Cleanspeak user alerts.
                try {
                    $cleanspeak->flag($cleanspeakID, $flag);
                } catch (Exception $e) {
                    // Error communicating with Cleanspeak.
                    // Content will not be flagged. Error can be seen in EventLog.
                }

            }
        }
    }

    /**
     * Save the CleanspeakID to the record attributes.  We will need this for reporting.
     *
     * @param $sender
     * @param $args
     */
    public function commentModel_afterSaveComment_handler($sender, $args) {
        /**
         * @var $Form Gdn_Form
         */
        $form = val('Form', Gdn::controller(), false);;
        if ($form) {
            $cleanspeakID = $form->getValue('CleanspeakID');
        } else {
            $cleanspeakID = valr('FormPostValues.CleanspeakID', $args);
        }
        if (!$cleanspeakID) {
            return;
        }
        $commentModel = new CommentModel();
        $comment = $commentModel->getID($args['CommentID'], DATASET_TYPE_ARRAY);
        if (val('Attributes', $comment)) {
            $attributes = dbdecode($comment['Attributes']);
        }
        $attributes['CleanspeakID'] = $cleanspeakID;
        $commentModel->setField($args['CommentID'], 'Attributes', dbencode($attributes));

    }

    /**
     * Save the CleanspeakID to the record attributes.  We will need this for reporting.
     *
     * @param DiscussionModel $sender
     * @param array $args
     */
    public function discussionModel_afterSaveDiscussion_handler($sender, $args) {
        /**
         * @var $form Gdn_Form
         */
        $form = val('Form', Gdn::controller(), false);;
        if ($form) {
            $cleanspeakID = $form->getValue('CleanspeakID');
        } else {
            $cleanspeakID = valr('FormPostValues.CleanspeakID', $args);
        }
        if (!$cleanspeakID) {
            return;
        }
        $discussionModel = new DiscussionModel();
        $discussion = $discussionModel->getID($args['DiscussionID']);
        if (val('Attributes', $discussion)) {
            $attributes = dbdecode($discussion['Attributes']);
        }
        $attributes['CleanspeakID'] = $cleanspeakID;
        $discussionModel->setField($args['DiscussionID'], 'Attributes', dbencode($attributes));

    }

    /**
     * Save the CleanspeakID to the record Data.  We will need this for reporting.
     *
     * @param ActivityModel $sender
     * @param $args
     */
    public function activityModel_afterSave_handler($sender, $args) {

        $form = val('Form', Gdn::controller(), false);;
        if ($form) {
            $cleanspeakID = $form->getValue('CleanspeakID');
        } else {
            $cleanspeakID = valr('FormPostValues.CleanspeakID', $args);
        }
        if (!$cleanspeakID) {
            return;
        }
        $activityModel = new ActivityModel();
        $activity = $activityModel->getID($args['Activity']['ActivityID']);
        if (val('Data', $activity)) {
            $data = dbdecode($activity['Activity']['Data']);
        }
        $data['CleanspeakID'] = $cleanspeakID;
        $activityModel->setField($args['Activity']['ActivityID'], 'Data', dbencode($data));
    }

    /**
     * Add CleanspeakID to the queue if present on record attributes.
     *
     * @param queueModel $sender Sending controller.
     * @param array $args sending arguments.
     */
    public function queueModel_afterConvertToQueueRow_handler($sender, $args) {
        if (valr('Data.Attributes.CleanspeakID', $args)) {
            $args['QueueRow']['CleanspeakID'] = $args['Data']['Attributes']['CleanspeakID'];
        } elseif (valr('Data.Data.CleanspeakID', $args)) {
            $args['QueueRow']['CleanspeakID'] = $args['Data']['Data']['CleanspeakID'];
        }

    }

    /**
     * Add MediaIDs to Premoderation Options.
     *
     * @param DiscussionModel $sender Sending object.
     * @param array $args Sending arguments.
     */
    public function discussionModel_beforePremoderate_handler($sender, $args) {
        $this->addMediaIDsToOptions($args);
    }

    /**
     * Add MediaIDs to Premoderation Options.
     *
     * @param CommentModel $sender Sending object.
     * @param array $args Sending arguments.
     */
    public function commentModel_beforePremoderate_handler($sender, $args) {
        $this->addMediaIDsToOptions($args);
    }

    /**
     * Add MediaIDs to Premoderation Options.
     *
     * @param array $args Sending arguments.
     */
    protected function addMediaIDsToOptions($args) {
        $mediaIDs = valr('FormPostValues.MediaIDs', $args);
        if ($mediaIDs) {
            $args['Options']['MediaIDs'] = $mediaIDs;
        }
    }


}
