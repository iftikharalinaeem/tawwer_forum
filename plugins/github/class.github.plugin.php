<?php
/**
 *
 * GitHub Plugin
 *
 * Changes:
 *  1.0      Initial release
 *
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

// Define the plugin:
$PluginInfo['github'] = [
    'Name' => 'GitHub',
    'Description' => 'Allow staff users to create issues from discussions and comments.',
    'Version' => '1.1.1',
    'RequiredApplications' => ['Vanilla' => '2.1.18'],
    'SettingsUrl' => '/plugin/github',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'MobileFriendly' => true,
    'Author' => 'John Ashton',
    'AuthorEmail' => 'john@vanillaforums.com',
    'AuthorUrl' => 'http://www.github.com/John0x00',
    'SocialConnect' => false,
    'Icon' => 'github.png',
];

/**
 * Github plugin.
 */
class GithubPlugin extends Gdn_Plugin {

    /**
     * @var Garden\Http\HttpClient Instance of HTTP client for API operations.
     */
    private $api;

    /**
     * @var string
     */
    protected $accessToken;

    protected $closedIssueString = 'closed';

    /**
     * If time since last update from Github is less then this; we wont check for update - saving api calls.
     *
     * @var int
     */
    protected $minimumTimeForUpdate = 600;

    const API_BASE_URL = 'https://api.github.com';

    const PROVIDER_KEY = 'github';

    const OAUTH_BASE_URL = 'https://github.com';

    //OAuth Methods

    /**
     * Set AccessToken to be used.
     *
     * @param string|bool $newToken A new access token.
     * @return string|null The value of the access token.
     */
    public function setAccessToken($newToken = false) {
        if ($newToken) {
            $this->accessToken = $newToken;
        } else {
            $userGithub = Gdn::session()->getAttribute(self::PROVIDER_KEY, []);
            $existingToken = val('AccessToken', $userGithub, c('Plugins.Github.GlobalLogin.AccessToken'));
            if ($existingToken) {
                $this->accessToken = $existingToken;
            }
        }

        return $this->accessToken;
    }

    /**
     * OAuth Method.  Gets the authorize Uri.
     *
     * @param bool|string $RedirectUri Redirect Url.
     *
     * @throws Gdn_UserException If Errors.
     * @return string Authorize URL Authorize Url.
     */
    public static function authorizeUri($RedirectUri = false) {
        if (!self::isConfigured()) {
            throw new Gdn_UserException('GitHub is not configured yet');
        }
        $AppID = c('Plugins.Github.ApplicationID');
        if (!$RedirectUri) {
            $RedirectUri = self::redirectUri();
        }
        $Query = [
            'redirect_uri' => $RedirectUri,
            'client_id' => $AppID,
            'response_type' => 'code',
            'scope' => 'repo',

        ];
        return self::OAUTH_BASE_URL.'/login/oauth/authorize?'.http_build_query($Query);
    }

    /**
     * Used in the OAuth Process.
     *
     * @param null|string $NewValue A different redirect url.
     *
     * @return string Redirect Url.
     */
    public static function redirectUri($NewValue = null) {
        if ($NewValue !== null) {
            $RedirectUri = $NewValue;
        } else {
            $RedirectUri = url('/profile/github', true);
        }
        return $RedirectUri;
    }

    /**
     * Grab the GitHub API access token.
     *
     * @return string|null The currently configured access token.
     */
    private function getAccessToken() {
        if ($this->accessToken === null) {
            $this->setAccessToken();
        }

        return $this->accessToken;
    }

    /**
     * OAuth Method.  Sends request to validate tokens.
     *
     * @param string $code OAuth Code.
     * @param string $redirectURI Redirect Uri.
     * @return array Response
     * @throws Gdn_UserException If error.
     */
    private function getTokens($code, $redirectURI) {
        if (!self::isConfigured()) {
            throw new Gdn_UserException('GitHub is not configured yet');
        }

        $response = $this->apiRequest(
            self::OAUTH_BASE_URL.'/login/oauth/access_token',
            [
                'client_id' => c('Plugins.Github.ApplicationID'),
                'client_secret' => c('Plugins.Github.Secret'),
                'code' => $code,
                'redirect_uri' => $redirectURI,
            ],
            false
        );

        if ($response) {
            parse_str($response, $tokens);
        } else {
            $tokens = [];
        }

        return $tokens;
    }

