<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Container\Container;
use Vanilla\Web\TwigRenderTrait;

/**
 * Salesforce Plugin
 *
 * This plugin connects the forums to a salesforce account; Once connected Staff users will
 * be able to Create Leads and Cases from Discussions and Comments in the forums.
 *
 */
class SalesforcePlugin extends Gdn_Plugin {

    use TwigRenderTrait;

    /**
     * If status is set to this we will stop getting updates from Salesforce
     *
     * @var string
     */
    protected $closedCaseStatusString = 'Closed';

    /**
     * If time since last update from Salesforce is less then this; we wont check for update - saving api calls.
     *
     * @var int
     */
    protected $minimumTimeForUpdate = 600;

    /**
     * Used in setup for OAuth.
     */
    const PROVIDERKEY = 'Salesforce';

    /** @var SsoUtils */
    private $ssoUtils;

    /**
     * Constructor.
     *
     * @param SsoUtils $ssoUtils
     */
    public function __construct(SsoUtils $ssoUtils) {
        parent::__construct();
        $this->ssoUtils = $ssoUtils;
    }

    /**
     * Setup the plugin
     *
     * @throws Gdn_UserException If no cURL is set.
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
        Gdn::sql()->replace(
            'UserAuthenticationProvider',
            [
                'AuthenticationSchemeAlias' => 'salesforce',
                'URL' => '...',
                'AssociationSecret' => '...',
                'AssociationHashMethod' => '...'
            ],
            [
                'AuthenticationKey' => self::PROVIDERKEY
            ],
            true
        );
        Gdn::permissionModel()->define(['Garden.Staff.Allow' => 'Garden.Moderation.Manage']);
    }

    /**
     * Get connection
     *
     * @param Controller $sender
     * @param array $args
     */
    public function base_getConnections_handler($sender, $args) {
        if (!Salesforce::isConfigured()) {
            return;
        }
        //Staff Only
        if (!Gdn::session()->checkPermission('Garden.Staff.Allow')) {
            return;
        }
        $sf = getValueR('User.Attributes.'.Salesforce::PROVIDERKEY, $args);
        trace($sf);
        $profile = getValueR('User.Attributes.'.Salesforce::PROVIDERKEY.'.Profile', $args);
        $sender->Data["Connections"][Salesforce::PROVIDERKEY] = [
            'Icon' => $this->getWebResource('icon.svg', '/'),
            'Name' => Salesforce::PROVIDERKEY,
            'ProviderKey' => Salesforce::PROVIDERKEY,
            'ConnectUrl' => Salesforce::authorizeUri(Salesforce::profileConnecUrl()),
            'Profile' => [
                'Name' => val('fullname', $profile),
                'Photo' => null
            ]
        ];
    }

    /**
     * Create connection
     *
     * @param ProfileController $sender
     * @param string $userReference
     * @param string $username
     * @param bool $code
     */
    public function profileController_salesforceConnect_create($sender, $userReference = '', $username = '', $code = false) {
        $sender->permission('Garden.SignIn.Allow');

        $state = json_decode(Gdn::request()->get('state', ''), true);
        $suppliedStateToken = val('token', $state);
        $this->ssoUtils->verifyStateToken('salesforceConnect', $suppliedStateToken);

        $sender->getUserInfo($userReference, $username, '', true);
        $sender->_SetBreadcrumbs(t('Connections'), userUrl($sender->User, '', 'connections'));


        if (val('type', $state) === 'DashboardConnection') {
            try {
                $tokens = Salesforce::getTokens($code, Salesforce::profileConnecUrl());
            } catch (Gdn_UserException $e) {
                $message = $e->getMessage();
                Gdn::dispatcher()->passData('Exception', htmlspecialchars($message))
                    ->dispatch('home/error');
                return;
            }
            redirectTo('/plugin/Salesforce/?DashboardConnection=1&'.http_build_query($tokens));
        }
        try {
            $tokens = Salesforce::getTokens($code, Salesforce::profileConnecUrl());
        } catch (Gdn_UserException $e) {
            $attributes = [
                'RefreshToken' => null,
                'AccessToken' => null,
                'InstanceUrl' => null,
                'Profile' => null,
            ];
            Gdn::userModel()->saveAttribute($sender->User->UserID, Salesforce::PROVIDERKEY, $attributes);
            $message = $e->getMessage();
            Gdn::dispatcher()->passData('Exception', htmlspecialchars($message))
                ->dispatch('home/error');
            return;
        }
        $accessToken = val('access_token', $tokens);
        $instanceUrl = val('instance_url', $tokens);
        $loginID = val('id', $tokens);
        $refreshToken = val('refresh_token', $tokens);
        $salesforce = new Salesforce($accessToken, $instanceUrl);
        $profile = $salesforce->getLoginProfile($loginID);
        Gdn::userModel()->saveAuthentication([
            'UserID' => $sender->User->UserID,
            'Provider' => Salesforce::PROVIDERKEY,
            'UniqueID' => $profile['id']
        ]);
        $attributes = [
            'RefreshToken' => $refreshToken,
            'AccessToken' => $accessToken,
            'InstanceUrl' => $instanceUrl,
            'Profile' => $profile,
        ];
        Gdn::userModel()->saveAttribute($sender->User->UserID, Salesforce::PROVIDERKEY, $attributes);
        $this->EventArguments['Provider'] = Salesforce::PROVIDERKEY;
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
    public function pluginController_salesforce_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->title('Salesforce');
        $sender->addSideMenu('plugin/Salesforce');
        $sender->Form = new Gdn_Form();
        $this->dispatch($sender, $sender->RequestArgs);
    }

