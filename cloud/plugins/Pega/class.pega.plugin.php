<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
/**
 * Pega Plugin
 *
 * This plugin connects the forums to a Pega account; Once connected Staff users will
 * be able to Create Leads and Cases from Discussions and Comments in the forums.
 *
 */
class PegaPlugin extends Gdn_Plugin {

    /**
     * If status is set to this we will stop getting updates from Pega
     * @var string
     */
    protected $ClosedCaseStatusString = 'Closed';

    /**
     * If time since last update from Pega is less then this; we wont check for update - saving api calls.
     * @var int
     */
    protected $MinimumTimeForUpdate = 600;

    /**
     * Used in setup for Oauth.
     */
    const ProviderKey = 'Pega';


    /**
     * Setup the plugin
     *
     * @throws Gdn_UserException
     */
    public function setup() {
        saveToConfig('Garden.AttachmentsEnabled', true);
        $error = '';
        if (!function_exists('curl_init')) {
            $error = concatSep("\n", $error, 'This plugin requires curl.');
        }
        if ($error) {
            throw new Gdn_UserException($error, 400);
        }
        // Save the provider type.
        Gdn::sql()->replace('UserAuthenticationProvider',
            [
                'AuthenticationSchemeAlias' => 'Pega',
                'URL' => '...',
                'AssociationSecret' => '...',
                'AssociationHashMethod' => '...'
            ],
            ['AuthenticationKey' => self::ProviderKey], TRUE
        );
        Gdn::permissionModel()->define(['Garden.Staff.Allow' => 'Garden.Moderation.Manage']);

        saveToConfig('Plugins.Pega.CreateCases', true);
    }

    /**
     * @param Controller $sender
     * @param array $args
     */
    public function base_getConnections_handler($sender, $args) {
        if (!Pega::isConfigured()) {
            return;
        }
        //Staff Only
        if (!Gdn::session()->checkPermission('Garden.Staff.Allow')) {
            return;
        }
    }

    /**
     * @param ProfileController $sender
     * @param string $userReference
     * @param string $username
     * @param bool $code
     *
     */
    public function profileController_pegaConnect_create($sender, $userReference = '', $username = '', $code = FALSE) {
        $sender->permission('Garden.SignIn.Allow');
        $sender->getUserInfo($userReference, $username, '', TRUE);
        $sender->_SetBreadcrumbs(t('Connections'), userUrl($sender->User, '', 'connections'));
        //check $GET state // if DashboardConnection // then do global connection.
        $state = getValue('state', $_GET, FALSE);
        if ($state == 'DashboardConnection') {
            try {
                $tokens = Pega::getTokens($code, Pega::profileConnecUrl());
            } catch (Gdn_UserException $e) {
                $message = $e->getMessage();
                Gdn::dispatcher()->passData('Exception', htmlspecialchars($message))
                    ->dispatch('home/error');
                return;
            }
            redirectTo('/plugin/Pega/?DashboardConnection=1&'.http_build_query($tokens));
        }
        try {
            $tokens = Pega::getTokens($code, Pega::profileConnecUrl());
        } catch (Gdn_UserException $e) {
            $attributes = [
                'RefreshToken' => NULL,
                'AccessToken' => NULL,
                'InstanceUrl' => NULL,
                'Profile' => NULL,
            ];
            Gdn::userModel()->saveAttribute($sender->User->UserID, Pega::ProviderKey, $attributes);
            $message = $e->getMessage();
            Gdn::dispatcher()->passData('Exception', htmlspecialchars($message))
                ->dispatch('home/error');
            return;
        }
        $accessToken = getValue('access_token', $tokens);
        $instanceUrl = getValue('instance_url', $tokens);
        $loginID = getValue('id', $tokens);
        $refreshToken = getValue('refresh_token', $tokens);
        $pega = new Pega($accessToken, $instanceUrl);
        $profile = $pega->getLoginProfile($loginID);
        Gdn::userModel()->saveAuthentication([
            'UserID' => $sender->User->UserID,
            'Provider' => Pega::ProviderKey,
            'UniqueID' => $profile['id']
        ]);
        $attributes = [
            'RefreshToken' => $refreshToken,
            'AccessToken' => $accessToken,
            'InstanceUrl' => $instanceUrl,
            'Profile' => $profile,
        ];
        Gdn::userModel()->saveAttribute($sender->User->UserID, Pega::ProviderKey, $attributes);
        $this->EventArguments['Provider'] = Pega::ProviderKey;
        $this->EventArguments['User'] = $sender->User;
        $this->fireEvent('AfterConnection');

        $redirectUrl = userUrl($sender->User, '', 'connections');

        redirectTo($redirectUrl);
    }