    /**
     * OAuth Method.
     *
     * @return string $Url
     */
    public static function profileConnectUrl() {
        return Gdn::request()->url('/profile/githubconnect', true);
    }

    /**
     * OAuth Method.
     *
     * @return bool
     */
    public static function isConfigured() {
        $AppID = c('Plugins.Github.ApplicationID');
        $Secret = c('Plugins.Github.Secret');
        if (!$AppID || !$Secret) {
            return false;
        }
        return true;
    }

    /**
     * OAuth Method.
     *
     * @return bool
     */
    public function isConnected() {
        return (bool)$this->getAccessToken();
    }

    /**
     * Profile Social Connections.
     *
     * @param Controller $Sender Sending controller.
     * @param array $Args Event arguments.
     */
    public function base_getConnections_handler($Sender, $Args) {
        if (!$this->isConfigured()) {
            return;
        }
        //Staff Only
        if (!Gdn::session()->checkPermission('Garden.Staff.Allow')) {
            return;
        }
        $Sf = getValueR('User.Attributes.'.self::PROVIDER_KEY, $Args);
        trace($Sf);
        $Profile = getValueR('User.Attributes.'.self::PROVIDER_KEY.'.Profile', $Args);
        $Sender->Data["Connections"][self::PROVIDER_KEY] = [
            'Icon' => $this->getWebResource('icon.png', '/'),
            'Name' => ucfirst(self::PROVIDER_KEY),
            'ProviderKey' => self::PROVIDER_KEY,
            'ConnectUrl' => self::authorizeUri(self::profileConnectUrl()),
            'Profile' => [
                'Name' => getValue('fullname', $Profile),
                'Photo' => getValue('photo', $Profile),
            ]
        ];
    }

    /**
     * OAUth Method.  Code is Exchanged for Token.
     *
     * Token is stored for later use.  Token does not expire.  It can be revoked from GitHub
     *
     * @param ProfileController $Sender Sending controller.
     * @param string $UserReference User Reference.
     * @param string $Username Username.
     * @param bool|string $Code Authorize Code.
     */
    public function profileController_githubConnect_create($Sender, $UserReference = '', $Username = '', $Code = false) {

        if (Gdn::request()->get('error')) {
            $Message = Gdn::request()->get('error_description');
            Gdn::dispatcher()->passData('Exception', htmlspecialchars($Message))
                ->dispatch('home/error');
            return;
        }

        if (stristr(Gdn::request()->url(), 'globallogin') !== false) {
            redirect(url('/plugin/github/connect?code='.Gdn::request()->get('code')));
        }
        $Sender->permission('Garden.SignIn.Allow');
        $Sender->getUserInfo($UserReference, $Username, '', true);
        $Sender->_SetBreadcrumbs(t('Connections'), userUrl($Sender->User, '', 'connections'));

        try {
            $Tokens = $this->getTokens($Code, self::profileConnectUrl());
        } catch (Gdn_UserException $e) {
            $Attributes = [
                'AccessToken' => null,
                'Profile' => null,
            ];
            Gdn::userModel()->saveAttribute($Sender->User->UserID, self::PROVIDER_KEY, $Attributes);
            $Message = $e->getMessage();
            Gdn::dispatcher()->passData('Exception', htmlspecialchars($Message))
                ->dispatch('home/error');
            return;
        }
        $AccessToken = getValue('access_token', $Tokens);
        $this->setAccessToken($AccessToken);
        $profile = $this->getProfile();

        Gdn::userModel()->saveAuthentication(
            [
                'UserID' => $Sender->User->UserID,
                'Provider' => self::PROVIDER_KEY,
                'UniqueID' => $profile['id'],
            ]
        );
        $Attributes = [
            'AccessToken' => $AccessToken,
            'Profile' => $profile,
        ];
        Gdn::userModel()->saveAttribute($Sender->User->UserID, self::PROVIDER_KEY, $Attributes);
        $this->EventArguments['Provider'] = self::PROVIDER_KEY;
        $this->EventArguments['User'] = $Sender->User;
        $this->fireEvent('AfterConnection');

        redirect(userUrl($Sender->User, '', 'connections'));
    }

