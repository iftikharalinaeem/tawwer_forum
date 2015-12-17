<?php


$PluginInfo['SegaSSO'] = array(
    'Name' => 'Sega SSO',
    'Description' => 'Allows user login to be authenticated on Sega SSO.',
    'Version' => '1.0.1',
    'RequiredApplications' => array('Vanilla' => '2.0.18'),
    'RequiredTheme' => false,
    'HasLocale' => false,
    'SettingsUrl' => '/settings/SegaSSO',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'MobileFriendly' => true,
    'Author' => "Patrick Kelly",
    'AuthorEmail' => 'patrick.k@vanillaforums.com'
);

require_once(dirname(__DIR__) . '/Auth0/class.oauth2pluginbase.php');

class SegaSSOPlugin extends OAuth2PluginBase implements Gdn_IPlugin {

    public function __construct() {
        $this
            ->setProviderKey('SegaSSO')
            ->setScope('ACCOUNT_READ SSO EMAIL DOB TITLES_OWNED COH2')
            ->setAuthorizeUriParams(array('title' => 'coh2', 'language' => 'en', 'response_type' => 'code'))
            ->setRequestAccessTokenParams(array('title' => 'COH2', 'language' => 'en'));
    }

    /**
     * Override parent provider with the url endpoints specific to this provider.
     *
     * @return array Row from GDN_UserAuthenticationProvider table customized.
     */
    public function provider() {
        $provider = parent::provider();

        $baseUrl = rtrim($provider['BaseUrl'], '/');
        $provider['TokenUrl'] = "$baseUrl/token";
        $provider['AuthorizeUrl'] = "$baseUrl/authorize";
        $provider['ProfileUrl'] = "$baseUrl/token";
        $provider['RegisterUrl'] = "$baseUrl/signup";
        $provider['SignOutUrl'] = null;

        return $provider;
    }

    /**
     * Setup
     */
    public function setUp() {
        $this->structure();
    }

    /**
     * Create the structure in the database.
     */
    public function structure() {
        // Make sure we have the Sega provider.
        $provider = $this->provider();
        if (!$provider['AuthenticationKey']) {
            $model = new Gdn_AuthenticationProviderModel();
            $provider = array(
                'AuthenticationKey' => $this->providerKey,
                'AuthenticationSchemeAlias' => $this->providerKey,
                'Name' => $this->providerKey
            );

            $model->save($provider);
        }
    }

    /**
     * Add form fields to settings specific to this plugin.
     *
     * @return array Form fields.
     */
    protected function getSettingsFormFields() {
        $formFields = parent::getSettingsFormFields();

        $formFields['BaseUrl'] = ['LabelCode' => 'Domain', 'Options' => ['Class' => 'InputBox BigInput'], 'Description' => 'Enter the base URL of Sega\'s sign-in API.'];

        return $formFields;
    }

    /**
     * Wrapper function for writing a generic settings controller.
     *
     * @param SettingsController $sender.
     * @param SettingsController $args.
     */
    public function settingsController_segaSSO_create($sender, $args) {
        $sender->setData('Title', sprintf(T('%s Settings'), 'Sega SSO'));

        // Create send the possible redirect URLs that will be required by Auth0 and display them in the dashboard.
        // Use Gdn::Request instead of convience function so that we can return http and https.
        $redirectUrls = Gdn::request()->Url('/entry/'. $this->getProviderKey(), true, false).','.Gdn::Request()->Url('/entry/'. $this->getProviderKey(), true, true);
        $sender->setData('redirectUrls', $redirectUrls);

        $this->settingsController_oAuth2_create($sender, $args);
    }

    /**
     * Wrapper function for writing a generic entry controller.
     *
     * @param EntryController $sender.
     * @param $code string Retrieved from the response of the authentication provider, used to fetch an authentication token.
     * @param $state string Values passed by us and returned in the response of the authentication provider.
     *
     * @throws Exception.
     * @throws Gdn_UserException.
     */
    public function entryController_segaSSO_create($sender, $code = false, $state = false) {
        if ($error = $sender->Request->get('error')) {
            throw new Gdn_UserException($error);
        }

        Gdn::session()->stash($this->getProviderKey()); // remove any stashed provider data.

        $response = $this->requestAccessToken($code);
        if (!$response) {
            throw new Gdn_UserException('The OAuth server did not return a valid response.');
        }

        if (!empty($response['error'])) {
            throw new Gdn_UserException($response['error_description']);
        } elseif (empty($response['access_token'])) {
            throw new Gdn_UserException("The OAuth server did not return an access token.", 400);
        } else {
            $this->accessToken($response['access_token']);
        }

        if(array_key_exists("access_token", $response) === true) {
            $profile = $this->translateProfileResults($response);
        }
        if ($state) {
            parse_str($state, $state);
        } else {
            $state = array('r' => 'entry', 'uid' => null, 'd' => 'none');
        }

        switch ($state['r']) {
            case 'profile':
                // This is a connect request from the user's profile.
                $user = Gdn::userModel()->getID($state['uid']);
                if (!$user) {
                    throw notFoundException('User');
                }
                // Save the authentication.
                Gdn::userModel()->saveAuthentication(array(
                    'UserID' => $user->UserID,
                    'Provider' => $this->getProviderKey(),
                    'UniqueID' => $profile['id']));

                // Save the information as attributes.
                $attributes = array(
                    'AccessToken' => $response['access_token'],
                    'Profile' => $profile
                );

                Gdn::userModel()->saveAttribute($user->UserID, $this->getProviderKey(), $attributes);

                $this->EventArguments['Provider'] = $this->getProviderKey();
                $this->EventArguments['User'] = $sender->User;
                $this->fireEvent('AfterConnection');

                redirect(userUrl($user, '', 'connections'));
                break;
            case 'entry':
            default:

                // This is an sso request, we need to redispatch to /entry/connect/[providerKey] which is Base_ConnectData_Handler() in this class.
                Gdn::session()->stash($this->getProviderKey(), array('AccessToken' => $response['access_token'], 'Profile' => $profile));
                $url = '/entry/connect/'.$this->getProviderKey();

                //pass the target if there is one so that the user will be redirected to where the request originated.
                if ($target = val('target', $state)) {
                    $url .= '?Target='.urlencode($target);
                }
                redirect($url);
                break;
        }
    }

