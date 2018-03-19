<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 * @package microsoftaccount
 */


/**
 * Class MicrosoftAccountPlugin
 *
 * A plug-in to facilitate SSO connections authenticated by Microsoft's "v2.0 app model" OAuth2 service.
 */
class MicrosoftAccountPlugin extends Gdn_OAuth2 {

    /**
     * MicrosoftAccountPlugin constructor.
     *
     * @link https://azure.microsoft.com/en-us/documentation/articles/active-directory-v2-protocols-oauth-code/#request-an-access-token
     * @link https://azure.microsoft.com/en-us/documentation/articles/active-directory-v2-scopes/#scopes-amp-permissions
     * @link https://msdn.microsoft.com/Library/Azure/Ad/Graph/howto/azure-ad-graph-api-permission-scopes#PermissionScopeDetails
     */
    public function __construct() {
        parent::__construct('microsoftaccount');
        $this->settingsView = 'plugins/settings/microsoftaccount';
        $this->setScope('https://graph.microsoft.com/user.read');
        $this->authorizeUriParams = [
            'redirect_uri'  => Gdn::request()->url("/entry/{$this->getProviderKey()}", true, true),
            'response_mode' => 'query',
            'scope' => 'https://graph.microsoft.com/user.read'
        ];
    }


    /**
     * Insert css file for custom styling of signin button/icon.
     *
     * @param AssetModel $sender.
     */
    public function assetModel_styleCss_handler($sender, $args) {
        $sender->addCssFile('microsoftaccount.css', 'plugins/microsoftaccount');
    }


    /**
     * Get profile data from authentication provider through API.
     *
     * @return array User's profile info from Authentication Provider
     * @throws Exception
     * @throws Gdn_UserException
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

        $this->log(
            'getProfile API call',
            [
                'Params'     => $get,
                'Profile'    => $profile,
                'ProfileUrl' => $uri,
                'RawProfile' => $rawProfile
            ]
        );

        return $profile;
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
        $url = $this->authorizeUri(['target' => $target]);
        $result = socialSignInButton(
            'MicrosoftAccount',
            $url,
            $type,
            [
                'class' => 'default',
                'rel'   => 'nofollow',
                'title' => 'Sign in with a Microsoft Account'
            ]
        );

        return $result;
    }


    /**
     * Form for capturing the Application Secret and ID.
     * This over-rides the base class settingsEndpoint().
     *
     * @param $sender SettingsController
     * @param $args SettingsController
     * @throws Gdn_UserException
     */
    public function settingsController_microsoftaccount_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');
        $model = new Gdn_AuthenticationProviderModel();

        /* @var Gdn_Form $form */
        $form = new Gdn_Form();
        $form->setModel($model);
        $sender->Form = $form;

        if (!$form->authenticatedPostBack()) {
            $provider = $this->provider();
            $form->setData($provider);
        } else {

            $form->setFormValue('AuthenticationKey', $this->getProviderKey());

            $sender->Form->validateRule('AssociationKey', 'ValidateRequired', 'You must provide a unique AccountID.');
            $sender->Form->validateRule('AssociationSecret', 'ValidateRequired', 'You must provide a Secret');

            // To satisfy the AuthenticationProviderModel, create a BaseUrl.
            $baseUrlParts = parse_url($form->getValue('AuthorizeUrl'));
            $baseUrl = (val('scheme', $baseUrlParts) && val('host', $baseUrlParts)) ? val('scheme', $baseUrlParts).'://'.val('host', $baseUrlParts) : null;
            if ($baseUrl) {
                $form->setFormValue('BaseUrl', $baseUrl);
                $form->setFormValue('SignInUrl', $baseUrl); // kludge for default provider
            }
            if ($form->save()) {
                $sender->informMessage(t('Saved'));
            }
        }

        // Set up the form.
        $formFields = [
            'AssociationKey' =>  ['LabelCode' => 'Application ID', 'Description' => 'Unique ID of the authentication application.'],
            'AssociationSecret' =>  ['LabelCode' => 'Application Secret', 'Description' => 'Secret provided by the authentication provider.'],
        ];

        $formFields['IsDefault'] = ['LabelCode' => 'Make this connection your default signin method.', 'Control' => 'checkbox'];

        $sender->setData('_Form', $formFields);

        $sender->setHighlightRoute();
        if (!$sender->data('Title')) {
            $sender->setData('Title', sprintf(t('%s Settings'), 'Microsoft Account'));
        }

        $view = ($this->settingsView) ? $this->settingsView : 'plugins/oauth2';

        // Create and send the possible redirect URLs that will be required by the authenticating server and display them in the dashboard.
        // Use Gdn::Request instead of convience function so that we can return http and https.
        $redirectUrls = Gdn::request()->url('/entry/'. $this->getProviderKey(), true, true);
        $sender->setData('redirectUrls', $redirectUrls);

        $sender->render('settings', '', $view);
    }

    /**
     * Setup
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Create the structure in the database.
     */
    public function structure() {
        $provider = $this->provider();

        if (!$provider['AuthenticationKey']) {
            $model = new Gdn_AuthenticationProviderModel();
            $provider = [
                'AuthenticationKey'         => $this->providerKey,
                'AuthenticationSchemeAlias' => $this->providerKey,
                'Name'                      => $this->providerKey
            ];

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
    public function translateProfileResults($rawProfile = []) {
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