    /**
     * OAuth Method. Redirects user to request access.
     */
    public function controller_authorize() {
        redirect(self::authorizeUri(self::globalConnectUrl()));
    }

    /**
     * OAuth Method. Handles the redirect from GitHub and stores AccessToken.
     *
     * @throws Gdn_UserException If Error.
     */
    public function controller_connect() {
        $Code = Gdn::request()->get('code');
        $Tokens = $this->getTokens($Code, self::globalConnectUrl());
        $AccessToken = getValue('access_token', $Tokens);

        if ($AccessToken) {
            $this->setAccessToken($AccessToken);
            saveToConfig(
                [
                    'Plugins.Github.GlobalLogin.Enabled' => true,
                    'Plugins.Github.GlobalLogin.AccessToken' => $AccessToken,
                ]
            );
        } else {
            removeFromConfig(
                [
                    'Plugins.Github.GlobalLogin.Enabled' => true,
                    'Plugins.Github.GlobalLogin.AccessToken' => $AccessToken,
                ]
            );
            throw new Gdn_UserException('Error Connecting to GitHub');
        }
        redirect(url('/plugin/github'));

    }

    /**
     * OAuth Method.
     *
     * @return string
     */
    public static function globalConnectUrl() {
        return Gdn::request()->url('/profile/githubconnect/globallogin/', true);
    }

    /**
     * Enable/Disable Global Login.
     *
     * @param Controller $Sender Sending controller.
     */
    public function controller_toggle($Sender) {
        $Sender->permission('Garden.Settings.Manage');
        // Enable/Disable
        if (Gdn::session()->validateTransientKey(getValue(1, $Sender->RequestArgs))) {
            if (c('Plugins.Github.GlobalLogin.Enabled')) {
                removeFromConfig('Plugins.Github.GlobalLogin.Enabled');
                removeFromConfig('Plugins.Github.GlobalLogin.AccessToken');
                redirect(url('/plugin/github'));
            }
            redirect(url('/plugin/github/authorize'));

        }
    }

    //end of OAUTH

    /**
     * Setup the plugin.
     */
    public function setup() {

        saveToConfig('Garden.AttachmentsEnabled', true);
        // Save the provider type.
        Gdn::sql()->replace(
            'UserAuthenticationProvider',
            [
                'AuthenticationSchemeAlias' => 'github',
                'URL' => '...',
                'AssociationSecret' => '...',
                'AssociationHashMethod' => '...',
            ],
            ['AuthenticationKey' => self::PROVIDER_KEY],
            true
        );
        Gdn::permissionModel()->define(['Garden.Staff.Allow' => 'Garden.Moderation.Manage']);
        $this->setupConfig();
    }

    /**
     * Perform appropriate database structure updates.
     */
    public function structure() {
        // Correct invalid casing.
        $provider = Gdn_AuthenticationProviderModel::getProviderByKey(self::PROVIDER_KEY);

        if ($provider['AuthenticationKey'] !== self::PROVIDER_KEY) {
            $provider['AuthenticationKey'] = self::PROVIDER_KEY;
            $model = new Gdn_AuthenticationProviderModel();
            $model->save($provider);
        }
    }

    /**
     * Setup Config Settings.
     */
    protected function setupConfig() {
        $ConfigSettings = [
            'Url',
            'ApplicationID',
            'Secret',
        ];
        //prevents resetting any previous values
        foreach ($ConfigSettings as $ConfigSetting) {
            if (!C('Plugins.Github.'.$ConfigSetting)) {
                saveToConfig('Plugins.Github.'.$ConfigSetting, '');
            }
        }
    }