    /**
     * Redirect to allow for DashboardConnection
     */
    public function controller_connect() {
        $authorizeUrl = Salesforce::authorizeUri(false, 'DashboardConnection');
        redirectTo($authorizeUrl, 302, false);
    }

    /**
     * Redirect to allow for DashboardConnection
     */
    public function controller_disconnect() {
        $salesforce = Salesforce::instance();
        $salesforce->useDashboardConnection();
        $token = val('token', $_GET, false);
        if ($token) {
            $salesforce->revoke($token);
            removeFromConfig('Plugins.Salesforce.DashboardConnection.Token');
            removeFromConfig('Plugins.Salesforce.DashboardConnection.RefreshToken');
            removeFromConfig('Plugins.Salesforce.DashboardConnection.Token');
            removeFromConfig('Plugins.Salesforce.DashboardConnection.InstanceUrl');
        }
        redirectTo('/plugin/Salesforce');
    }

    /**
     * Redirect to allow for DashboardConnection
     *
     * @param Controller $sender
     */
    public function controller_reconnect($sender) {
        $salesforce = Salesforce::instance();
        $salesforce->useDashboardConnection();
        $token = val('token', $_GET, false);
        if ($token) {
            $refreshResponse = $salesforce->refresh($token);
            $accessToken = val('access_token', $refreshResponse);
            $instanceUrl = val('instance_url', $refreshResponse);
            saveToConfig([
                'Plugins.Salesforce.DashboardConnection.InstanceUrl' => $instanceUrl,
                'Plugins.Salesforce.DashboardConnection.Token' => $accessToken
            ]);
            $salesforce->setAccessToken($accessToken);
            $salesforce->setInstanceUrl($instanceUrl);
            redirectTo('/plugin/Salesforce');
        }
    }

    /**
     * Enable controller
     */
    public function controller_enable() {
        saveToConfig('Plugins.Salesforce.DashboardConnection.Enabled', true);
        redirectTo('/plugin/Salesforce');
    }

    /**
     * Disable controller
     */
    public function controller_disable() {
        removeFromConfig('Plugins.Salesforce.DashboardConnection.Enabled');
        redirectTo('/plugin/Salesforce');
    }

