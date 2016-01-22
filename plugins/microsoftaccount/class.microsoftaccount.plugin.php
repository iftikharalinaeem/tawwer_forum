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

        $this->setAuthorizeUriParams([
            'response_type' => 'id_token',
            'nonce'         => uniqid('', true)
        ]);

        /**
         * @link https://msdn.microsoft.com/Library/Azure/Ad/Graph/howto/azure-ad-graph-api-permission-scopes#PermissionScopeDetails
         */
        $this->setScope('openid email profile');
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
        $provider['TokenUrl']     = "{$baseUrl}/token";

        Logger::event('sso_provider', Logger::DEBUG, print_r($provider, true));
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
     * Add form fields to settings specific to this plugin.
     *
     * @return array Form fields.
     */
    protected function getSettingsFormFields() {
        $formFields = parent::getSettingsFormFields();

        $formFields['AssociationKey']['LabelCode'] = 'Application ID';
        $formFields['AssociationSecret']['LabelCode'] = 'Private Key';

        return $formFields;
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
     * Wrapper function for writing a generic entry controller.
     *
     * @param EntryController $sender.
     * @param $code string Retrieved from the response of the authentication provider, used to fetch an authentication token.
     * @param $state string Values passed by us and returned in the response of the authentication provider.
     *
     * @throws Exception.
     * @throws Gdn_UserException.
     */
    public function entryController_microsoftaccount_create($sender, $code = false, $state = false) {
        $this->entryController_OAuth2_create($sender, $code, $state);
    }

    /**
     * Create signup button specific to this plugin.
     *
     * @param string $type Either button or icon to be output.
     *
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
                'title' => 'Sign in with your Microsoft Account'
            ]
        );

        return $result;
    }

    /**
     * Insert css file for custom styling of signin button/icon.
     *
     * @param AssetModel $sender.
     */
    public function assetModel_styleCss_handler($sender) {
        $sender->addCssFile('microsoftaccount.css', 'plugins/microsoftaccount');
    }
}
