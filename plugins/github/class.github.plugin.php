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
            'scope' => '',

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
            'Name' => self::PROVIDER_KEY,
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
     *
     * @return string JSON response from Github.
     * @throws Gdn_UserException If response != 200.
     */
    public function apiRequest($endPoint) {
        $Proxy = new ProxyRequest();
        $Response = $Proxy->Request(
            array(
                'URL' => self::API_BASE_URL . $endPoint,
                'Method' => 'GET',
            ),
            null,
            null,
            array(
                'Authorization' => ' token ' . $this->accessToken,
                'Accept' => 'application/json'
            )
        );
        Trace('Github API Request: ' . self::API_BASE_URL . $endPoint);
        if ($Proxy->ResponseStatus != 200) {
            throw new Gdn_UserException('Invalid apiRequest', $Proxy->ResponseStatus);
        }

        return json_decode($Response, true);
    }

    /**
     * Get Profile of current authenticated user.
     */
    public function getProfile() {
        $this->setAccessToken();
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
//
//        $Validation = new Gdn_Validation();
//        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
//        $ConfigurationModel->SetField(array('Url', 'ApplicationID', 'Secret'));
//
//        // Set the model on the form.
//        $Sender->Form->SetModel($ConfigurationModel);
//
//        // If seeing the form for the first time...
//        if ($Sender->Form->AuthenticatedPostBack() === false) {
//            // Apply the config settings to the form.
//            $Sender->Form->SetData($ConfigurationModel->Data);
//        } else {
//            $FormValues = $Sender->Form->FormValues();
//            if ($Sender->Form->IsPostBack()) {
//                $Sender->Form->ValidateRule(
//                    'Url',
//                    'function:ValidateRequired',
//                    'Url is required'
//                );
//                $Sender->Form->ValidateRule(
//                    'ApplicationID',
//                    'function:ValidateRequired',
//                    'Unique Identifier is required'
//                );
//                $Sender->Form->ValidateRule('Secret', 'function:ValidateRequired', 'Secret is required');
//
//
//                if ($Sender->Form->ErrorCount() == 0) {
//                    SaveToConfig('Plugins.Zendesk.ApplicationID', trim($FormValues['ApplicationID']));
//                    SaveToConfig('Plugins.Zendesk.Secret', trim($FormValues['Secret']));
//                    SaveToConfig('Plugins.Zendesk.Url', trim($FormValues['Url']));
//                    $Sender->InformMessage(T("Your changes have been saved."));
//                } else {
//                    $Sender->InformMessage(T("Error saving settings to config."));
//                }
//            }
//
//        }
//
        $Sender->Form->SetValue('ApplicationID', C('Plugins.Zendesk.ApplicationID'));
        $Sender->Form->SetValue('Secret', C('Plugins.Zendesk.Secret'));
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
}
