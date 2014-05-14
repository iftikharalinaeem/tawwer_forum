<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

// Define the plugin:
$PluginInfo['github'] = array(
    'Name' => 'Github',
    'Description' => "Github",
    'Version' => '0.0.1-alpha',
    'RequiredApplications' => array('Vanilla' => '2.1.18'),
    'SettingsUrl' => '/dashboard/plugin/github',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'MobileFriendly' => true,
    'Author' => 'John Ashton',
    'AuthorEmail' => 'john@vanillaforums.com',
    'AuthorUrl' => 'http://www.github.com/John0x00'
);

/**
 * Github plugin.
 */
class GithubPlugin extends Gdn_Plugin {


    /**
     * @var string
     */
    protected $accessToken;

    const API_BASE_URL = 'https://api.github.com';

    const PROVIDER_KEY = 'github';

    const OAUTH_BASE_URL = 'https://github.com';

    //OAuth Methods

    /**
     * Set AccessToken to be used.
     */
    public function setAccessToken() {

        $this->accessToken = GetValueR('Attributes.' . self::PROVIDER_KEY . '.AccessToken', Gdn::Session()->User);
        if (!$this->accessToken) {
            $this->accessToken = C('Plugins.Github.GlobalLogin.AccessToken');
            if ($this->accessToken) {
                Trace('Github Using Global Login');
            }
        }

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
            throw new Gdn_UserException('Github is not configured yet');
        }
        $AppID = C('Plugins.Github.ApplicationID');
        if (!$RedirectUri) {
            $RedirectUri = self::redirectUri();
        }
        $Query = array(
            'redirect_uri' => $RedirectUri,
            'client_id' => $AppID,
            'response_type' => 'code',
            'scope' => 'repo',

        );
        return self::OAUTH_BASE_URL . '/login/oauth/authorize?' . http_build_query($Query);
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
            $RedirectUri = Url('/profile/github', true, true, true);
        }
        return $RedirectUri;
    }

    /**
     * OAuth Method.  Sends request to validate tokens.
     *
     * @param string $Code OAuth Code.
     * @param string $RedirectUri Redirect Uri.
     *
     * @return string Response
     * @throws Gdn_UserException If error.
     */
    public static function getTokens($Code, $RedirectUri) {
        if (!self::isConfigured()) {
            throw new Gdn_UserException('Github is not configured yet');
        }
        $Post = array(
            'client_id' => C('Plugins.Github.ApplicationID'),
            'client_secret' => C('Plugins.Github.Secret'),
            'code' => $Code,
            'redirect_uri' => $RedirectUri,
        );


        $Proxy = new ProxyRequest();
        $Response = $Proxy->Request(
            array(
                'URL' => self::OAUTH_BASE_URL . '/login/oauth/access_token',
                'Method' => 'POST',
            ),
            $Post,
            '',
            array('Accept' => 'application/json')
        );

        if ($Proxy->ResponseStatus == 404) {
            throw new Gdn_UserException('Error Communicating with Github API');
        }

        if (isset($Response->error)) {
            throw new Gdn_UserException('Error Communicating with Github API: ' . $Response->error_description);
        }

        return json_decode($Response);

    }

    /**
     * OAuth Method.
     *
     * @return string $Url
     */
    public static function profileConnectUrl() {
        return Gdn::Request()->Url('/profile/githubconnect', true, true, true);
    }

    /**
     * OAuth Method.
     *
     * @return bool
     */
    public static function isConfigured() {
        $Url = C('Plugins.Github.Url');
        $AppID = C('Plugins.Github.ApplicationID');
        $Secret = C('Plugins.Github.Secret');
        if (!$AppID || !$Secret || !$Url) {
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
        if ($this->accessToken) {
            return true;
        }
        return false;
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
        $Sf = GetValueR('User.Attributes.' . self::PROVIDER_KEY, $Args);
        Trace($Sf);
        $Profile = GetValueR('User.Attributes.' . self::PROVIDER_KEY . '.Profile', $Args);
        $Sender->Data["Connections"][self::PROVIDER_KEY] = array(
            'Icon' => $this->GetWebResource('github.png', '/'),
            'Name' => ucfirst(self::PROVIDER_KEY),
            'ProviderKey' => self::PROVIDER_KEY,
            'ConnectUrl' => self::authorizeUri(self::profileConnectUrl()),
            'Profile' => array(
                'Name' => GetValue('fullname', $Profile),
                'Photo' => GetValue('photo', $Profile)
            )
        );
    }

    /**
     * OAUth Method.  Code is Exchanged for Token.
     *
     * Token is stored for later use.  Token does not expire.  It can be revoked from Github
     *
     * @param ProfileController $Sender Sending controller.
     * @param string $UserReference User Reference.
     * @param string $Username Username.
     * @param bool|string $Code Authorize Code.
     */
    public function profileController_githubConnect_create(
        $Sender,
        $UserReference = '',
        $Username = '',
        $Code = false
    ) {

        if (stristr(Gdn::Request()->Url(), 'globallogin') !== false) {
            Redirect(Url('/plugin/github/connect?code=' . Gdn::Request()->Get('code')));
        }
        $Sender->Permission('Garden.SignIn.Allow');
        $Sender->GetUserInfo($UserReference, $Username, '', true);
        $Sender->_SetBreadcrumbs(T('Connections'), UserUrl($Sender->User, '', 'connections'));

        try {
            $Tokens = $this->getTokens($Code, self::profileConnectUrl());

        } catch (Gdn_UserException $e) {
            $Attributes = array(
                'AccessToken' => null,
                'Profile' => null,
            );
            Gdn::UserModel()->SaveAttribute($Sender->User->UserID, self::PROVIDER_KEY, $Attributes);
            $Message = $e->getMessage();
            Gdn::Dispatcher()->PassData('Exception', htmlspecialchars($Message))
                ->Dispatch('home/error');
            return;
        }
        $AccessToken = GetValue('access_token', $Tokens);
        $this->accessToken = $AccessToken;
        $profile = $this->getProfile();

        Gdn::UserModel()->SaveAuthentication(
            array(
                'UserID' => $Sender->User->UserID,
                'Provider' => self::PROVIDER_KEY,
                'UniqueID' => $profile['id']
            )
        );
        $Attributes = array(
            'AccessToken' => $AccessToken,
            'Profile' => $profile,
        );
        Gdn::UserModel()->SaveAttribute($Sender->User->UserID, self::PROVIDER_KEY, $Attributes);
        $this->EventArguments['Provider'] = self::PROVIDER_KEY;
        $this->EventArguments['User'] = $Sender->User;
        $this->FireEvent('AfterConnection');

        Redirect(UserUrl($Sender->User, '', 'connections'));
    }

    /**
     * OAuth Method. Redirects user to request access.
     */
    public function controller_authorize() {
        Redirect(self::authorizeUri(self::globalConnectUrl()));
    }

    /**
     * OAuth Method. Handles the redirect from Github and stores AccessToken.
     *
     * @throws Gdn_UserException If Error.
     */
    public function controller_connect() {
        $Code = Gdn::Request()->Get('code');
        $Tokens = $this->getTokens($Code, self::globalConnectUrl());
        $AccessToken = GetValue('access_token', $Tokens);

        if ($AccessToken) {
            $this->accessToken = $AccessToken;
            SaveToConfig(
                array(
                    'Plugins.Github.GlobalLogin.Enabled' => true,
                    'Plugins.Github.GlobalLogin.AccessToken' => $AccessToken
                )
            );
        } else {
            RemoveFromConfig(
                array(
                    'Plugins.Github.GlobalLogin.Enabled' => true,
                    'Plugins.Github.GlobalLogin.AccessToken' => $AccessToken
                )
            );
            throw new Gdn_UserException('Error Connecting to Github');
        }
        Redirect(Url('/plugin/github'));

    }

    /**
     * OAuth Method.
     *
     * @return string
     */
    public static function globalConnectUrl() {
        return Gdn::Request()->Url('/profile/githubconnect/globallogin/', true, true, true);
    }

    /**
     * Enable/Disable Global Login.
     *
     * @param Controller $Sender Sending controller.
     */
    public function controller_toggle($Sender) {
        // Enable/Disable
        if (Gdn::Session()->ValidateTransientKey(GetValue(1, $Sender->RequestArgs))) {
            if (C('Plugins.Github.GlobalLogin.Enabled')) {
                RemoveFromConfig('Plugins.Github.GlobalLogin.Enabled');
                RemoveFromConfig('Plugins.Github.GlobalLogin.AccessToken');
                Redirect(Url('/plugin/github'));
            }
            Redirect(Url('/plugin/github/authorize'));

        }
    }

    //end of OAUTH

    /**
     * Setup the plugin.
     */
    public function setup() {
        // Save the provider type.
        Gdn::SQL()->Replace(
            'UserAuthenticationProvider',
            array(
                'AuthenticationSchemeAlias' => 'github',
                'URL' => '...',
                'AssociationSecret' => '...',
                'AssociationHashMethod' => '...'
            ),
            array('AuthenticationKey' => self::PROVIDER_KEY),
            true
        );
        Gdn::PermissionModel()->Define(array('Garden.Staff.Allow' => 'Garden.Moderation.Manage'));
        $this->setupConfig();
    }

    /**
     * Setup Config Settings.
     */
    protected function setupConfig() {
        $ConfigSettings = array(
            'Url',
            'ApplicationID',
            'Secret'
        );
        //prevents resetting any previous values
        foreach ($ConfigSettings as $ConfigSetting) {
            if (!C('Plugins.Github.' . $ConfigSetting)) {
                SaveToConfig('Plugins.Github.' . $ConfigSetting, '');
            }
        }
    }


    /**
     * Writes and updates discussion attachments.
     *
     * @param DiscussionController $Sender Sending controller.
     * @param array $Args Event Arguments.
     */
    public function discussioncontroller_afterDiscussionBody_handler($Sender, $Args) {
       // var_export($this->getProfile(), true);
    }


    //API Calls

    /**
     * Make request to the API.
     *
     * @param string $endPoint Path of the API endpoint.  ie: /users.
     * @param array $Post Post values.
     *
     * @return string JSON response from Github.
     * @throws Gdn_UserException If response != 200.
     */
    public function apiRequest($endPoint, $post = null) {
        if ($this->accessToken === null) {
            $this->setAccessToken();
        }
        $Proxy = new ProxyRequest();
        $Response = $Proxy->Request(
            array(
                'URL' => self::API_BASE_URL . $endPoint,
                'Method' => ($post === null) ? 'GET' : 'POST',
                'PreEncodePost' => ($post === null) ? false : true,
            ),
            $post,
            null,
            array(
                'Authorization' => ' token ' . $this->accessToken,
                'Accept' => 'application/json'
            )
        );
        Trace('Github API Request: ' . self::API_BASE_URL . $endPoint);
        $DecodedResponse = json_decode($Response, true);
//        if (!GetValue('errors', $DecodedResponse) && $Proxy->ResponseStatus != 200) {
//            throw new Gdn_UserException('Invalid apiRequest', $Proxy->ResponseStatus);
//        }

        return $DecodedResponse;
    }

    /**
     * Get Profile of current authenticated user.
     */
    public function getProfile() {
        $fullProfile = $this->apiRequest('/user');
        return array(
            'id' => $fullProfile['id'],
            'fullname' => $fullProfile['name'],
            'photo' => $fullProfile['avatar_url'],
        );
    }

    //End of API Calls


    /**
     * Creates the Virtual Github Controller and adds Link to SideMenu in the dashboard.
     *
     * @param PluginController $Sender Sending controller.
     */
    public function pluginController_github_create($Sender) {

        $Sender->Permission('Garden.Settings.Manage');
        $Sender->Title('Github');
        $Sender->AddSideMenu('plugin/github');
        $Sender->Form = new Gdn_Form();
        $this->Dispatch($Sender, $Sender->RequestArgs);
    }

    /**
     * Dashboard Settings.
     *
     * Default method of virtual controller.
     *
     * @param pluginController $Sender Sending controller.
     */
    public function controller_index($Sender) {

        $Sender->AddCssFile('admin.css');

        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->SetField(array('ApplicationID', 'Secret'));

        // Set the model on the form.
        $Sender->Form->SetModel($ConfigurationModel);

        // If seeing the form for the first time...
        if ($Sender->Form->AuthenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $Sender->Form->SetData($ConfigurationModel->Data);
        } else {
            $FormValues = $Sender->Form->FormValues();
            if ($Sender->Form->IsPostBack()) {
                $Sender->Form->ValidateRule(
                    'ApplicationID',
                    'function:ValidateRequired',
                    'Unique Identifier is required'
                );

                //repo validation.
                $repos = explode("\n", trim($FormValues['Repositories']));
                if ($repos[0] == '') {
                    $Sender->Form->AddError('Please enter a valid repo name', 'Repositories');
                } else {
                    foreach ($repos as $repo) {
                        try {
                            if (!$this->isValidRepo(trim($repo))) {
                                $Sender->Form->AddError('Repository not found: ' . $repo, 'Repositories');
                            }
                        } catch (Gdn_UserException $e) {
                            $Sender->Form->AddError('Invalid Repository: ' . $repo, 'Repositories');
                        }
                    }
                }



                $Sender->Form->ValidateRule('Secret', 'function:ValidateRequired', 'Secret is required');


                if ($Sender->Form->ErrorCount() == 0) {
                    foreach ($repos as $repo) {
                        SaveToConfig('Plugins.Github.Repos.' . trim($repo), true);
                    }
                    SaveToConfig('Plugins.Github.ApplicationID', trim($FormValues['ApplicationID']));
                    SaveToConfig('Plugins.Github.Secret', trim($FormValues['Secret']));
                    $Sender->InformMessage(T("Your changes have been saved."));
                } else {
                    $Sender->InformMessage(T("Error saving settings to config."));
                }
            }

        }

        $Sender->Form->SetValue('ApplicationID', C('Plugins.Github.ApplicationID'));
        $Sender->Form->SetValue('Secret', C('Plugins.Github.Secret'));

        $Repositories = C('Plugins.Github.Repos', array());
        $ReposForForm = '';
        foreach (array_keys($Repositories) as $Repo) {
            $ReposForForm .= $Repo . "\n";
        }
        $ReposForForm = trim($ReposForForm);
        $Sender->Form->SetValue('Repositories', $ReposForForm);


        $Sender->SetData(array(
                'GlobalLoginEnabled' => C('Plugins.Github.GlobalLogin.Enabled'),
                'GlobalLoginConnected' => C('Plugins.Github.GlobalLogin.AccessToken'),
                'ToggleUrl' => Url('/plugin/github/toggle/' . Gdn::Session()->TransientKey())
            ));
        if (C('Plugins.Github.GlobalLogin.Enabled')) {
            $globalLoginProfile = $this->getProfile();
            $Sender->SetData('GlobalLoginProfile', $globalLoginProfile);
        }

        $Sender->Render($this->GetView('dashboard.php'));
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

        // Signed in users only.
        if (!(Gdn::Session()->IsValid())) {
            throw PermissionException('Garden.Signin.Allow');
        }
        //Permissions
        $Sender->Permission('Garden.Staff.Allow');

        //get arguments
        if (count($Sender->RequestArgs) != 3) {
            throw new Gdn_UserException('Bad Request', 400);
        }
        list($context, $contextID, $userId) = $Sender->RequestArgs;

        // Get Content
        if ($context == 'discussion') {
            $Content = $Sender->DiscussionModel->GetID($contextID);
            $Url = DiscussionUrl($Content, 1);
        } elseif ($context == 'comment') {
            $CommentModel = new CommentModel();
            $Content = $CommentModel->GetID($contextID);
            $Url = CommentUrl($Content);

        } else {
            throw new Gdn_UserException('Content Type not supported');
        }

        $Repositories = C('Plugins.Github.Repos', array());
        $RepositoryOptions = '';
        foreach (array_keys($Repositories) as $Repo) {
            $RepositoryOptions .= '<option>' . Gdn_Format::Text($Repo) . '</option>';
        }

        // If form is being submitted
        if ($Sender->Form->IsPostBack() && $Sender->Form->AuthenticatedPostBack() === true) {
            // Form Validation
            $Sender->Form->ValidateRule('Title', 'function:ValidateRequired', 'Title is required');
            $Sender->Form->ValidateRule('Body', 'function:ValidateRequired', 'Body is required');
            $Sender->Form->ValidateRule('Repository', 'function:ValidateRequired', 'Repository is required');
            // If no errors
            if ($Sender->Form->ErrorCount() == 0) {
                $FormValues = $Sender->Form->FormValues();

                //@todo save attachment....
                $AttachmentModel = AttachmentModel::Instance();

                $Sender->JsonTarget('', $Url, 'Redirect');
                $Sender->InformMessage(T('Github Lead Created'));

            }
        }

        $Data = array(
            'RepositoryOptions' => $RepositoryOptions,
            'Body' => $Content->Body
        );
        $Sender->SetData($Data);
        $Sender->Form->SetData($Data);


        $Sender->Render('createissue', '', 'plugins/github');


    }

    /**
     * Add option to create issue to Cog.
     *
     * @param DiscussionController $Sender Sending controller.
     * @param array $Args Sender Arguments.
     */
    public function discussionController_discussionOptions_handler($Sender, $Args) {
        //Staff Only
        $Session = Gdn::Session();
        if (!$Session->CheckPermission('Garden.Staff.Allow')) {
            return;
        }
        $UserID = $Args['Discussion']->InsertUserID;
        $DiscussionID = $Args['Discussion']->DiscussionID;
        if (isset($Args['DiscussionOptions'])) {
            $Args['DiscussionOptions']['GithubIssue'] = array(
                'Label' => T('Github - Create Issue'),
                'Url' => "/discussion/githubissue/discussion/$DiscussionID/$UserID",
                'Class' => 'Popup'
            );
        }
    }

    //API Methods

    /**
     * Create an Issue using the github API.
     *
     * @param string $repo Full repo name.  Example John/MyRepo.
     * @param array $issue Issue details.
     *
     * @link https://developer.github.com/v3/repos/#create
     *
     * @return array
     */
    protected function createIssue($repo, $issue) {

        $response = $this->apiRequest('/repos/' . $repo . '/issues', json_encode($issue));

        return $response;
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

        if (substr_count($repo, '/') != 1) {
            throw new Gdn_UserException('Invalid repo name: ' . $repo);
        }

        $response = $this->apiRequest('/repos/' . $repo);
        if (GetValue('id', $response, 0) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Test controller.
     *
     * @param PlugginController $Sender Sending controller.
     */
    public function controller_test($Sender) {

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
                $repos = array_keys(C('Plugins.Github.Repos', array()));
                var_dump('Repos from config: ');
                var_dump($repos);

                break;
            case 'isValidRepo':

                $repos = array(
                    'John0x00/test' => true,
                    'John0x00/VanillaPlugins' => true
                );
                foreach (array_keys($repos) as $repo) {
                    echo "Repo $repo =====> " . var_export($this->isValidRepo($repo), true) . '<br/>';
                }
                break;

            case 'createIssue':

                $issue = $this->createIssue(
                    'John0x00/VanillaPlugins',
                    array(
                        'title' => 'title',
                        'body' => 'body',
//                'assignee' => '',
//                'milestone' => '',
//                'labels' => array('label1', 'label2')
                    )
                );

                if (GetValue('errors', $issue)) {
                    $errorMessage = '';
                    var_dump($issue['errors']);
                    foreach ($issue['errors'] as $error) {
                        $errorMessage .= $error['code'] . ' ' . $error['field'] . "<br/>";
                    }
                    var_dump("Failed creating issue: \n" . $errorMessage);
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


}

