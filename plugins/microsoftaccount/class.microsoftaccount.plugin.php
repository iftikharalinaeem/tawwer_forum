<?php

$PluginInfo['microsoftaccount'] = array(
    'Name'                 => 'Microsoft Account',
    'ClassName'            => 'MicrosoftAccountPlugin',
    'Description'          => 'Allows users to sign-in with their Microsoft Account credentials.',
    'Version'              => '1.0.0',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'RequiredTheme'        => false,
    'HasLocale'            => false,
    'SettingsUrl'          => '/settings/microsoftaccount',
    'SettingsPermission'   => 'Garden.Settings.Manage',
    'MobileFriendly'       => true
);

require_once('class.oauth2pluginbase.php');

class MicrosoftAccountPlugin extends OAuth2PluginBase implements Gdn_IPlugin {

    public function __construct() {
        $this->setProviderKey('microsoftaccount');

        /**
         * @link https://azure.microsoft.com/en-us/documentation/articles/active-directory-v2-protocols-oauth-code/#request-an-access-token
         */
        $this->setAuthorizeUriParams([
            'response_mode' => 'query'
        ]);

        /**
         * @link https://azure.microsoft.com/en-us/documentation/articles/active-directory-v2-scopes/#scopes-amp-permissions
         * @link https://msdn.microsoft.com/Library/Azure/Ad/Graph/howto/azure-ad-graph-api-permission-scopes#PermissionScopeDetails
         */
        $this->setScope('https://graph.microsoft.com/user.read');
    }

    /**
     * Insert css file for custom styling of signin button/icon.
     *
     * @param AssetModel $sender.
     */
    public function assetModel_styleCss_handler($sender) {
        $sender->addCssFile('microsoftaccount.css', 'plugins/microsoftaccount');
    }

    /**
     * Wrapper function for writing a generic entry controller.
     *
     * @param EntryController $sender.
     * @param $code string Retrieved from the response of the authentication provider, used to fetch an authentication token.
     * @param $state string Values passed by us and returned in the response of the authentication provider.
     * @throws Exception.
     * @throws Gdn_UserException.
     */
    public function entryController_microsoftaccount_create($sender, $code = false, $state = false) {
        $this->entryController_OAuth2_create($sender, $code, $state);
    }

    /**
     * Get profile data from authentication provider through API.
     *
     * @link http://graph.microsoft.io/docs/api-reference/v1.0/api/user_get
     * @return array User profile from provider.
     */
    public function getProfile() {
        $provider = $this->provider();

        $uri = $this->requireVal('ProfileUrl', $provider, 'provider');

        $options = [
            'Authorization-Header-Message' => 'Bearer ' . $this->accessToken()
        ];

        $get = [];

        $rawProfile = $this->api($uri, 'GET', $get, $options);

        $profile = $this->translateProfileResults($rawProfile);

        $this->log('getProfile API call', array('ProfileUrl' => $uri, 'Params' => $get, 'RawProfile' => $rawProfile, 'Profile' => $profile));

        return $profile;
    }

    /**
     * Add form fields to settings specific to this plugin.
     *
     * @return array Form fields.
     */
    protected function getSettingsFormFields() {
        $formFields = parent::getSettingsFormFields();

        $formFields['AssociationKey']['LabelCode'] = 'Application ID';
        $formFields['AssociationSecret']['LabelCode'] = 'Application Secret';

        return $formFields;
    }

    /**
     * Override parent provider with the url endpoints specific to this provider.
     *
     * @return array Row from GDN_UserAuthenticationProvider table customized.
     */
    public function provider() {
        $provider = parent::provider();

        $baseUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0';

        $provider['AuthorizeUrl'] = "{$baseUrl}/authorize";
        $provider['ProfileUrl']   = "https://graph.microsoft.com/v1.0/me";
        $provider['TokenUrl']     = "{$baseUrl}/token";

        return $provider;
    }

    /**
     * Create signup button specific to this plugin.
     *
     * @param string $type Either button or icon to be output.
     * @return string Resulting HTML element (button).
     */
    public function signInButton($type = 'button') {
        $target = Gdn::request()->post(
            'Target',
            Gdn::request()->get(
                'Target',
                url('', '/')
            )
        );
        $url = $this->authorizeUri(
            ['target' => $target]
        );
        $result = socialSignInButton(
            'MicrosoftAccount',
            $url,
            $type,
            [
                'rel'   => 'nofollow',
                'class' => 'default',
                'title' => 'Sign in with a Microsoft Account'
            ]
        );

        return $result;
    }

    /**
     * Wrapper function for writing a generic settings controller.
     *
     * @param SettingsController $sender.
     * @param SettingsController $args.
     */
    public function settingsController_microsoftaccount_create($sender, $args) {
        $sender->setData('Title', sprintf(T('%s Settings'), 'Microsoft Account'));

        $redirectUrls = Gdn::request()->Url('/entry/'. $this->getProviderKey(), true, false).','.Gdn::Request()->Url('/entry/'. $this->getProviderKey(), true, true);
        $sender->setData('redirectUrls', $redirectUrls);

        $this->settingsController_oAuth2_create($sender, $args);
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
        $provider = $this->provider();

        if (!$provider['AuthenticationKey']) {
            $model = new Gdn_AuthenticationProviderModel();
            $provider = array(
                'AuthenticationKey'         => $this->providerKey,
                'AuthenticationSchemeAlias' => $this->providerKey,
                'Name'                      => $this->providerKey
            );

            $model->save($provider);
        }
    }

    /**
     * Translate the array keys for the profile returning from the provider so that they align with Vanilla keys.
     *
     * @link http://graph.microsoft.io/docs/api-reference/v1.0/api/user_get
     * @param array $rawProfile Retrieved from authentication provider.
     * @return array Profile with Vanilla keys.
     */
    public function translateProfileResults($rawProfile = array()) {
        $profile = arrayTranslate(
            $rawProfile,
            [
                'id'                  => 'UniqueID',
                'name'                => 'FullName',
                'userPrincipalName'   => 'Email'
            ]
        );

        $profile['Provider'] = $this->providerKey;

        return $profile;
    }
}
