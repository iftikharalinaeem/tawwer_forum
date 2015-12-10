<?php


$PluginInfo['Oxford'] = array(
    'Name' => 'Oxford',
    'Description' => 'Customizations for Oxford Forums',
    'Version' => '1.0.0',
    'RequiredApplications' => array('Vanilla' => '2.0.18'),
    'RequiredTheme' => false,
    'HasLocale' => false,
    'SettingsUrl' => '/settings/Oxford',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'MobileFriendly' => true,
    'Author' => "Patrick Kelly",
    'AuthorEmail' => 'patrick.k@vanillaforums.com'
);

require_once(dirname(__DIR__) . '/Auth0/class.oauth2pluginbase.php');

class OxfordPlugin extends OAuth2PluginBase implements Gdn_IPlugin {

    public function __construct() {
        $this
            ->setProviderKey('Oxford')
            ->setRequestAccessTokenParams(array('grant_type' => 'authorization_code', 'response_type' => 'code', 'state' => gdn::request()->get('state')));
    }

    /**
     * Override parent provider with the url endpoints specific to this provider.
     *
     * @return array Row from GDN_UserAuthenticationProvider table customized.
     */
    public function provider() {
        $provider = parent::provider();

        $baseUrl = rtrim($provider['BaseUrl'], '/');
        $provider['TokenUrl'] = "$baseUrl/oauth/access_token";
        $provider['AuthorizeUrl'] = "$baseUrl/oauth/authorize";
        $provider['ProfileUrl'] = "$baseUrl/user.json";
        $provider['RegisterUrl'] = "$baseUrl/users/signup";
        $provider['SignOutUrl'] = "$baseUrl/users/signout";

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
        // Make sure we have the Oxford provider.
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

        $formFields['BaseUrl'] = ['LabelCode' => 'Domain', 'Options' => ['Class' => 'InputBox BigInput'], 'Description' => 'Enter the base URL of Oxford\'s sign-in API.'];

        return $formFields;
    }

    /**
     * Wrapper function for writing a generic settings controller.
     *
     * @param SettingsController $sender.
     * @param SettingsController $args.
     */
    public function settingsController_Oxford_create($sender, $args) {
        $sender->setData('Title', sprintf(T('%s Settings'), 'Oxford SSO'));

        // Create send the possible redirect URLs that will be required by Auth0 and display them in the dashboard.
        // Use Gdn::Request instead of convience function so that we can return http and https.
        $redirectUrls = Gdn::request()->Url('/entry/'. $this->getProviderKey(), true, false).','.Gdn::Request()->Url('/entry/'. $this->getProviderKey(), true, true);
        $sender->setData('redirectUrls', $redirectUrls);

        $this->settingsController_oAuth2_create($sender, $args);
    }



    /**
     * Get profile data from authentication provider through API.
     *
     * @return array User profile from provider.
     */
    public function getProfile() {
        $provider = $this->provider();

        $uri = $this->requireVal('ProfileUrl', $provider, "provider");

        $uri .= (strpos($uri, '?') === false) ? "?" : "&";

        $uri .= "access_token=".urlencode($this->accessToken());

        $uri .= "&client_id=" . $this->requireVal('AssociationKey', $provider, "provider");

        $this->log('getProfile API call', array('ProfileUrl' => $uri));

        $rawProfile = $this->api($uri);
        $profile = $this->translateProfileResults($rawProfile['user']);

        return $profile;
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

    public function entryController_Oxford_create($sender, $code = false, $state = false) {
        $this->entryController_OAuth2_create($sender, $code, $state);
    }


    /**
     * Remove photos from User table that were inserted when signing up from Auth0
     *
     * @param EntryController $sender.
     * @param EntryController $args.
     */
    public function EntryController_OAuth_handler($sender) {
        $formValues = $sender->Form->FormValues();
        $sender->Form->addHidden();
        if($formValues) {
            $chosenDisplayName = val("ConnectName", $formValues, null);
            $passedDisplayName = val("displayname", $formValues['user_metadata'], null);
        }

        if($passedDisplayName) {
            $sender->Form->setFormValue('Name', $passedDisplayName);
            $sender->Form->ValidateRule('Name', 'ValidateUsername');
        }

        if($chosenDisplayName) {
            $sender->Form->setFormValue('Name', $chosenDisplayName);
            $sender->Form->ValidateRule('Name', 'ValidateUsername');
        }
    }


    public function entryController_revoke_create($sender, $type=null) {
        if ($type != $this->getProviderKey()) {
            return;
        }

        $reason = gdn::request()->get('invalidation_reason');
        $subject = gdn::request()->get('invalidation_subject');
        $id = gdn::request()->get('ssoID');
        $session_ids = gdn::request()->get('session_ids');
        $secret = gdn::request()->get('shared_secret');
        gdn::session()->setAttribute($this->getProviderKey() . ".AccessToken", "");
        $this->setAccessToken("");
        gdn::session()->end();
        redirect('/');
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
            'username' => 'Name',
            'uid' => 'UniqueID'
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
        $result = socialSignInButton('Oxford', $url, $type, array('rel' => 'nofollow', 'class' => 'default', 'title' => 'Sign in with Oxford'));
        return $result;
    }

    /**
     * Insert css file for custom styling of signin button/icon.
     *
     * @param AssetModel $sender.
     */
    public function assetModel_styleCss_handler($sender) {
        $sender->addCssFile('oxford.css', 'plugins/Oxford');
    }
}