    /**
     * Writes and updates discussion attachments.
     *
     * @param DiscussionController $Sender Sending controller.
     * @param array $Args Event Arguments.
     */
    public function discussionController_afterDiscussionBody_handler($Sender, $Args) {
        $this->updateAttachments($Sender, $Args);
    }

    /**
     * Writes and updates discussion attachments.
     *
     * @param DiscussionController $Sender Sending controller.
     * @param array $Args Event Arguments.
     */
    public function discussionController_afterCommentBody_handler($Sender, $Args) {
        $this->updateAttachments($Sender, $Args);
    }

    /**
     * Writes and updates attachments for comments and discussions.
     *
     * @param DiscussionController|Commentocntroller $Sender Sending controller.
     * @param array $Args Event arguments.
     *
     * @throws Gdn_UserException If Errors.
     */
    protected function updateAttachments($Sender, $Args) {
        if ($Args['Type'] == 'Discussion') {
            $Content = 'Discussion';
        } elseif ($Args['Type'] == 'Comment') {
            $Content = 'Comment';
        } else {
            // Invalid Content
            return;
        }
        // Signed in users only.
        if (!Gdn::session()->isValid()) {
            return;
        }

        if (!Gdn::session()->checkPermission('Garden.Staff.Allow')) {
            return;
        }
        $Attachments = getValue('Attachments', $Args[$Content]);
        if ($Attachments) {
            foreach ($Args[$Content]->Attachments as $Attachment) {
                if ($Attachment['Type'] == 'github-issue') {
                    $this->updateAttachment($Attachment);
                }
            }
        }

    }

    /**
     * Check to see if attachment needs to be updated.
     *
     * @param array $Attachment Attachment Data - see AttachmentModel.
     *
     * @see    AttachmentModel
     *
     * @return bool
     */
    protected function isToBeUpdated($Attachment) {
        if (getValue('State', $Attachment) == $this->closedIssueString) {
            trace("Issue {$this->closedIssueString}.  Not checking for update.");
            return false;
        }
        $TimeDiff = time() - strtotime($Attachment['DateUpdated']);
        if ($TimeDiff < $this->minimumTimeForUpdate) {
            trace("Not Checking For Update: $TimeDiff seconds since last update");
            return false;
        }
        if (isset($Attachment['LastModifiedDate'])) {
            $TimeDiff = time() - strtotime($Attachment['LastModifiedDate']);
            if ($TimeDiff < $this->minimumTimeForUpdate) {
                trace("Not Checking For Update: $TimeDiff seconds since last update");
                return false;
            }
        }
        return true;
    }

    /**
     * Update the Attachment.
     *
     * @param array $Attachment Attachment.
     *
     * @see    AttachmentModel
     *
     * @return bool
     */
    protected function updateAttachment($Attachment) {
        if (!$this->isConfigured()) {
            return;
        }

        if ($this->isToBeUpdated($Attachment)) {
            $issue = $this->getIssue($Attachment['Repository'], $Attachment['SourceID']);

            if (!$issue) {
                trace(
                    'getIssue returned false for: '.$Attachment['Repository'].' Issue: '.$Attachment['SourceID']
                );
                return false;
            }
            $Attachment['State'] = $issue['state'];
            $Attachment['Assignee'] = $issue['assignee'];
            $Attachment['Milestone'] = $issue['milestone'];
            $Attachment['LastModifiedDate'] = $issue['updated_at'];
            $Attachment['DateUpdated'] = Gdn_Format::toDateTime();
            $Attachment['ClosedBy'] = $issue['closed_by']['login'];

            $AttachmentModel = AttachmentModel::instance();
            $AttachmentModel->save($Attachment);
            return true;
        }
        return false;
    }

    /**
     * Add attachment views.
     *
     * @param DiscussionController $Sender Sending Controller.
     */
    public function discussionController_fetchAttachmentViews_handler($Sender) {
        require_once $Sender->fetchViewLocation('attachment', '', 'plugins/github');
    }