    /**
     * Creates the Virtual Controller
     *
     * @param DashboardController $sender
     */
    public function pluginController_pega_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->title('Pega');
        $sender->addSideMenu('plugin/Pega');
        $sender->Form = new Gdn_Form();
        $this->dispatch($sender, $sender->RequestArgs);
    }

    /**
     * Redirect to allow for DashboardConnection
     */
    public function controller_Connect() {
        $authorizeUrl = Pega::authorizeUri(FALSE, 'DashboardConnection');
        redirectTo($authorizeUrl, 302, false);
    }

    /**
     * Redirect to allow for DashboardConnection
     */
    public function controller_Disconnect() {
        $pega = Pega::instance();
        $pega->useDashboardConnection();
        $token = getValue('token', $_GET, FALSE);
        if ($token) {
            $pega->revoke($token);
            removeFromConfig([
                'Plugins.Pega.DashboardConnection.Token' => FALSE,
                'Plugins.Pega.DashboardConnection.RefreshToken' => FALSE,
                'Plugins.Pega.DashboardConnection.Token' => FALSE,
                'Plugins.Pega.DashboardConnection.InstanceUrl' => FALSE
            ]);
        }
        redirectTo('/plugin/Pega');
    }


    public function controller_Enable() {
        saveToConfig('Plugins.Pega.DashboardConnection.Enabled', TRUE);
        redirectTo('/plugin/Pega');
    }

    public function controller_Disable() {
        removeFromConfig('Plugins.Pega.DashboardConnection.Enabled');
        redirectTo('/plugin/Pega');
    }

    /**
     * Dashboard Settings
     * Default method of virtual Pega controller.
     *
     * @param DashboardController $sender
     */
    public function controller_Index($sender) {
        $pega = Pega::instance();
        if (getValue('DashboardConnection', $_GET, FALSE)) {
            $sender->setData('DashboardConnection', TRUE);
            saveToConfig([
                'Plugins.Pega.DashboardConnection.Enabled' => TRUE,
                'Plugins.Pega.DashboardConnection.LoginId' => getValue('id', $_GET),
                'Plugins.Pega.DashboardConnection.InstanceUrl' => getValue('instance_url', $_GET),
                'Plugins.Pega.DashboardConnection.Token' => getValue('access_token', $_GET),
                'Plugins.Pega.DashboardConnection.RefreshToken' => getValue('refresh_token', $_GET),
            ]);

            $sender->informMessage('Changes Saved to Config');
            redirectTo('/plugin/Pega');
        }
        $sender->setData([
            'DashboardConnection' => c('Plugins.Pega.DashboardConnection.Enabled'),
            'DashboardConnectionProfile' => FALSE,
            'DashboardConnectionToken' => c('Plugins.Pega.DashboardConnection.Token', FALSE),
            'DashboardConnectionRefreshToken' => c('Plugins.Pega.DashboardConnection.RefreshToken', FALSE)
        ]);
        if (c('Plugins.Pega.DashboardConnection.LoginId') && c('Plugins.Pega.DashboardConnection.Enabled')) {
//         $Pega->useDashboardConnection();
            $dashboardConnectionProfile = $pega->getLoginProfile(c('Plugins.Pega.DashboardConnection.LoginId'));
            $sender->setData('DashboardConnectionProfile', $dashboardConnectionProfile);
            $sender->addCssFile('admin.css');
        }
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField([
            'Plugins.Pega.ApplicationID',
            'Plugins.Pega.Secret',
            'Plugins.Pega.AuthenticationUrl',
        ]);
        // Set the model on the form.
        $sender->Form->setModel($configurationModel);
        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === FALSE) {
            // Apply the config settings to the form.
            $sender->Form->setData($configurationModel->Data);
        } else {
            $formValues = $sender->Form->formValues();
            if ($sender->Form->isPostBack()) {
                $sender->Form->validateRule('Plugins.Pega.ApplicationID', 'function:ValidateRequired', 'ApplicationID is required');
                $sender->Form->validateRule('Plugins.Pega.Secret', 'function:ValidateRequired', 'Secret is required');
                $sender->Form->validateRule('Plugins.Pega.AuthenticationUrl', 'function:ValidateRequired', 'Authentication Url is required');
                if ($sender->Form->errorCount() == 0) {
                    saveToConfig('Plugins.Pega.ApplicationID', trim($formValues['Plugins.Pega.ApplicationID']));
                    saveToConfig('Plugins.Pega.Secret', trim($formValues['Plugins.Pega.Secret']));
                    saveToConfig('Plugins.Pega.AuthenticationUrl', rtrim(trim($formValues['Plugins.Pega.AuthenticationUrl'])), '/');

                    $sender->informMessage(t("Your changes have been saved."));
                } else {
                    $sender->informMessage(t("Error saving settings to config."));
                }
            }
        }
        $sender->Form->setValue('Plugins.Pega.ApplicationID', c('Plugins.Pega.ApplicationID'));
        $sender->Form->setValue('Plugins.Pega.Secret', c('Plugins.Pega.Secret'));
        $sender->Form->setValue('Plugins.Pega.AuthenticationUrl', c('Plugins.Pega.AuthenticationUrl'));
        $sender->render($this->getView('dashboard.php'));
    }

    /**
     * @param DiscussionController $sender
     * @param array $args
     */
    public function discussionController_discussionOptions_handler($sender, $args) {
        //Staff Only
        $session = Gdn::session();
        if (!$session->checkPermission('Garden.Staff.Allow')) {
            return;
        }

        $pega = Pega::instance();

        $userID = $args['Discussion']->InsertUserID;
        $discussionID = $args['Discussion']->DiscussionID;

        $css_class = null;

        if (isset($args['DiscussionOptions'])) {

            if (c('Plugins.Pega.CreateCases')) {
                $args['DiscussionOptions']['PegaCase'] = [
                    'Label' => t('Pega - Create Case'),
                    'Url' => "/discussion/PegaCase/Discussion/$discussionID/$userID",
                    'Class' => 'Popup'.$css_class
                ];
            }
            //remove create Create already created
            $attachments = getValue('Attachments', $args['Discussion'], []);
            foreach ($attachments as $attachment) {
                if ($attachment['Type'] == 'pega-case') {
                    unset($args['DiscussionOptions']['PegaCase']);
                }
            }
        }

    }

    /**
     * @param CommentController $sender
     * @param $args
     */
    public function discussionController_commentOptions_handler($sender, $args) {
        //Staff Only
        $session = Gdn::session();
        if (!$session->checkPermission('Garden.Staff.Allow')) {
            return;
        }

        $pega = Pega::instance();

        $userID = $args['Comment']->InsertUserID;
        $commentID = $args['Comment']->CommentID;
        $css_class = null;


        if (c('Plugins.Pega.CreateCases')) {
            $args['CommentOptions']['PegaCase'] = [
                'Label' => t('Pega - Create Case'),
                'Url' => "/discussion/PegaCase/Comment/$commentID/$userID",
                'Class' => 'Popup'.$css_class
            ];
        }
        //remove create Create already created
        $attachments = getValue('Attachments', $args['Comment'], []);
        foreach ($attachments as $attachment) {
            if ($attachment['Type'] == 'pega-case') {
                unset($args['CommentOptions']['PegaCase']);
            }
        }
    }

    /**
     * Popup to Add Pega Case
     *
     * @param DiscussionController $sender
     * @param array $args
     * @throws Gdn_UserException
     * @throws Exception
     *
     */
    public function discussionController_pegaCase_create($sender, $args) {

        // Signed in users only.
        if (!(Gdn::session()->isValid())) {
            throw permissionException('Garden.Signin.Allow');
        }
        //Permissions
        $sender->permission('Garden.Staff.Allow');
        // Check that we are connected to Pega
        $pega = Pega::instance();

        //Get Request Arguments
        $arguments = $sender->RequestArgs;
        if (sizeof($arguments) != 3) {
            throw new Gdn_UserException('Invalid Request Url');
        }
        $type = $arguments[0];
        $elementID = $arguments[1];
        $userID = $arguments[2];
        //Get User
        $user = Gdn::userModel()->getID($userID);
        //Setup Form
        $sender->Form = new Gdn_Form();
        //Get Content
        if ($type == 'Discussion') {
            $content = $sender->DiscussionModel->getID($elementID);
            $url = discussionUrl($content, 1);
        } elseif ($type == 'Comment') {
            $commentModel = new CommentModel();
            $content = $commentModel->getID($elementID);
            $url = commentUrl($content);
        } else {
            throw new Gdn_UserException('Content Type not supported');
        }
        $sender->Form->addHidden('SourceUri', $url);

        $attachmentModel = AttachmentModel::instance();

        //If form is being submitted
        if ($sender->Form->isPostBack() && $sender->Form->authenticatedPostBack() === TRUE) {
            //Form Validation
            $sender->Form->validateRule('FirstName', 'function:ValidateRequired', 'First Name is required');
            $sender->Form->validateRule('LastName', 'function:ValidateRequired', 'Last Name is required');
            $sender->Form->validateRule('Email', 'function:ValidateRequired', 'Email is required');
            //if no errors
            if ($sender->Form->errorCount() == 0 && $attachmentModel->validate($sender->Form->formValues())) {
                $formValues = $sender->Form->formValues();

                //check to see if user is a contact
                //$Contact = $Pega->findContact($FormValues['Email']); // presume that the user is a contact
//            if (!$Contact['Id']) {
//               $this->reconnect($Sender, $Args);
//               //If not a contact then add contact
//               $Contact['Id'] = $Pega->createContact(array(
//                  'FirstName' => $FormValues['FirstName'],
//                  'LastName' => $FormValues['LastName'],
//                  'Email' => $FormValues['Email'],
//                  'LeadSource' => $FormValues['LeadSource'],
//               ));
//            }

                //Create Case using Pega API
                $caseID = $pega->createCase([
                    'Status' => 1,
                    'Subject' => $sender->DiscussionModel->getID($content->DiscussionID)->Name,
                    'Description' => $formValues['Body'],
                    'Vanilla__ForumUrl__c' => $formValues['SourceUri']
                ]);
                //Save information to our Attachment Table
                $iD = $attachmentModel->save([
                    'Type' => 'pega-case',
                    'ForeignID' => $attachmentModel->rowID($content),
                    'ForeignUserID' => $content->InsertUserID,
                    'Source' => 'Pega',
                    'SourceID' => $caseID,
                    'SourceURL' => c('Plugins.Pega.BaseUrl').'/forum/interaction/'.$caseID,
                    'Status' => "N/A",
                    'Priority' => "N/A", // pega does not need these
                ]);
                if (!$iD) {
                    $sender->Form->setValidationResults($attachmentModel->validationResults());
                }
                $sender->jsonTarget('', $url, 'Redirect');
                $sender->informMessage('Case Added to Pega');

            }

        } else {
            $sender->Form->setValidationResults($attachmentModel->validationResults());
        }
        list($firstName, $lastName) = $this->getFirstNameLastName($user->Name);

        try {
            $data = [
                'DiscussionID' => $content->DiscussionID,
                'FirstName' => $firstName,
                'LastName' => $lastName,
                'Email' => $user->Email,
                'Body' => Gdn_Format::textEx($content->Body)
            ];
        } catch (Gdn_UserException $e) {
            $this->reconnect($sender, $args);
        }

        $sender->Form->setData($data);
        $sender->setData('Data', $data);
        $sender->render('createcase', '', 'plugins/Pega');

    }

    /**
     * @param DiscussionController $sender
     * @param array $args
     */
    public function discussionController_afterDiscussionBody_handler($sender, $args) {
        trace('DiscussionController_AfterDiscussionBody_Handler');
        $this->writeAndUpdateAttachments($sender, $args);
    }

    /**
     * @param DiscussionController $sender
     * @param array $args
     */
    public function discussionController_afterCommentBody_handler($sender, $args) {
        trace('AfterCommentBody_Handler');
        $this->writeAndUpdateAttachments($sender, $args);
    }


    protected function writeAndUpdateAttachments($sender, $args) {

//        error_log("WriteAndUpdateAttachments\n\n\n", 3, "/Users/patrick/my-errors.log");

        $type = getValue('Type', $args);

        if ($type == 'Discussion') {
            $content = 'Discussion';
        } elseif ($type == 'Comment') {
            $content = 'Comment';
        } else {
            return;
        }
        $session = Gdn::session();
        if (!$session->checkPermission('Garden.SignIn.Allow')) {
            return;
        }

        if (!$session->checkPermission('Garden.Staff.Allow') && $session->isValid() && isset($args[$content]->Attachments)) {
            foreach ($args[$content]->Attachments as $attachment) {
                if ($attachment['Type'] == 'pega-case') {;
                    if ($attachment['ForeignUserID'] == $session->UserID) {
//                        error_log("WriteGenericAttachment line 529\n\n\n", 3, "/Users/patrick/my-errors.log");
                        writeGenericAttachment([
                            'Icon' => 'case',
                            'Body' => wrap(t('A ticket has been generated from this post.'), 'p'),
                            'Fields' => [
                                'one' => 'two'
                            ]
                        ]);

                    } else {
//                        error_log("WriteGenericAttachment line 540\n\n\n", 3, "/Users/patrick/my-errors.log");
                        writeGenericAttachment([
                            'Icon' => 'case',
                            'Body' => wrap(t('A case has been generated from this post.'), 'p'),
                        ]);
                    }
                }
            }
            return;
        }
        if (!$session->checkPermission('Garden.Staff.Allow')) {
//            error_log("WriteAttachment aborted, no permissions\n\n\n", 3, "/Users/patrick/my-errors.log");
            return;
        }

        if (isset($args[$content]->Attachments)) {
//            error_log("Attempt to update attachments. " . json_encode($Args[$Content]->Attachments) . "\n\n\n", 3, "/Users/patrick/my-errors.log");
            try {
                $this->updateAttachments($args[$content]->Attachments, $sender, $args);
            } catch (Gdn_UserException $e) {
                $sender->informMessage('Error Reconnecting to Pega');
            }
//            error_log("Write attachments. " . json_encode($Args[$Content]->Attachments) . "\n\n\n", 3, "/Users/patrick/my-errors.log");
//            writeAttachments($Args[$Content]->Attachments);
        }
    }

    /**
     * @param Gdn_Controller $sender
     */
    public function settingsController_pega_create($sender) {
        $sender->permission('Garden.Settings.Manage');

        $cf = new ConfigurationModule($sender);

        $cf->initialize(
            [
                'Plugins.Pega.BaseUrl' => [],
                'Plugins.Pega.Username' => [],
                'Plugins.Pega.Password' => []
            ]
        );

        $sender->addSideMenu();
        $sender->setData('Title', t('Pega Settings'));
        $cf->renderAll();
    }

    /**
     * @param array $attachments
     * @param $sender
     * @param array $args
     */
    protected function updateAttachments(&$attachments, $sender, $args) {
        return; //temporarily disable this functionality; response time too slow
        $pega = Pega::instance();
        $attachmentModel = new AttachmentModel();

        foreach ($attachments as &$attachment) {

            if (!$this->isToBeUpdated($attachment)) {
                continue;
            }

            $caseResponse = $pega->getCase($attachment['SourceID']);
//            $CaseResponse['HttpCode'] = 200;
//            $CaseResponse['Status'] = 1;
//            $CaseResponse['Priority'] = 1;
//            $CaseResponse['LastModifiedDate'] = date();
//            $CaseResponse['CaseNumber'] = 10;


            $updatedAttachment = (array)$attachmentModel->getID($attachment['AttachmentID']);

            if ($caseResponse['HttpCode'] == 401) {
                $this->reconnect($sender, $args);
                continue;
            } elseif ($caseResponse['HttpCode'] == 404) {
                $updatedAttachment['DateUpdated'] = Gdn_Format::toDateTime();
                $updatedAttachment['Error'] = t('Case has been deleted from Pega');
                $attachmentModel->save($updatedAttachment);
                $attachment = $updatedAttachment;
                continue;
            } elseif ($caseResponse['HttpCode'] == 200) {
//                error_log("Saving attachement" . json_encode($CaseResponse) . "\n\n\n", 3, "/Users/patrick/my-errors.log");
                $case = $caseResponse['Response'];
                $updatedAttachment['Status'] = $case['pyWorkObjectStatus'];
                $updatedAttachment['Priority'] = 1;
                $updatedAttachment['LastModifiedDate'] = Gdn_Format::toDateTime();
                $updatedAttachment['CaseNumber'] = $case['pyID'];
                $updatedAttachment['DateUpdated'] = Gdn_Format::toDateTime();
                $attachmentModel->save($updatedAttachment);
                $attachment = $updatedAttachment;
            }
        }
    }

    /**
     * @param ProfileController $sender
     * @param array $args
     */
    public function profileController_render_before($sender, $args) {
        $attachmentModel = AttachmentModel::instance();
        $attachmentModel->joinAttachmentsToUser($sender, $args, ['Type' => 'pega-lead'], 1);
    }

    /**
     * @param ProfileController $Sender
     * @param array $Args
     */
    public function profileController_afterUserInfo_handler($Sender, $Args) {
        //check permissions
        if (!Gdn::session()->checkPermission('Garden.Staff.Allow')) {
            return;
        }
        $Sender->addCssFile('vanillicon.css', 'static');
        $Attachments = $Sender->Data['Attachments'];
        if ($Sender->deliveryMethod() === DELIVERY_METHOD_XHTML) {
            require_once $Sender->fetchViewLocation('attachment', '', 'plugins/Pega');
        }
        foreach ($Attachments as $Attachment) {
            if ($Attachment['Type'] == 'pega-lead') {
                writePegaLeadAttachment($Attachment);
            }
        }
    }

    /**
     * Take a Full Name and attempt to split it into FirstName LastName
     *
     * @param string $fullName
     * @return array $Name
     *    [FirstName]
     *    [LastName]
     */
    public function getFirstNameLastName($fullName) {
        $nameParts = explode(' ', $fullName);
        switch (count($nameParts)) {
            case 3:
                $firstName = $nameParts[0].' '.$nameParts[1];
                $lastName = $nameParts[2];
                break;
            case 2:
                $firstName = $nameParts[0];
                $lastName = $nameParts[1];
                break;
            default:
                $firstName = $fullName;
                $lastName = '';
                break;
        }
        return [$firstName, $lastName];
    }

    /**
     * @param AssetModel $sender
     */
    public function assetModel_styleCss_handler($sender) {
        $sender->addCssFile('pega.css', 'plugins/Pega');
    }

    /**
     * @param array $attachment Attachment Data - see AttachmentModel
     * @param string $type case or lead
     * @return bool
     */
    protected function isToBeUpdated($attachment, $type = 'pega-case') {
        if (getValue('Status', $attachment) == $this->ClosedCaseStatusString) {
            return FALSE;
        }
        $timeDiff = time() - strtotime($attachment['DateUpdated']);
        if ($timeDiff < $this->MinimumTimeForUpdate) {
            trace("Not Checking For Update: $timeDiff seconds since last update");
            return FALSE;
        }
        if (isset($attachment['LastModifiedDate'])) {
            if ($type == 'pega-case') {
                $timeDiff = time() - strtotime($attachment['LastModifiedDate']);
                if ($timeDiff < $this->MinimumTimeForUpdate && $attachment['Status'] != $this->ClosedCaseStatusString) {
                    trace("Not Checking For Update: $timeDiff seconds since last update");
                    return FALSE;
                }
            }
        }
        return TRUE;
    }

    /**
     * Add attachment views.
     *
     * @param DiscussionController $Sender Sending Controller.
     */
    public function discussionController_fetchAttachmentViews_handler($Sender) {
        require_once $Sender->fetchViewLocation('attachment', '', 'plugins/Pega');
    }

    public function isConfigured() {
        $pega = Pega::instance();
        return $pega->isConfigured();
    }

}