    /**
     * Translate the array keys for the profile returning from the provider so that they align with Vanilla keys.
     *
     * @param array $rawProfile Retrieved from authentication provider.
     *
     * @return array Profile with Vanilla keys.
     */
    public function translateProfileResults($rawProfile = array()) {
        $profile = arrayTranslate($rawProfile, [
            'email' => 'Email',
            'provider' => 'Provider',
            'picture' => 'Photo',
            'name' => 'FullName',
            'ssoID' => 'UniqueID',
            'email_validated' => 'Verified',
            'dob' => 'DateOfBirth'
        ], true);

        $profile['Provider'] = $this->providerKey;

        return $profile;
    }

    /**
     * Create signup button specific to this plugin.
     *
     * @param string $type Either button or icon to be output.
     *
     * @return string Resulting HTML element (button).
     */
    public function signInButton($type = 'button') {
        $target = Gdn::request()->post('Target', Gdn::request()->get('Target', url('', '/')));
        $url = $this->authorizeUri(array('target' => $target)); //. "&scope=" . rawurlencode($this->scope);
        $result = socialSignInButton('Sega', $url, $type, array('rel' => 'nofollow', 'class' => 'default', 'title' => 'Sign in with SEGA'));
        return $result;
    }

    /**
     * Insert css file for custom styling of signin button/icon.
     *
     * @param AssetModel $sender.
     */
    public function assetModel_styleCss_handler($sender) {
        $sender->addCssFile('segaSSO.css', 'plugins/SegaSSO');
    }

    /**
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_render_before($sender, $args) {
//        $sender->addJsFile('https://test-sso.reliclink.com/html/sdk/v1/reliclink.js', '', array('AddVersion' => array('AddVersion' => true, 'id' => 'reliclinksdk')));
//        $sender->addJsFile('managesession.js', 'plugins/SegaSSO', array('id' => 'reliclinksdk', 'class' => 'reliclickn', 'things'=>'stuff'));

        $loggedIn = (gdn::session()->UserID) ? true : false;
        $sender->addDefinition('userLoggedIn', $loggedIn);

        $provider = $this->provider();
        $loginURL = $this->requireVal('AuthorizeUrl', $provider, "provider");
        $sender->addDefinition('loginURL', $loginURL);

        $logoutURL = '/entry/signout?TransientKey=' . gdn::session()->transientKey();
        $sender->addDefinition('logoutURL', $logoutURL);
    }

    /**
     * Add form fields to update the user with the properly formatted date and verified.
     *
     * @param $sender
     * @param $args
     */
    public function entryController_OAuth_handler($sender, $args) {
        $formValues = $sender->Form->FormValues();

        if($formValues) {
            $dateOfBirth = val("DateOfBirth", $formValues, null);
            $verified = val("Verified", $formValues, null);
        }

        if($dateOfBirth) {
            $sender->Form->setFormValue('DateOfBirth', date('Y-m-d', $dateOfBirth));
        }

        if($verified) {
            $sender->Form->setFormValue('Verified', $verified);
        }

    }

    public function profileController_AfterPreferencesDefined_handler() {
        trace(gdn::session()->User, "User");
    }

    /**
     * Update roles after signin with the roles that are associated with titles owned by the user.
     *
     * @param $sender
     * @param $args
     */
    public function userModel_afterSignIn_handler($sender, $args) {
        $titlesRoles = c('Plugins.SegaSSO.TitlesRoles');
        $unverifiedRole = c('Plugins.SegaSSO.UnverifiedRole');

        $userID = gdn::session()->UserID;
        $user = gdn::session()->User;
        $userAttributes = gdn::session()->getAttributes();

        // The $RoleIDs are a comma delimited list of game title role names in the config.
        // Get the ID numbers of each role.
        $titleRoleNames = array_map('trim', explode(',', $titlesRoles));
        $titleRoleIDs = $sender->SQL
            ->select('r.RoleID')
            ->from('Role r')
            ->whereIn('r.Name', $titleRoleNames)
            ->get()->resultArray();

        // Delete all the roles associated with game titles in case the user no longer owns it.
        $titleRoleIDs = array_column($titleRoleIDs, 'RoleID');
        $delete = $sender->SQL->whereIn('RoleID', $titleRoleIDs)->delete('UserRole', array('UserID' => $userID));

        $this->log("Session Attributes in After Signin", $userAttributes);

        // Get the titles currently owned by the user.
        $titlesOwned = $userAttributes['SegaSSO']['Profile']['titles_owned'];
        $this->log("Titles Owned in After Signin", $titlesOwned);

        // Update user roles with the role associated with the game title the user currently owns.
        foreach($titlesOwned as $title) {
            $titleRoleIDs = $sender->SQL->select('r.RoleID')->from('Role r')->where('r.Name', $title['title_name'])->get()->resultArray();
            $titleRoleID = array_column($titleRoleIDs, 'RoleID');
            $sender->SQL->insert('UserRole', array('UserID' => $userID, 'RoleID' => $titleRoleID[0]));
        }

        $sender->clearCache($userID, array('roles', 'permissions'));
    }

}