    /**
     * Creates the Virtual GitHub Controller and adds Link to SideMenu in the dashboard.
     *
     * @param PluginController $Sender Sending controller.
     */
    public function pluginController_github_create($Sender) {

        $Sender->permission('Garden.Staff.Allow');
        $Sender->title('GitHub');
        $Sender->addSideMenu('plugin/github');
        $Sender->Form = new Gdn_Form();
        $this->dispatch($Sender, $Sender->RequestArgs);
    }

    /**
     * Dashboard Settings.
     *
     * Default method of virtual controller.
     *
     * @param pluginController $Sender Sending controller.
     */
    public function controller_index($Sender) {

        $Sender->permission('Garden.Settings.Manage');
        $Sender->addCssFile('admin.css');

        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->setField(['ApplicationID', 'Secret']);

        // Set the model on the form.
        $Sender->Form->setModel($ConfigurationModel);

        // If seeing the form for the first time...
        if ($Sender->Form->authenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $Sender->Form->setData($ConfigurationModel->Data);
        } else {
            $FormValues = $Sender->Form->formValues();
            if ($Sender->Form->isPostBack()) {
                $Sender->Form->validateRule(
                    'ApplicationID',
                    'function:ValidateRequired',
                    'Unique Identifier is required'
                );

                //repo validation.
                $repos = explode("\n", trim($FormValues['Repositories']));
                if ($repos[0] == '') {
                    $Sender->Form->addError('Please enter a valid repo name', 'Repositories');
                } else {
                    foreach ($repos as $repo) {
                        if (!$this->isValidRepoName($repo)) {
                            $Sender->Form->addError('Invalid Repository: '.$repo, 'Repositories');
                        }
                    }
                }


                $Sender->Form->validateRule('Secret', 'function:ValidateRequired', 'Secret is required');


                if ($Sender->Form->errorCount() == 0) {
                    foreach ($repos as $repo) {
                        saveToConfig('Plugins.Github.Repos.'.trim($repo), true);
                    }
                    saveToConfig('Plugins.Github.ApplicationID', trim($FormValues['ApplicationID']));
                    saveToConfig('Plugins.Github.Secret', trim($FormValues['Secret']));
                    $Sender->informMessage(t("Your changes have been saved."));
                } else {
                    $Sender->informMessage(t("Error saving settings to config."));
                }
            }

        }

        $Sender->Form->setValue('ApplicationID', c('Plugins.Github.ApplicationID'));
        $Sender->Form->setValue('Secret', c('Plugins.Github.Secret'));

        $Repositories = c('Plugins.Github.Repos', []);
        $ReposForForm = '';
        foreach (array_keys($Repositories) as $Repo) {
            $ReposForForm .= $Repo."\n";
        }
        $ReposForForm = trim($ReposForForm);
        $Sender->Form->setValue('Repositories', $ReposForForm);


        $Sender->setData([
            'GlobalLoginEnabled' => c('Plugins.Github.GlobalLogin.Enabled'),
            'GlobalLoginConnected' => c('Plugins.Github.GlobalLogin.AccessToken'),
            'ToggleUrl' => url('/plugin/github/toggle/'.Gdn::session()->transientKey()),
        ]);
        if (c('Plugins.Github.GlobalLogin.Enabled')) {
            $globalLoginProfile = $this->getProfile();
            $Sender->setData('GlobalLoginProfile', $globalLoginProfile);
        }

        $Sender->render($this->getView('dashboard.php'));
    }

