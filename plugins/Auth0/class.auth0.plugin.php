<?php

require_once('class.oauth2.plugin.php');

$pluginInfo['Auth0'] = array(
    'Name' => 'Auth0 SSO',
    'Description' => 'Allows user login to be authenticated on Auth0 SSO.',
    'Version' => '1.0.0',
    'RequiredApplications' => array('Vanilla' => '1.0'),
    'RequiredPlugins' => array(
        'OAuth2' => '1.0.0'
    ),
    'RequiredTheme' => false,
    'RequiredPlugins' => false,
    'HasLocale' => false,
    'SettingsUrl' => '/settings/Auth0',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'MobileFriendly' => TRUE
);

class Auth0Plugin extends OAuth2Plugin implements Gdn_IPlugin {

    public function __construct() {
        $this
            ->setProviderKey('Auth0')
            ->setScope('profile');
    }

    /**
     * Override parent provider with the url endpoints specific to this provider.
     *
     * @return array|bool|stdClass
     */
    public function provider() {
        $provider = parent::provider();

        $baseUrl = rtrim($provider['BaseUrl'], '/');
        $provider['TokenUrl'] = "$baseUrl/oauth/token";
        $provider['AuthorizeUrl'] = "$baseUrl/authorize";
        $provider['ProfileUrl'] = "$baseUrl/userinfo";
        $provider['RegisterUrl'] = "$baseUrl/authorize";

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
        // Make sure we have the Auth0 provider.
        $provider = $this->provider();
        if (!$provider) {
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
     * @return array
     */
    protected function getSettingsFormFields() {
        $form = parent::getSettingsFormFields();

        $form['BaseUrl'] = ['LabelCode' => 'Domain', 'Options' => ['Class' => 'InputBox BigInput'], 'Description' => 'Enter the domain found in the dashboard of your Auth0 application.'];

        return $form;
    }

    /**
     * Wrapper function for writing a generic settings controller.
     *
     * @param SettingsController $Sender
     */
    public function settingsController_auth0_create($sender, $args) {
        $sender->setData('Title', sprintf(T('%s Settings'), 'Auth0 SSO'));

        // Create send the possible redirect URLs that will be required by Auth0.
        // Use Gdn::Request instead of convience function so that we can return http and https.
        $redirectUrls = Gdn::request()->Url('/entry/'. $this->getProviderKey(), true, false).','.Gdn::Request()->Url('/entry/'. $this->getProviderKey(), true, true);
        $sender->setData('redirectUrls', $redirectUrls);

        $this->settingsController_oAuth2_create($sender, $args);
    }

    /**
     *  Wrapper function for writing a generic entry controller.
     *
     * @param EntryController $Sender
     * @param string $Code
     * @param string $State
     * @throws Gdn_UserException
     */
    public function entryController_auth0_create($sender, $code = false, $state = false) {
        $this->entryController_OAuth2_create($sender, $code, $state);
    }

    /**
     * Translate the array keys for the profile returning from the provider so that they align with Vanilla keys.
     *
     * @param array $rawProfile
     * @return array
     */
    public function translateProfileResults($rawProfile = array()) {
        $profile = arrayTranslate($rawProfile, [
            'email' => 'Email',
            'provider' => 'Provider',
            'picture' => 'Photo',
            'nickname' => 'Name',
            'name' => 'FullName',
            'user_id' => 'UniqueID'
        ], true);

        $profile['Provider'] = $this->providerKey;

        return $profile;
    }

    /**
     * Create signup button specific to this plugin.
     *
     * @param string $type
     * @return string
     */
    public function signInButton($type = 'button') {
        $target = Gdn::request()->post('Target', Gdn::request()->get('Target', url('', '/')));
        $url = $this->authorizeUri(array('target' => $target));
        $result = socialSignInButton('Auth0', $url, $type, array('rel' => 'nofollow', 'class' => 'default', 'title' => 'Sign in with Auth0'));
        return $result;
    }

    /**
     * Insert css file for custom styling of signin button/icon.
     *
     * @param AssetModel $Sender
     */
    public function assetModel_styleCss_handler($sender) {
        $sender->addCssFile('auth0.css', 'plugins/Auth0');
    }

}
