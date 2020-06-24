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
     * @param bool|string $redirectUri Redirect Url.
     *
     * @throws Gdn_UserException If Errors.
     * @return string Authorize URL Authorize Url.
     */
    public function authorizeUri($redirectUri = false) {
        if (!self::isConfigured()) {
            throw new Gdn_UserException('GitHub is not configured yet');
        }

        $appID = c('Plugins.Github.ApplicationID');
        if (!$redirectUri) {
            $redirectUri = self::redirectUri();
        }

        // Get a state token.
        $stateToken = $this->ssoUtils->getStateToken();

        $query = [
            'redirect_uri' => $redirectUri,
            'client_id' => $appID,
            'response_type' => 'code',
            'scope' => 'repo',
            'state' => json_encode(['token' => $stateToken]),

        ];
        return self::OAUTH_BASE_URL.'/login/oauth/authorize?'.http_build_query($query);
    }

    /**
     * Used in the OAuth Process.
     *
     * @param null|string $newValue A different redirect url.
     *
     * @return string Redirect Url.
     */
    public static function redirectUri($newValue = null) {
        if ($newValue !== null) {
            $redirectUri = $newValue;
        } else {
            $redirectUri = url('/profile/github', true);
        }
        return $redirectUri;
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
        $appID = c('Plugins.Github.ApplicationID');
        $secret = c('Plugins.Github.Secret');
        if (!$appID || !$secret) {
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
     * @param Controller $sender Sending controller.
     * @param array $args Event arguments.
     */
    public function base_getConnections_handler($sender, $args) {
        if (!$this->isConfigured()) {
            return;
        }
        //Staff Only
        if (!Gdn::session()->checkPermission('Garden.Staff.Allow')) {
            return;
        }
        $sf = getValueR('User.Attributes.'.self::PROVIDER_KEY, $args);
        trace($sf);
        $profile = getValueR('User.Attributes.'.self::PROVIDER_KEY.'.Profile', $args);
        $sender->Data["Connections"][self::PROVIDER_KEY] = [
            'Icon' => $this->getWebResource('icon.png', '/'),
            'Name' => ucfirst(self::PROVIDER_KEY),
            'ProviderKey' => self::PROVIDER_KEY,
            'ConnectUrl' => $this->authorizeUri(self::profileConnectUrl()),
            'Profile' => [
                'Name' => val('fullname', $profile),
                'Photo' => val('photo', $profile),
            ]
        ];
    }

    /**
     * OAUth Method.  Code is Exchanged for Token.
     *
     * Token is stored for later use.  Token does not expire.  It can be revoked from GitHub
     *
     * @param ProfileController $sender Sending controller.
     * @param string $userReference User Reference.
     * @param string $username Username.
     * @param bool|string $code Authorize Code.
     */
    public function profileController_githubConnect_create($sender, $userReference = '', $username = '', $code = false) {
        $sender->permission('Garden.SignIn.Allow');

        $state = json_decode(Gdn::request()->get('state', ''), true);
        $suppliedStateToken = val('token', $state);
        $this->ssoUtils->verifyStateToken('github', $suppliedStateToken);

        if (Gdn::request()->get('error')) {
            $message = Gdn::request()->get('error_description');
            Gdn::dispatcher()->passData('Exception', htmlspecialchars($message))
                ->dispatch('home/error');
            return;
        }

        if (stristr(Gdn::request()->url(), 'globallogin') !== false) {
            redirectTo('/plugin/github/connect?code='.Gdn::request()->get('code'));
        }

        $sender->getUserInfo($userReference, $username, '', true);
        $sender->_SetBreadcrumbs(t('Connections'), userUrl($sender->User, '', 'connections'));

        try {
            $tokens = $this->getTokens($code, self::profileConnectUrl());
        } catch (Gdn_UserException $e) {
            $attributes = [
                'AccessToken' => null,
                'Profile' => null,
            ];
            Gdn::userModel()->saveAttribute($sender->User->UserID, self::PROVIDER_KEY, $attributes);
            $message = $e->getMessage();
            Gdn::dispatcher()->passData('Exception', htmlspecialchars($message))
                ->dispatch('home/error');
            return;
        }
        $accessToken = val('access_token', $tokens);
        $this->setAccessToken($accessToken);
        $profile = $this->getProfile();

        Gdn::userModel()->saveAuthentication(
            [
                'UserID' => $sender->User->UserID,
                'Provider' => self::PROVIDER_KEY,
                'UniqueID' => $profile['id'],
            ]
        );
        $attributes = [
            'AccessToken' => $accessToken,
            'Profile' => $profile,
        ];
        Gdn::userModel()->saveAttribute($sender->User->UserID, self::PROVIDER_KEY, $attributes);
        $this->EventArguments['Provider'] = self::PROVIDER_KEY;
        $this->EventArguments['User'] = $sender->User;
        $this->fireEvent('AfterConnection');

        redirectTo(userUrl($sender->User, '', 'connections'));
    }

    /**
     * OAuth Method. Redirects user to request access.
     */
    public function controller_authorize() {
        redirectTo($this->authorizeUri(self::globalConnectUrl()), 302, false);
    }

    /**
     * OAuth Method. Handles the redirect from GitHub and stores AccessToken.
     *
     * @throws Gdn_UserException If Error.
     */
    public function controller_connect() {
        $code = Gdn::request()->get('code');
        $tokens = $this->getTokens($code, self::globalConnectUrl());
        $accessToken = val('access_token', $tokens);

        if ($accessToken) {
            $this->setAccessToken($accessToken);
            saveToConfig(
                [
                    'Plugins.Github.GlobalLogin.Enabled' => true,
                    'Plugins.Github.GlobalLogin.AccessToken' => $accessToken,
                ]
            );
        } else {
            removeFromConfig(
                [
                    'Plugins.Github.GlobalLogin.Enabled' => true,
                    'Plugins.Github.GlobalLogin.AccessToken' => $accessToken,
                ]
            );
            throw new Gdn_UserException('Error Connecting to GitHub');
        }
        redirectTo('/plugin/github');

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
     * @param Controller $sender Sending controller.
     */
    public function controller_toggle($sender) {
        $sender->permission('Garden.Settings.Manage');
        // Enable/Disable
        if (Gdn::session()->validateTransientKey(val(1, $sender->RequestArgs))) {
            if (c('Plugins.Github.GlobalLogin.Enabled')) {
                removeFromConfig('Plugins.Github.GlobalLogin.Enabled');
                removeFromConfig('Plugins.Github.GlobalLogin.AccessToken');
                redirectTo('/plugin/github');
            }
            redirectTo('/plugin/github/authorize');

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
        $configSettings = [
            'Url',
            'ApplicationID',
            'Secret',
        ];
        //prevents resetting any previous values
        foreach ($configSettings as $configSetting) {
            if (!c('Plugins.Github.'.$configSetting)) {
                saveToConfig('Plugins.Github.'.$configSetting, '');
            }
        }
    }

    /**
     * Writes and updates discussion attachments.
     *
     * @param DiscussionController $sender Sending controller.
     * @param array $args Event Arguments.
     */
    public function discussionController_afterDiscussionBody_handler($sender, $args) {
        $this->updateAttachments($sender, $args);
    }

    /**
     * Writes and updates discussion attachments.
     *
     * @param DiscussionController $sender Sending controller.
     * @param array $args Event Arguments.
     */
    public function discussionController_afterCommentBody_handler($sender, $args) {
        $this->updateAttachments($sender, $args);
    }

    /**
     * Writes and updates attachments for comments and discussions.
     *
     * @param DiscussionController|Commentocntroller $sender Sending controller.
     * @param array $args Event arguments.
     *
     * @throws Gdn_UserException If Errors.
     */
    protected function updateAttachments($sender, $args) {
        if ($args['Type'] == 'Discussion') {
            $content = 'Discussion';
        } elseif ($args['Type'] == 'Comment') {
            $content = 'Comment';
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
        $attachments = val('Attachments', $args[$content]);
        if ($attachments) {
            foreach ($args[$content]->Attachments as $attachment) {
                if ($attachment['Type'] == 'github-issue') {
                    $this->updateAttachment($attachment);
                }
            }
        }

    }

    /**
     * Check to see if attachment needs to be updated.
     *
     * @param array $attachment Attachment Data - see AttachmentModel.
     *
     * @see    AttachmentModel
     *
     * @return bool
     */
    protected function isToBeUpdated($attachment) {
        if (val('State', $attachment) == $this->closedIssueString) {
            trace("Issue {$this->closedIssueString}.  Not checking for update.");
            return false;
        }
        $timeDiff = time() - strtotime($attachment['DateUpdated']);
        if ($timeDiff < $this->minimumTimeForUpdate) {
            trace("Not Checking For Update: $timeDiff seconds since last update");
            return false;
        }
        if (isset($attachment['LastModifiedDate'])) {
            $timeDiff = time() - strtotime($attachment['LastModifiedDate']);
            if ($timeDiff < $this->minimumTimeForUpdate) {
                trace("Not Checking For Update: $timeDiff seconds since last update");
                return false;
            }
        }
        return true;
    }

    /**
     * Update the Attachment.
     *
     * @param array $attachment Attachment.
     *
     * @see    AttachmentModel
     *
     * @return bool
     */
    protected function updateAttachment($attachment) {
        if (!$this->isConfigured()) {
            return;
        }

        if ($this->isToBeUpdated($attachment)) {
            $issue = $this->getIssue($attachment['Repository'], $attachment['SourceID']);

            if (!$issue) {
                trace(
                    'getIssue returned false for: '.$attachment['Repository'].' Issue: '.$attachment['SourceID']
                );
                return false;
            }
            $attachment['State'] = $issue['state'];
            $attachment['Assignee'] = $issue['assignee'];
            $attachment['Milestone'] = $issue['milestone'];
            $attachment['LastModifiedDate'] = $issue['updated_at'];
            $attachment['DateUpdated'] = Gdn_Format::toDateTime();
            $attachment['ClosedBy'] = $issue['closed_by']['login'];

            $attachmentModel = AttachmentModel::instance();
            $attachmentModel->save($attachment);
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
     * @param PluginController $sender Sending controller.
     */
    public function pluginController_github_create($sender) {

        $sender->permission('Garden.Staff.Allow');
        $sender->title('GitHub');
        $sender->addSideMenu('plugin/github');
        $sender->Form = new Gdn_Form();
        $this->dispatch($sender, $sender->RequestArgs);
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
                        saveToConfig('Plugins.Github.Repos.'.str_replace('.', '-{dot}-', trim($repo)), true);
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

        $Repositories = $this->getRepositories();
        $ReposForForm = '';
        foreach ($Repositories as $Repo) {
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
     * @param DiscussionController $sender Sending controller.
     * @param array $args Sender Arguments.
     *
     * @throws Gdn_UserException Permission Denied.
     * @throws Exception Permission Denied.
     */
    public function discussionController_githubIssue_create($sender, $args) {
        if (!$this->isConnected()) {
            $sender->setData('LoginURL', url('/profile/connections', true));
            $sender->render('reconnect', '', 'plugins/github');
            return;
        }

        // Signed in users only.
        if (!(Gdn::session()->isValid())) {
            throw permissionException('Garden.Signin.Allow');
        }
        //Permissions
        $sender->permission('Garden.Staff.Allow');

        //get arguments
        if (count($sender->RequestArgs) != 3) {
            throw new Gdn_UserException('Bad Request', 400);
        }
        list($context, $contextID, $userId) = $sender->RequestArgs;

        // Get Content
        if ($context == 'discussion') {
            $content = $sender->DiscussionModel->getID($contextID);
            $url = discussionUrl($content, 1);
            $title = $content->Name;
        } elseif ($context == 'comment') {
            $commentModel = new CommentModel();
            $content = $commentModel->getID($contextID);
            $discussion = $sender->DiscussionModel->getID($content->DiscussionID);
            $url = commentUrl($content);
            $title = $discussion->Name;

        } else {
            throw new Gdn_UserException('Content Type not supported');
        }

        $repositories = $this->getRepositories();
        $repositoryOptions = '';
        foreach ($repositories as $repo) {
            $repositoryOptions .= '<option>'.Gdn_Format::text($repo).'</option>';
        }

        // If form is being submitted
        if ($sender->Form->isPostBack() && $sender->Form->authenticatedPostBack() === true) {
            // Form Validation
            $sender->Form->validateRule('Title', 'function:ValidateRequired', 'Title is required');
            $sender->Form->validateRule('Body', 'function:ValidateRequired', 'Body is required');
            $sender->Form->validateRule('Repository', 'function:ValidateRequired', 'Repository is required');
            // If no errors
            if ($sender->Form->errorCount() == 0) {
                $formValues = $sender->Form->formValues();

                $bodyAppend = "\n\n".t("This Issue was generated from your [forums] \n");
                $bodyAppend .= "[forums]: $url\n";

                $issue = $this->createIssue(
                    $formValues['Repository'],
                    [
                        'title' => $formValues['Title'],
                        'body' => $formValues['Body'].$bodyAppend,
                        'labels' => ['Vanilla'],
                    ]
                );
                if ($issue != false) {
                    $attachmentModel = AttachmentModel::instance();
                    $attachmentModel->save([
                        'Type' => 'github-issue',
                        'ForeignID' => AttachmentModel::rowID($content),
                        'ForeignUserID' => $content->InsertUserID,
                        'Source' => 'github',
                        'SourceID' => $issue['number'],
                        'SourceURL' => 'https://github.com/'.$formValues['Repository'].'/issues/'.$issue['number'],
                        'LastModifiedDate' => Gdn_Format::toDateTime(),
                        'State' => $issue['state'],
                        'Assignee' => $issue['assignee'],
                        'MileStone' => $issue['milestone'],
                        'Repository' => $formValues['Repository'],
                    ]);


                    $sender->jsonTarget('', $url, 'Redirect');
                    $sender->informMessage(t('GitHub Issue created'));
                } else {
                    $sender->informMessage(t('Error creating GitHub Issue'));
                }

            }
        }

        $data = [
            'RepositoryOptions' => $repositoryOptions,
            'Body' => $this->convertToMDCompatible($content->Body, $content->Format),
            'Title' => $title,
        ];
        $sender->setData($data);
        $sender->Form->setData($data);


        $sender->render('createissue', '', 'plugins/github');


    }

    /**
     * Add option to create issue to Cog.
     *
     * @param DiscussionController $sender Sending controller.
     * @param array $args Sender Arguments.
     *
     * @todo remove option if issue has been created.
     */
    public function discussionController_discussionOptions_handler($sender, $args) {
        // Staff Only
        $session = Gdn::session();
        if (!$session->checkPermission('Garden.Staff.Allow')) {
            return;
        }
        $userID = $args['Discussion']->InsertUserID;
        $discussionID = $args['Discussion']->DiscussionID;

        // Don not add option if attachment already created.
        $attachments = val('Attachments', $args['Discussion'], []);
        foreach ($attachments as $attachment) {
            if ($attachment['Type'] == 'github-issue') {
                return;
            }
        }

        if (isset($args['DiscussionOptions'])) {
            $args['DiscussionOptions']['GithubIssue'] = [
                'Label' => t('GitHub - Create Issue'),
                'Url' => "/discussion/githubissue/discussion/$discussionID/$userID",
                'Class' => 'Popup',
            ];
        }

    }

    /**
     * Add option to create issue to Cog.
     *
     * @param DiscussionController $sender Sending controller.
     * @param array $args Sender Arguments.
     *
     * @todo remove option if issue has been created.
     */
    public function discussionController_commentOptions_handler($sender, $args) {
        //Staff Only
        $session = Gdn::session();
        if (!$session->checkPermission('Garden.Staff.Allow')) {
            return;
        }
        $userID = $args['Comment']->InsertUserID;
        $commentID = $args['Comment']->CommentID;
        // Don not add option if attachment already created.
        $attachments = val('Attachments', $args['Comment'], []);
        foreach ($attachments as $attachment) {
            if ($attachment['Type'] == 'github-issue') {
                return;
            }
        }
        if (isset($args['CommentOptions'])) {
            $args['CommentOptions']['GithubIssue'] = [
                'Label' => t('GitHub - Create Issue'),
                'Url' => "/discussion/githubissue/comment/$commentID/$userID",
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
        if (!val('id', $issue)) {
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
        if (val('id', $response, 0) > 0) {
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
     * @param PlugginController $sender Sending controller.
     */
    public function controller_test($sender) {
        $sender->permission('Garden.Settings.Manage');

        $args = $sender->RequestArgs;
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
                $repos = $this->getRepositories();
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

                if (val('errors', $issue)) {
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
     * @param string $text Text to be converted.
     * @param string $format Format of text.
     * @return string MD Compatible text.
     */
    public function convertToMDCompatible($text, $format = 'Html') {
        switch ($format) {
            case 'Markdown':
                return $text;
                break;
            default:
                return Gdn_Format::text($text, false);
        }
    }

    /**
     * Return the list of configured repositories.
     *
     * @return array
     */
    public function getRepositories() {
        $repositories = array_keys(c('Plugins.Github.Repos', []));
        foreach($repositories as &$repo) {
            $repo = str_replace('-{dot}-', '.', $repo);
        }
        return $repositories;
    }

}