    /**
     * Popup to Add Issue.
     *
     * @param DiscussionController $Sender Sending controller.
     * @param array $Args Sender Arguments.
     *
     * @throws Gdn_UserException Permission Denied.
     * @throws Exception Permission Denied.
     */
    public function discussionController_githubIssue_create($Sender, $Args) {
        if (!$this->isConnected()) {
            $Sender->setData('LoginURL', url('/profile/connections'));
            $Sender->render('reconnect', '', 'plugins/github');
            return;
        }

        // Signed in users only.
        if (!(Gdn::session()->isValid())) {
            throw permissionException('Garden.Signin.Allow');
        }
        //Permissions
        $Sender->permission('Garden.Staff.Allow');

        //get arguments
        if (count($Sender->RequestArgs) != 3) {
            throw new Gdn_UserException('Bad Request', 400);
        }
        list($context, $contextID, $userId) = $Sender->RequestArgs;

        // Get Content
        if ($context == 'discussion') {
            $Content = $Sender->DiscussionModel->getID($contextID);
            $Url = discussionUrl($Content, 1);
            $Title = $Content->Name;
        } elseif ($context == 'comment') {
            $CommentModel = new CommentModel();
            $Content = $CommentModel->getID($contextID);
            $Discussion = $Sender->DiscussionModel->getID($Content->DiscussionID);
            $Url = commentUrl($Content);
            $Title = $Discussion->Name;

        } else {
            throw new Gdn_UserException('Content Type not supported');
        }

        $Repositories = c('Plugins.Github.Repos', []);
        $RepositoryOptions = '';
        foreach (array_keys($Repositories) as $Repo) {
            $RepositoryOptions .= '<option>'.Gdn_Format::text($Repo).'</option>';
        }

        // If form is being submitted
        if ($Sender->Form->isPostBack() && $Sender->Form->authenticatedPostBack() === true) {
            // Form Validation
            $Sender->Form->validateRule('Title', 'function:ValidateRequired', 'Title is required');
            $Sender->Form->validateRule('Body', 'function:ValidateRequired', 'Body is required');
            $Sender->Form->validateRule('Repository', 'function:ValidateRequired', 'Repository is required');
            // If no errors
            if ($Sender->Form->errorCount() == 0) {
                $FormValues = $Sender->Form->formValues();

                $bodyAppend = "\n\n".t("This Issue was generated from your [forums] \n");
                $bodyAppend .= "[forums]: $Url\n";

                $issue = $this->createIssue(
                    $FormValues['Repository'],
                    [
                        'title' => $FormValues['Title'],
                        'body' => $FormValues['Body'].$bodyAppend,
                        'labels' => ['Vanilla'],
                    ]
                );
                if ($issue != false) {
                    $AttachmentModel = AttachmentModel::instance();
                    $AttachmentModel->save([
                        'Type' => 'github-issue',
                        'ForeignID' => AttachmentModel::rowID($Content),
                        'ForeignUserID' => $Content->InsertUserID,
                        'Source' => 'github',
                        'SourceID' => $issue['number'],
                        'SourceURL' => 'https://github.com/'.$FormValues['Repository'].'/issues/'.$issue['number'],
                        'LastModifiedDate' => Gdn_Format::toDateTime(),
                        'State' => $issue['state'],
                        'Assignee' => $issue['assignee'],
                        'MileStone' => $issue['milestone'],
                        'Repository' => $FormValues['Repository'],
                    ]);


                    $Sender->jsonTarget('', $Url, 'Redirect');
                    $Sender->informMessage(t('GitHub Issue created'));
                } else {
                    $Sender->informMessage(t('Error creating GitHub Issue'));
                }

            }
        }

        $Data = [
            'RepositoryOptions' => $RepositoryOptions,
            'Body' => $this->convertToMDCompatible($Content->Body, $Content->Format),
            'Title' => $Title,
        ];
        $Sender->setData($Data);
        $Sender->Form->setData($Data);


        $Sender->render('createissue', '', 'plugins/github');


    }

    /**
     * Add option to create issue to Cog.
     *
     * @param DiscussionController $Sender Sending controller.
     * @param array $Args Sender Arguments.
     *
     * @todo remove option if issue has been created.
     */
    public function discussionController_discussionOptions_handler($Sender, $Args) {
        // Staff Only
        $Session = Gdn::session();
        if (!$Session->checkPermission('Garden.Staff.Allow')) {
            return;
        }
        $UserID = $Args['Discussion']->InsertUserID;
        $DiscussionID = $Args['Discussion']->DiscussionID;

        // Don not add option if attachment already created.
        $Attachments = getValue('Attachments', $Args['Discussion'], []);
        foreach ($Attachments as $Attachment) {
            if ($Attachment['Type'] == 'github-issue') {
                return;
            }
        }

        if (isset($Args['DiscussionOptions'])) {
            $Args['DiscussionOptions']['GithubIssue'] = [
                'Label' => t('GitHub - Create Issue'),
                'Url' => "/discussion/githubissue/discussion/$DiscussionID/$UserID",
                'Class' => 'Popup',
            ];
        }

    }