    /**
     * Dashboard Settings
     * Default method of virtual Salesforce controller.
     *
     * @param DashboardController $sender
     */
    public function controller_index($sender) {
        $salesforce = Salesforce::instance();
        if (val('DashboardConnection', $_GET, false)) {
            $sender->setData('DashboardConnection', true);
            saveToConfig([
                'Plugins.Salesforce.DashboardConnection.Enabled' => true,
                'Plugins.Salesforce.DashboardConnection.LoginId' => val('id', $_GET),
                'Plugins.Salesforce.DashboardConnection.InstanceUrl' => val('instance_url', $_GET),
                'Plugins.Salesforce.DashboardConnection.Token' => val('access_token', $_GET),
                'Plugins.Salesforce.DashboardConnection.RefreshToken' => val('refresh_token', $_GET),
            ]);

            $sender->informMessage('Changes Saved to Config');
            redirectTo('/plugin/Salesforce');
        }
        $sender->setData([
            'DashboardConnection' => c('Plugins.Salesforce.DashboardConnection.Enabled'),
            'DashboardConnectionProfile' => false,
            'DashboardConnectionToken' => c('Plugins.Salesforce.DashboardConnection.Token', false),
            'DashboardConnectionRefreshToken' => c('Plugins.Salesforce.DashboardConnection.RefreshToken', false),
            'Plugins.Salesforce.SyncUsers' => c('Plugins.Salesforce.SyncUsers')
        ]);
        if (c('Plugins.Salesforce.DashboardConnection.LoginId') && c('Plugins.Salesforce.DashboardConnection.Enabled')) {
            $dashboardConnectionProfile = $salesforce->getLoginProfile(c('Plugins.Salesforce.DashboardConnection.LoginId'));
            $sender->setData('DashboardConnectionProfile', $dashboardConnectionProfile);
            $sender->addCssFile('admin.css');
        }
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField([
            'Plugins.Salesforce.ApplicationID',
            'Plugins.Salesforce.Secret',
            'Plugins.Salesforce.AuthenticationUrl',
            'Plugins.Salesforce.SyncUsers',
        ]);
        // Set the model on the form.
        $sender->Form->setModel($configurationModel);
        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $sender->Form->setData($configurationModel->Data);
        } else {
            $formValues = $sender->Form->formValues();
            if ($sender->Form->isPostBack()) {
                saveToConfig('Plugins.Salesforce.SyncUsers', (boolean)$formValues['Plugins.Salesforce.SyncUsers']);
                $sender->Form->validateRule('Plugins.Salesforce.ApplicationID', 'function:ValidateRequired', 'ApplicationID is required');
                $sender->Form->validateRule('Plugins.Salesforce.Secret', 'function:ValidateRequired', 'Secret is required');
                $sender->Form->validateRule('Plugins.Salesforce.AuthenticationUrl', 'function:ValidateRequired', 'Authentication Url is required');
                if ($sender->Form->errorCount() == 0) {
                    saveToConfig('Plugins.Salesforce.ApplicationID', trim($formValues['Plugins.Salesforce.ApplicationID']));
                    saveToConfig('Plugins.Salesforce.Secret', trim($formValues['Plugins.Salesforce.Secret']));
                    saveToConfig('Plugins.Salesforce.AuthenticationUrl', rtrim(trim($formValues['Plugins.Salesforce.AuthenticationUrl'])), '/');

                    $sender->informMessage(t("Your changes have been saved."));
                } else {
                    $sender->informMessage(t("Error saving settings to config."));
                }
            }
        }
        $sender->Form->setValue('Plugins.Salesforce.ApplicationID', c('Plugins.Salesforce.ApplicationID'));
        $sender->Form->setValue('Plugins.Salesforce.Secret', c('Plugins.Salesforce.Secret'));
        $sender->Form->setValue('Plugins.Salesforce.AuthenticationUrl', c('Plugins.Salesforce.AuthenticationUrl'));
        $sender->render($this->getView('dashboard.php'));
    }

    /**
     * Create "Add Lead" and "Create Case" options on discussions options
     *
     * @param DiscussionController $sender
     * @param array $args
     */
    public function discussionController_discussionOptions_handler($sender, $args) {
        //Staff Only
        $session = Gdn::session();
        if (!$session->checkPermission('Garden.Staff.Allow')) {
            return;
        }
        $userID = $args['Discussion']->InsertUserID;
        $discussionID = $args['Discussion']->DiscussionID;
        if (isset($args['DiscussionOptions'])) {
            $args['DiscussionOptions']['SalesforceLead'] = [
                'Label' => t('Salesforce - Add Lead'),
                'Url' => "/discussion/SalesforceLead/Discussion/$discussionID/$userID",
                'Class' => 'Popup'
            ];
            $args['DiscussionOptions']['SalesforceCase'] = [
                'Label' => t('Salesforce - Create Case'),
                'Url' => "/discussion/SalesforceCase/Discussion/$discussionID/$userID",
                'Class' => 'Popup'
            ];
            //remove create Create already created
            $attachments = val('Attachments', $args['Discussion'], []);
            foreach ($attachments as $attachment) {
                if ($attachment['Type'] == 'salesforce-case') {
                    unset($args['DiscussionOptions']['SalesforceCase']);
                }
                if ($attachment['Type'] == 'salesforce-lead') {
                    unset($args['DiscussionOptions']['SalesforceLead']);
                }
            }
        }
    }

    /**
     * Create "Add Lead" and "Create Case" options on discussion thread options
     *
     * @param DiscussionController $sender
     * @param array $args
     */
    public function discussionController_commentOptions_handler($sender, $args) {
        //Staff Only
        $session = Gdn::session();
        if (!$session->checkPermission('Garden.Staff.Allow')) {
            return;
        }
        $userID = $args['Comment']->InsertUserID;
        $commentID = $args['Comment']->CommentID;
        $args['CommentOptions']['SalesforceLead'] = [
            'Label' => t('Salesforce - Add Lead'),
            'Url' => "/discussion/SalesforceLead/Comment/$commentID/$userID",
            'Class' => 'Popup'
        ];
        $args['CommentOptions']['SalesforceCase'] = [
            'Label' => t('Salesforce - Create Case'),
            'Url' => "/discussion/SalesforceCase/Comment/$commentID/$userID",
            'Class' => 'Popup'
        ];
        //remove create Create already created
        $attachments = val('Attachments', $args['Comment'], []);
        foreach ($attachments as $attachment) {
            if ($attachment['Type'] == 'salesforce-case') {
                unset($args['CommentOptions']['SalesforceCase']);
            }
            if ($attachment['Type'] == 'salesforce-lead') {
                unset($args['CommentOptions']['SalesforceLead']);
            }
        }
    }

    /**
     * Creates the Add Salesforce Lead Panel
     *
     * @param DiscussionController $sender
     * @param array $args
     * @throws Exception Permission Denied.
     * @throws Gdn_UserException If invalid request or content type not supported.
     */
    public function discussionController_salesforceLead_create($sender, $args) {
        // Signed in users only.
        if (!(Gdn::session()->UserID)) {
            throw permissionException('Garden.Signin.Allow');
        }
        // Check Permissions
        $sender->permission('Garden.Staff.Allow');
        // Check that we are connected to salesforce
        $salesforce = Salesforce::instance();
        if (!$salesforce->isConnected()) {
            $this->loginModal($sender);
            return;
        }
        // Setup Form
        $sender->Form = new Gdn_Form();
        // Get Request Arguments
        $arguments = $sender->RequestArgs;
        if (sizeof($arguments) != 3) {
            throw new Gdn_UserException('Invalid Request Url');
        }
        $type = $arguments[0];
        $elementID = $arguments[1];
        $userID = $arguments[2];
        $user = Gdn::userModel()->getID($userID);
        // Get Content
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
        $sender->Form->addHidden('ForumUrl', $url);
        $sender->Form->addHidden('Description', Gdn_Format::textEx(Gdn_Format::to($content->Body, $content->Format)));

        //See if user is already registered in Sales Force
        if (!c('Plugins.Salesforce.AllowDuplicateLeads', false)) {
            $existingLeadResponse = $salesforce->findLead($user->Email);
            if ($existingLeadResponse['HttpCode'] == 401) {
                $salesforce->reconnect();
                $existingLeadResponse = $salesforce->findLead($user->Email);
            }
            $existingLead = $existingLeadResponse['Response'];

            if ($existingLead) {
                $sender->setData('LeadID', $existingLead['Id']);
                $sender->render('existinglead', '', 'plugins/Salesforce');
                return;
            }
        }
        $attachmentModel = AttachmentModel::instance();

        // If form is being submitted
        if ($sender->Form->isPostBack() && $sender->Form->authenticatedPostBack() === true) {
            // Form Validation
            $sender->Form->validateRule('FirstName', 'function:ValidateRequired', 'First Name is required');
            $sender->Form->validateRule('LastName', 'function:ValidateRequired', 'Last Name is required');
            $sender->Form->validateRule('Email', 'function:ValidateRequired', 'Email is required');
            $sender->Form->validateRule('Company', 'function:ValidateRequired', 'Company is required');
            $sender->fireEvent('ValidateLead');
            // If no errors
            if ($sender->Form->errorCount() == 0) {
                $formValues = $sender->Form->formValues();
                // Create Lead in salesforce
                $leadData = [
                    'FirstName' => $formValues['FirstName'],
                    'LastName' => $formValues['LastName'],
                    'Email' => $formValues['Email'],
                    'LeadSource' => $formValues['LeadSource'],
                    'Company' => $formValues['Company'],
                    'Title' => $formValues['Title'],
                    'Status' => $formValues['Status'],
                    'Description' => $formValues['Description']
                ];
                // Add Vanilla custom field only if it exists.
                $vanillaUrl = $salesforce->salesforceFieldExists('Vanilla__ForumUrl__c', 'Lead');
                if ($vanillaUrl) {
                    $leadData['Vanilla__ForumUrl__c'] = $formValues['ForumUrl'] ?? '';
                }

                $sender->EventArguments['LeadData'] = &$leadData;
                $sender->fireEvent('SendingLeadData');
                $leadID = $salesforce->createLead($leadData);
                // Save Lead information in our Attachment Table
                $attachmentData = [
                    'Type' => 'salesforce-lead',
                    'ForeignID' => $attachmentModel->rowID($content),
                    'ForeignUserID' => $content->InsertUserID,
                    'Source' => 'salesforce',
                    'SourceID' => $leadID,
                    'SourceURL' => c('Plugins.Salesforce.AuthenticationUrl').'/'.$leadID,
                    'FirstName' => $formValues['FirstName'],
                    'LastName' => $formValues['LastName'],
                    'Company' => $formValues['Company'],
                    'Title' => $formValues['Title'],
                    'Status' => $formValues['Status'],
                    'LastModifiedDate' => Gdn_Format::toDateTime(),
                ];
                $sender->EventArguments['attachmentData'] = &$attachmentData;
                $sender->fireEvent('SavingLeadAttachment');
                $iD = $attachmentModel->save($attachmentData);

                if (!$iD) {
                    $sender->Form->setValidationResults($attachmentModel->validationResults());
                }

                $sender->jsonTarget('', $url, 'Redirect');
                $sender->informMessage('Salesforce Lead Created.');
            }
        }

        try {
            $salesforce->getLeadStatusOptions();
        } catch (Gdn_UserException $e) {
            $salesforce->reconnect();
        }

        list($firstName, $lastName) = $this->getFirstNameLastName($user->Name);
        $data = [
            'DiscussionID' => $content->DiscussionID,
            'FirstName' => $firstName,
            'LastName' => $lastName,
            'Name' => $user->Name,
            'Email' => $user->Email,
            'Title' => $user->Title,
            'LeadSource' => c('Salesforce.SourceValue', 'Vanilla'),
            'Options' => $salesforce->getLeadStatusOptions(),
            'Type' => $type,
            'CommentID' => val('CommentID', $content),
            'InsertUserID' => val('InsertUserID', $content),
        ];
        $this->EventArguments['Data'] = &$data;
        $this->fireEvent('LeadFormData');

        $sender->Form->setData($data);
        $sender->setData('Data', $data);
        $sender->render('addlead', '', 'plugins/Salesforce');
    }

    /**
     * Popup to Add Salesforce Case
     *
     * @param DiscussionController $sender
     * @param array $args
     * @throws Gdn_UserException If invalid request URL.
     * @throws Exception Permission Denied.
     */
    public function discussionController_salesforceCase_create($sender, $args) {

        // Signed in users only.
        if (!(Gdn::session()->isValid())) {
            throw permissionException('Garden.Signin.Allow');
        }
        //Permissions
        $sender->permission('Garden.Staff.Allow');
        // Check that we are connected to salesforce
        $salesforce = Salesforce::instance();
        if (!$salesforce->isConnected()) {
            $this->loginModal($sender);
            return;
        }
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
        $sender->Form->addHidden('Origin', c('Salesforce.OriginValue', 'Vanilla'));
        $sender->Form->addHidden('LeadSource', c('Salesforce.SourceValue', 'Vanilla'));
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
        if ($sender->Form->isPostBack() && $sender->Form->authenticatedPostBack() === true) {
            //Form Validation
            $sender->Form->validateRule('FirstName', 'function:ValidateRequired', 'First Name is required');
            $sender->Form->validateRule('LastName', 'function:ValidateRequired', 'Last Name is required');
            $sender->Form->validateRule('Email', 'function:ValidateRequired', 'Email is required');
            $sender->fireEvent('ValidateCase');
            //if no errors
            if ($sender->Form->errorCount() == 0 && $attachmentModel->validate($sender->Form->formValues())) {
                $formValues = $sender->Form->formValues();

                //check to see if user is a contact
                $contact = $salesforce->findContact($formValues['Email']);
                if (!$contact['Id']) {
                    //if not a contact then add contact
                    $contactData = $this->createContactData($formValues);

                    $sender->EventArguments['ContactData'] = &$contactData;
                    $sender->fireEvent('CreateSalesforceContact');
                    $contact['Id'] = $salesforce->createContact($contactData);
                }

                //Create Case using salesforce API
                $caseData = [
                    'ContactId' => $contact['Id'],
                    'Status' => $formValues['Status'],
                    'Origin' => $formValues['Origin'],
                    'Priority' => $formValues['Priority'],
                    'Subject' => $sender->DiscussionModel->getID($content->DiscussionID)->Name,
                    'Description' => $formValues['Body']
                ];

                // Add Vanilla custom field only if it exists.
                $vanillaUrl = $salesforce->salesforceFieldExists('Vanilla__ForumUrl__c', 'Case');
                if ($vanillaUrl) {
                    $caseData['Vanilla__ForumUrl__c'] = $formValues['SourceUri'] ?? '';
                }
                $sender->EventArguments['CaseData'] = &$caseData;
                $sender->fireEvent('SendingCaseData');
                $caseID = $salesforce->createCase($caseData);

                //Save information to our Attachment Table
                $attachmentData = [
                    'Type' => 'salesforce-case',
                    'ForeignID' => $attachmentModel->rowID($content),
                    'ForeignUserID' => $content->InsertUserID,
                    'Source' => 'salesforce',
                    'SourceID' => $caseID,
                    'SourceURL' => c('Plugins.Salesforce.AuthenticationUrl').'/'.$caseID,
                    'Status' => $formValues['Status'],
                    'Priority' => $formValues['Priority'],

                ];
                $sender->EventArguments['AttachmentData'] = &$attachmentData;
                $sender->fireEvent('SavingCaseAttachment');
                $iD = $attachmentModel->save($attachmentData);
                if (!$iD) {
                    $sender->Form->setValidationResults($attachmentModel->validationResults());
                }
                $sender->jsonTarget('', $url, 'Redirect');
                $sender->informMessage('Case Added to Salesforce');
            }
        } else {
            $sender->Form->setValidationResults($attachmentModel->validationResults());
        }

        try {
            $salesforce->getCasePriorityOptions();
        } catch (Gdn_UserException $e) {
            $salesforce->reconnect();
        }

        $format = Gdn::getContainer()->get(\Vanilla\Formatting\FormatService::class);
        $body = $format->renderPlainText($content->Body, $content->Format);

        list($firstName, $lastName) = $this->getFirstNameLastName($user->Name);
        $data = [
            'DiscussionID' => $content->DiscussionID,
            'FirstName' => $firstName,
            'LastName' => $lastName,
            'Email' => $user->Email,
            'LeadSource' => c('Salesforce.SourceValue', 'Vanilla'),
            'Origin' => c('Salesforce.OriginValue', 'Vanilla'),
            'Options' => $salesforce->getCaseStatusOptions(),
            'Priorities' => $salesforce->getCasePriorityOptions(),
            'Body' => $body,
            'Type' => $type,
            'CommentID' => val('CommentID', $content),
            'InsertUserID' => val('InsertUserID', $content),
        ];

        $sender->EventArguments['Data'] = &$data;
        $sender->fireEvent('CaseFormData');

        $sender->Form->setData($data);
        $sender->setData('Data', $data);
        $sender->render('createcase', '', 'plugins/Salesforce');
    }

    /**
     * Call writeAndUpdateAttachments()
     *
     * @param DiscussionController $sender
     * @param array $args
     */
    public function discussionController_afterDiscussionBody_handler($sender, $args) {
        $this->writeAndUpdateAttachments($sender, $args);
    }

    /**
     * Call writeAndUpdateAttachments()
     *
     * @param DiscussionController $sender
     * @param array $args
     */
    public function discussionController_afterCommentBody_handler($sender, $args) {
        $this->writeAndUpdateAttachments($sender, $args);
    }

    /**
     * Write update attachments
     *
     * @param DiscussionController $sender
     * @param array $args
     */
    protected function writeAndUpdateAttachments($sender, $args) {
        $type = val('Type', $args);

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
                if ($attachment['Type'] == 'salesforce-case') {
                    if ($attachment['ForeignUserID'] == $session->UserID) {
                        writeGenericAttachment([
                            'Icon' => 'ticket',
                            'Body' => wrap(t('A ticket has been generated from this post.'), 'p'),
                        ]);
                    } else {
                        writeGenericAttachment([
                            'Icon' => 'ticket',
                            'Body' => wrap(t('A ticket has been generated from this post.'), 'p')
                        ]);
                    }
                }
            }
            return;
        }

        if (!$session->checkPermission('Garden.Staff.Allow')) {
            return;
        }
        $salesforce = Salesforce::instance();

        if (isset($args[$content]->Attachments)) {
            if ($salesforce->isConnected()) {
                try {
                    $this->updateAttachments($args[$content]->Attachments);
                } catch (Gdn_UserException $e) {
                    $sender->informMessage('Error Reconnecting to Salesforce');
                }
            }
        }
    }

    /**
     * Update attachments
     *
     * @param array $attachments
     */
    protected function updateAttachments(&$attachments) {
        $salesforce = Salesforce::instance();
        $attachmentModel = new AttachmentModel();

        foreach ($attachments as &$attachment) {
            if ($attachment['Type'] == 'salesforce-case') {
                if (!$this->isToBeUpdated($attachment)) {
                    continue;
                }
                $caseResponse = $salesforce->getCase($attachment['SourceID']);
                $updatedAttachment = (array)$attachmentModel->getID($attachment['AttachmentID']);
                if ($caseResponse['HttpCode'] == 401) {
                    $salesforce->reconnect();
                    continue;
                } elseif ($caseResponse['HttpCode'] == 404) {
                    $updatedAttachment['DateUpdated'] = Gdn_Format::toDateTime();
                    $updatedAttachment['Error'] = t('Case has been deleted from Salesforce');
                    $attachmentModel->save($updatedAttachment);
                    $attachment = $updatedAttachment;
                    continue;
                } elseif ($caseResponse['HttpCode'] == 200) {
                    $case = $caseResponse['Response'];
                    $updatedAttachment['Status'] = $case['Status'];
                    $updatedAttachment['Priority'] = $case['Priority'];
                    $updatedAttachment['LastModifiedDate'] = $case['LastModifiedDate'];
                    $updatedAttachment['CaseNumber'] = $case['CaseNumber'];
                    $updatedAttachment['DateUpdated'] = Gdn_Format::toDateTime();
                    $attachmentModel->save($updatedAttachment);
                    $attachment = $updatedAttachment;
                }
            } elseif ($attachment['Type'] == 'salesforce-lead') {
                if (!$this->isToBeUpdated($attachment, $attachment['Type'])) {
                    continue;
                }

                $leadResponse = $salesforce->getLead($attachment['SourceID']);
                $updatedAttachment = (array)$attachmentModel->getID($attachment['AttachmentID']);

                if ($leadResponse['HttpCode'] == 401) {
                    $salesforce->reconnect();
                    continue;
                } elseif ($leadResponse['HttpCode'] == 404) {
                    $updatedAttachment['Error'] = t('Lead has been deleted from Salesforce');
                    $updatedAttachment['DateUpdated'] = Gdn_Format::toDateTime();
                    $attachmentModel->save($updatedAttachment);
                    $attachment = $updatedAttachment;
                    continue;
                } elseif ($leadResponse['HttpCode'] == 200) {
                    $lead = $leadResponse['Response'];
                    $updatedAttachment['Status'] = $lead['Status'];
                    $updatedAttachment['FirstName'] = $lead['FirstName'];
                    $updatedAttachment['LastName'] = $lead['LastName'];
                    $updatedAttachment['LastModifiedDate'] = $lead['LastModifiedDate'];
                    $updatedAttachment['Company'] = $lead['Company'];
                    $updatedAttachment['Title'] = $lead['Title'];
                    $updatedAttachment['DateUpdated'] = Gdn_Format::toDateTime();
                    $attachmentModel->save($updatedAttachment);
                    $attachment = $updatedAttachment;
                }
            }
        }
    }

    /**
     * Join attachments to user
     *
     * @param ProfileController $sender
     * @param array $args
     */
    public function profileController_render_before($sender, $args) {
        $attachmentModel = AttachmentModel::instance();
        $attachmentModel->joinAttachmentsToUser($sender, $args, ['Type' => 'salesforce-lead'], 1);
    }

    /**
     * Write lead attachment
     *
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
            require_once $Sender->fetchViewLocation('attachment', '', 'plugins/Salesforce');
        }
        foreach ($Attachments as $Attachment) {
            if ($Attachment['Type'] == 'salesforce-lead') {
                writeSalesforceLeadAttachment($Attachment);
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
     * Load CSS into header
     *
     * @param AssetModel $sender
     */
    public function assetModel_styleCss_handler($sender) {
        $sender->addCssFile('salesforce.css', 'plugins/Salesforce');
    }

    /**
     * Render Login Modal if staff triggers and action from popup and not connected to salesforce
     *
     * @param DiscussionController $sender
     * @return bool
     */
    public function loginModal($sender) {
        $loginUrl = url('/profile/connections');
        if (c('Plugins.Salesforce.DashboardConnection.Enabled', false)) {
            $loginUrl = url('/plugin/Salesforce');
        }
        $sender->setData('LoginURL', $loginUrl);
        $sender->render('reconnect', '', 'plugins/Salesforce');
    }

    /**
     * Check for attachment last update
     *
     * @param array $attachment Attachment Data - see AttachmentModel
     * @param string $type case or lead
     * @return bool
     */
    protected function isToBeUpdated($attachment, $type = 'salesforce-case') {
        if (val('Status', $attachment) == $this->closedCaseStatusString) {
            return false;
        }
        $timeDiff = time() - strtotime($attachment['DateUpdated']);
        if ($timeDiff < $this->minimumTimeForUpdate) {
            trace("Not Checking For Update: $timeDiff seconds since last update");
            return false;
        }
        if (isset($attachment['LastModifiedDate'])) {
            if ($type == 'salesforce-case') {
                $timeDiff = time() - strtotime($attachment['LastModifiedDate']);
                if ($timeDiff < $this->minimumTimeForUpdate && $attachment['Status'] != $this->closedCaseStatusString) {
                    trace("Not Checking For Update: $timeDiff seconds since last update");
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Add attachment views.
     *
     * @param DiscussionController $Sender Sending Controller.
     */
    public function discussionController_fetchAttachmentViews_handler($Sender) {
        require_once $Sender->fetchViewLocation('attachment', '', 'plugins/Salesforce');
    }

    /**
     * Check if Salesforce is configured
     *
     * @return bool
     */
    public function isConfigured() {
        $salesforce = Salesforce::instance();
        return $salesforce->isConfigured();
    }

    /**
     * Add SalesForce ID input
     *
     * @param SettingsController $sender
     */
    public function settingsController_beforeProfileExtenderAddEditRender_handler(\SettingsController $sender) {
        $data = [];
        $data["formLabel"] = $sender->Form->label('SalesForce ID', 'SalesForceID');
        $data["formInput"] = $sender->Form->textBox('SalesForceID', ['class' => 'form-control']);

        $content =  $this->renderTwig('/plugins/Salesforce/views/profileExtenderAddEdit.twig', $data);

        $sender->setData('beforeProfileExtenderCheckbox', $content);
    }

    /**
     * Create/Update contact on Salesforce
     *
     * @param \UserModel $sender
     * @param array $args
     */
    public function userModel_afterRegister_handler(\UserModel $sender, array $args) {
        //if  SyncUsers is enabled
        if (!c('Plugins.Salesforce.SyncUsers')) {
            return;
        }
        $this->syncUser($sender, $args['RegisteringUser']);
    }

    /**
     * Create/Update contact on Salesforce
     *
     * @param \UserModel $sender
     * @param array $args
     */
    public function userModel_afterSave_handler(\UserModel $sender, array $args) {
        //if  SyncUsers is enabled
        if (!c('Plugins.Salesforce.SyncUsers')) {
            return;
        }
        $this->syncUser($sender, $args['FormPostValues']);
    }

    /**
     * Sync user with contact in Salesforce
     *
     * @param \UserModel $sender
     * @param array $formFields
     */
    public function syncUser(\UserModel $sender, array $formFields) {
        $salesforce = Salesforce::instance();

        // reconnect salesforce in case it's not connected
        $salesforce->reconnect();

        // create contactData
        $contactData = $this->createContactData($formFields);

        $contact = $salesforce->findContact($formFields['Email']);
        $sender->EventArguments['ContactData'] = &$contactData;
        if (!$contact['Id']) {
            // add new contact
            $sender->fireEvent('CreateSalesforceContact');
            $salesforce->createContact($contactData);
        } else {
            // update contact
            $sender->fireEvent('UpdateSalesforceContact');
            $salesforce->updateContact($contactData, $contact['Id']);
        }
    }

    /**
     * Create contact data
     *
     * @param array $formFields
     * @return array
     */
    private function createContactData(array $formFields): array {
        $salesforceFields = $this->getContactFields();

        $contactData = [];
        //Salesforce does not accept field "Name", so unset
        unset($formFields["Name"]);

        // if there are fields from Salesforce, map fields
        if (count($salesforceFields) > 0) {
            foreach ($formFields as $field => $fieldValue) {
                if($fieldValue !== "") {
                    //field has SalesforceID ? (SalesforceID is used for custom fields)
                    $fieldID = c('ProfileExtender.Fields.'.$field.'.SalesForceID') ?
                        c('ProfileExtender.Fields.'.$field.'.SalesForceID') :
                        $field;
                    // field exists in Salesforce ?
                    if(isset($salesforceFields[$fieldID])) {
                        $contactData[$fieldID] = $fieldValue;
                    }
                }
            }
        //Otherwise, send as is
        } else {
            $contactData = $formFields;
        }

        //special case for "DateOfBirth"
        // Only send "DateFields" to salesforce if "SalesForceID" is set
        if(isset($formFields["DateFields"]) && !empty(c('ProfileExtender.Fields.DateOfBirth.SalesForceID'))) {
            $dateOfBirth = $this->validateDateOfBirth($formFields["DateOfBirth_Day"], $formFields["DateOfBirth_Month"], $formFields["DateOfBirth_Year"]);
            if($dateOfBirth) {
                $contactData["Birthdate"] = $dateOfBirth;
            }
        }
        return $contactData;
    }

    /**
     * Get Contact fields
     *
     * @return array
     */
    private function getContactFields(): array {
        $salesforce = Salesforce::instance();
        $salesforceFields = $salesforce->getFields('Contact');

        // if error fetching salesforce fields
        if (count($salesforceFields) == 0) {
            Logger::event(
                'salesforce_failure',
                Logger::ERROR,
                'Error getting Contact fields',
                $salesforceFields
            );
        }

        return $salesforceFields;
    }

    /**
     * Validate and sanitize DateOfBirth
     * @param string $day
     * @param string $month
     * @param string $year
     * @return bool|string
     */
    private function validateDateOfBirth(string $day, string $month, string $year) {
        $dateOfBirth = false;
        if($day !== "0" && $month !== "0" && $year !== "0" ) {
            $dateOfBirth = $year.'-'.$month.'-'.$day;
        }
        return $dateOfBirth;
    }
}