    /**
     * Add option to create issue to Cog.
     *
     * @param DiscussionController $Sender Sending controller.
     * @param array $Args Sender Arguments.
     *
     * @todo remove option if issue has been created.
     */
    public function discussionController_commentOptions_handler($Sender, $Args) {
        //Staff Only
        $Session = Gdn::session();
        if (!$Session->checkPermission('Garden.Staff.Allow')) {
            return;
        }
        $UserID = $Args['Comment']->InsertUserID;
        $CommentID = $Args['Comment']->CommentID;
        // Don not add option if attachment already created.
        $Attachments = getValue('Attachments', $Args['Comment'], []);
        foreach ($Attachments as $Attachment) {
            if ($Attachment['Type'] == 'github-issue') {
                return;
            }
        }
        if (isset($Args['CommentOptions'])) {
            $Args['CommentOptions']['GithubIssue'] = [
                'Label' => t('GitHub - Create Issue'),
                'Url' => "/discussion/githubissue/comment/$CommentID/$UserID",
                'Class' => 'Popup',
            ];
        }
    }

    //API Calls

    /**
     * Make request to the API.
     *
     * @param string $endPoint Path of the API endpoint.  ie: /users.
     * @param null|array $post Post values.
     *
     * @todo add cache for GET
     *
     * @return string JSON response from GitHub.
     */
    public function apiRequest($endPoint, $post = null, $authenticate = true) {
        $api = $this->getAPI();
        $additionalHeaders = [];

        if ($authenticate) {
            $additionalHeaders['Authorization'] = 'token '.$this->getAccessToken();
        }

        try {
            if ($post === null) {
                $response = $api->get($endPoint, [], $additionalHeaders);
            } else {
                $response = $api->post($endPoint, $post, $additionalHeaders);
            }
        } catch (\Exception $e) {
            Logger::log(Logger::ERROR, 'github_error', [
                'endpoint' => $endPoint,
                'errorCode' => $e->getCode(),
                'errorMessage' => $e->getMessage(),
                'post' => $post
            ]);

            return false;
        }

        $responseBody = $response->getBody();
        Logger::log(Logger::DEBUG, 'github_api', [
            'endpoint' => $endPoint,
            'post' => $post,
            'response' => $responseBody
        ]);
        return $responseBody;
    }

    /**
     * Grab the current instance of the API client.
     *
     * @return \Garden\Http\HttpClient The HTTP interface for the API client.
     */
    private function getAPI() {
        if ($this->api === null) {
            $this->api = new \Garden\Http\HttpClient(self::API_BASE_URL);
            $this->api->setThrowExceptions(true);
            $this->api->setDefaultHeader('Content-Type', 'application/json');
        }

        return $this->api;
    }

    /**
     * Get Profile of current authenticated user.
     */
    public function getProfile() {
        $fullProfile = $this->apiRequest('/user');
        return [
            'id' => $fullProfile['id'],
            'fullname' => $fullProfile['name'],
            'photo' => $fullProfile['avatar_url'],
        ];
    }

    /**
     * Create an Issue using the github API.
     *
     * @param string $repo Full repo name.  Example John/MyRepo.
     * @param array $issue Issue details
     *    * [title]     - string
     *    * [body]      - string
     *      [assignee]  - string
     *      [milestone] - string
     *      [labels]    - array
     * Keys prefixed with a * are required.
     *
     * @throws Gdn_UserException
     * @link https://developer.github.com/v3/repos/#create
     *
     * @return array|bool
     */
    protected function createIssue($repo, $issue) {
        $response = $this->apiRequest('/repos/'.$repo.'/issues', $issue);

        if (val('message', $response)) {
            throw new Gdn_UserException('Error creating issue: '.$response['message']);
        }

        return val('id', $response) ? $response : false;
    }

    /**
     * Get issue details.
     *
     * @param string $repo Repository Name.
     * @param int $issueNumber Issue Number.
     *
     * @link https://developer.github.com/v3/issues/#get-a-single-issue
     * @throws Gdn_UserException Invalid Input.
     *
     * @return array Issue Details.
     */
    protected function getIssue($repo, $issueNumber) {
        ///repos/:owner/:repo/issues/:number
        if (!$this->isValidRepoName($repo)) {
            throw new Gdn_UserException('Invalid repository name: '.$repo);
        }
        if (!is_numeric($issueNumber)) {
            throw new Gdn_UserException('Invalid issue number: '.$issueNumber);
        }
        $issue = $this->apiRequest('/repos/'.$repo.'/issues/'.$issueNumber);
        if (!GetValue('id', $issue)) {
            return false;
        }
        return $issue;
    }

    /**
     * Check to see if repo provided exists on github.com.
     *
     * @param string $repo Full repo name. ie: John/MyRepo.
     *
     * @return bool
     * @throws Gdn_UserException If repo name is invalid format.
     */
    public function isValidRepo($repo) {

        if (!$this->isValidRepoName($repo)) {
            throw new Gdn_UserException('Invalid repo name: '.$repo);
        }
        $response = $this->apiRequest('/repos/'.$repo);
        if (getValue('id', $response, 0) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Checks format of repo name provided.
     *
     * @param string $repo Repository Name.
     *
     * @return bool
     */
    protected function isValidRepoName($repo) {
        if (substr_count($repo, '/') != 1) {
            return false;
        }
        return true;
    }

    //End of API Calls

    /**
     * Test controller.
     *
     * @param PlugginController $Sender Sending controller.
     */
    public function controller_test($Sender) {
        $Sender->permission('Garden.Settings.Manage');

        $args = $Sender->RequestArgs;
        if (count($args) == 1) {
            ?>
            <ul>
                <li><a href="test/createIssue">createIssue</a></li>
                <li><a href="test/isValidRepo">isValidRepo</a></li>
                <li><a href="test/getReposFromConfig">getReposFromConfig</a></li>
            </ul>
            <?php
            return;
        }
        $test = $args[1];

        switch ($test) {

            case 'getReposFromConfig':
                $repos = array_keys(c('Plugins.Github.Repos', []));
                var_dump('Repos from config: ');
                var_dump($repos);

                break;
            case 'isValidRepo':

                $repos = [
                    'John0x00/test' => true,
                    'John0x00/VanillaPlugins' => true,
                ];
                foreach (array_keys($repos) as $repo) {
                    echo "Repo $repo =====> ".var_export($this->isValidRepo($repo), true).'<br/>';
                }
                break;

            case 'createIssue':

                $issue = $this->createIssue(
                    'John0x00/VanillaPlugins',
                    [
                        'title' => 'title',
                        'body' => 'body',
//                'assignee' => '',
//                'milestone' => '',
//                'labels' => ['label1', 'label2')
                    ]
                );

                if (getValue('errors', $issue)) {
                    $errorMessage = '';
                    var_dump($issue['errors']);
                    foreach ($issue['errors'] as $error) {
                        $errorMessage .= $error['code'].' '.$error['field']."<br/>";
                    }
                    var_dump("Failed creating issue: \n".$errorMessage);
                } else {
                    $issueID = $issue['id'];
                    var_dump("Issue created: $issueID");
                    var_dump($issue);
                }

                break;

            default:
                echo 'Test not configured';

        }

    }

    /**
     * Used to convert text to mark down accepted by GitHub.
     *
     * @param string $Text Text to be converted.
     * @param string $Format Format of text.
     * @return string MD Compatible text.
     */
    public function convertToMDCompatible($Text, $Format = 'Html') {
        switch ($Format) {
            case 'Markdown':
                return $Text;
                break;
            default:
                return Gdn_Format::text($Text, false);
        }
    }


}
