<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license proprietary
 */


/**
 * Class FireBasePlugin
 *
 * This plugin injects the Firebase SDK into the head of pages to detect via Javascript if a visitor is logged into a firebase provider
 * and if so, and if the user is not already logged in, connects the user to the forum.
 */
class FireBasePlugin extends Gdn_OAuth2 {

    /* var array of possible third-party providers that can be handled by Firebase. */
    public $availableAuthProviders = [
            'GoogleAuthProvider',
            'FacebookAuthProvider',
            'TwitterAuthProvider',
            'GithubAuthProvider',
            'EmailAuthProvider',
        ];

    /**
     * fireBasePlugin constructor.
     */
    public function __construct() {
        $this->setProviderKey('firebase');
        $this->settingsView = 'plugins/settings/firebase';
    }

    /**
     * Inject the Javascript and CSS files into the head to listen for and handle logged in users.
     *
     * @param VanillaController $sender
     * @param VanillaController $args
     * @throws Exception
     */
    public function base_afterRenderAsset_handler($sender, $args) {
        if (Gdn::controller()->deliveryMethod() != DELIVERY_METHOD_XHTML) {
            return;
        }

        // Include the javascript only in the head.
        if (val('AssetName', $args) !== 'Head') {
            return;
        }

        // Do not include in the entry pages since we are already connecting.
        if ($sender->ControllerName === 'entrycontroller') {
            return;
        }

        // Don't bother if the provider is not configured.
        if (!$this->isConfigured()) {
            return;
        }

        $provider = $this->provider();
        $configuredAuthProviders = $this->configuredProviders($provider);
        $useFirebaseUI = false;
        $autoDetectFirebasUser = false;

        // If the user is not logged in, FirebaseUI is configured, set UseFirebaseUI to true.
        if (!Gdn::session()->isValid() && val('UseFirebaseUI', $provider) && $configuredAuthProviders) {
            $useFirebaseUI = true;
        }

        if (!Gdn::session()->isValid() && !$configuredAuthProviders) {
            $autoDetectFirebasUser = true;
        }

        // Create the configuration providers for FirebaseUI interface.
        if ($configuredAuthProviders) {
            $authProvidersConfigured = [];
            foreach ($configuredAuthProviders as $authProvider) {
                $authProvidersConfigured[] = 'firebase.auth.'.$authProvider.'.PROVIDER_ID';
            }
        }

        $sender->setData('APIKey', val('AssociationKey', $provider));
        $sender->setData('AuthDomain', val('AuthenticateUrl', $provider));
        $sender->setData('UseFirebaseUI', $useFirebaseUI);
        $sender->setData('AutoDetectFirebaseUser', $autoDetectFirebasUser);
        $sender->setData('FirebaseAuthProviders', implode(",\n", $authProvidersConfigured));
        $sender->setData('TermsUrl', val('TermsUrl', $provider));
        $sender->setData('DebugJavascript', c('Vanilla.SSO.Debug'));
        include $sender->fetchViewLocation('firebase-js', '', 'plugins/firebase');
   }

   public function vanillaController_firebasesignin_create($sender, $args) {
        $sender->render('firebasesignin', '', 'plugins/firebase');
   }

    /**
     * Inject a container into page for the Firebase SDK to print out buttons, etc.
     *
     * @param VanillaController $sender
     * @param VanillaController $args
     * @return string An HTML element to receive Firebase UI interface.
     */
    public function base_afterSignInButton_handler($sender, $args) {
        $provider = $this->provider();
        if (!val('UseFirebaseUI', $provider)) {
            return '';
        }
        $path = Gdn::request()->getPath();
        if ($path !== '/vanilla/firebasesignin') {
            echo '
                <div id="firebaseui-auth-container"></div> 
            ';
        }
    }

    /**
     * Create a form in the Dashboard to save the Firebase API Key and Auth Domain as well is toggle on and off
     *  which third-party providers to use.
     *
     * @param Gdn_Controller $sender
     * @param Gdn_Controller $args
     * @throws Gdn_UserException
     */
    public function settingsEndpoint($sender, $args) {
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

            $sender->Form->validateRule('AssociationKey', 'ValidateRequired', 'You must provide an API Key.');
            $sender->Form->validateRule('AuthenticateUrl', 'ValidateRequired', 'You must provide a valid Firebase Auth Domain.');

            // To satisfy the AuthenticationProviderModel, create a BaseUrl.
            $baseUrlParts = parse_url($form->getValue('AuthenticateUrl'));
            $baseUrl = (val('scheme', $baseUrlParts) && val('host', $baseUrlParts)) ? val('scheme', $baseUrlParts).'://'.val('host', $baseUrlParts) : null;
            if ($baseUrl) {
                $form->setFormValue('BaseUrl', $baseUrl);
            }
            if ($form->save()) {
                $sender->informMessage(t('Saved'));
            }
        }

        // Set up the form.
        $formFields = [
            'AssociationKey' =>  ['LabelCode' => 'API Key', 'Description' => 'API key from the console of your Firebase Web App.'],
            'AuthenticateUrl' =>  ['LabelCode' => 'Auth Domain', 'Description' => 'Auth Domain from the console of your Firebase Web App.'],
            'RegisterUrl' => ['LabelCode' => 'Register Url', 'Description' => 'Enter the endpoint to direct a user to register.'],
            'SignOutUrl' => ['LabelCode' => 'Sign Out Url', 'Description' => 'Enter the endpoint to direct a user to sign out.'],
            'UseFirebaseUI' => ['LabelCode' => 'Use Firebase UI', 'Description' => 'Check this if you want Firebase buttons and/or email/password interfaces on the forum to connect users.', 'Control' => 'toggle'],
            'TermsUrl' => ['LabelCode' => 'Terms of Service URL', 'Description' => 'URL to your Terms of Service'],
            'GoogleAuthProvider' => ['LabelCode' => 'Google Auth Provider', 'Control' => 'toggle', 'Description' => 'Allow users to sign in with their Google identities.'],
            'FacebookAuthProvider' => ['LabelCode' => 'Facebook Auth Provider', 'Control' => 'toggle', 'Description' => 'Allow users to sign in with their Facebook identities.'],
            'TwitterAuthProvider' => ['LabelCode' => 'Twitter Auth Provider', 'Control' => 'toggle', 'Description' => 'Allow users to sign in with their Twitter identities.'],
            'GithubAuthProvider' => ['LabelCode' => 'GitHub Auth Provider', 'Control' => 'toggle', 'Description' => 'Allow users to sign in with their GitHub identities.'],
            'EmailAuthProvider' => ['LabelCode' => 'Email/Password Authentication', 'Control' => 'toggle', 'Description' => 'Allow users to sign in with their Email and Password.'],
            'IsDefault' =>  ['LabelCode' => 'Make this connection your default signin method.', 'Control' => 'toggle']
        ];

        $sender->setData('_Form', $formFields);

        $sender->setHighlightRoute();
        if (!$sender->data('Title')) {
            $sender->setData('Title', sprintf(t('%s Settings'), 'Firebase SSO'));
        }

        $view = ($this->settingsView) ? $this->settingsView : 'plugins/oauth2';

        // Create and send the possible redirect URLs that will be required by the authenticating server and display them in the dashboard.
        // Use Gdn::Request instead of convience function so that we can return http and https.
        $redirectUrls = Gdn::request()->url('/entry/'. $this->getProviderKey(), true, true);
        $sender->setData('redirectUrls', $redirectUrls);

        $sender->render('settings', '', $view);
    }

    /**
     * Recieve the user's data via an AJAX post returned from Firebase SDK and save it to the session.
     *
     * @param EntryController $sender
     * @throws Gdn_UserException
     * @return true;
     */
    public function entryEndpoint($sender, $code, $state = '') {
        if ($error = $sender->Request->get('error')) {
            throw new Gdn_UserException($error);
        }

        Gdn::session()->stash($this->getProviderKey()); // remove any stashed provider data.

        /* @var Gdn_Form $form */
        $form = $sender->Form; //new gdn_Form();

        $rawProfile = $form->formValues();
        $profile = $this->translateProfileResults($rawProfile['providerData'][0]);

        // Once the profile data has been saved to the session, Firebase SDK will redirect the browser to the connect page.
        Gdn::session()->stash($this->getProviderKey(), ['Profile' => $profile]);
        return true;
    }

    /**
     * Translate the keys of the profile data being sent from Firebase to match the keys Vanilla uses.
     *
     * @param array $rawProfile profile as it is returned from the provider.
     * @return array Profile array transformed by child class or as is.
     */
    public function translateProfileResults($rawProfile = []) {
        $translatedKeys = [
            'email' => 'Email',
            'photoURL' => 'Photo',
            'displayName' => 'Name',
            'name' => 'FullName',
            'uid' => 'UniqueID',
        ];
        $profile = self::translateArrayMulti($rawProfile, $translatedKeys, true);
        $profile['Provider'] = $this->providerKey;
        return $profile;
    }

    /**
     * Check if the plugin is configured.
     *
     * @return bool True if it is configured, otherwise false.
     */
    public function isConfigured($provider = false) {
        if ($provider === false) {
            $provider = $this->provider();
        }

        if (!$provider) {
            return false;
        }

        if (!val('AssociationKey', $provider) || !val('AuthenticateUrl', $provider)) {
            return false;
        }
        return true;
    }

    /**
     * Get the third-party providers that Firebase is configured to authenticate against (e.g. Facebook, GitHub, Google, etc.).
     *
     * @param bool $provider
     * @return array|bool False if no provider was passed, or an array of third-party providers.
     */
    public function configuredProviders($provider = false) {
        if (!$provider) {
            return false;
        }
        $authProvidersConfigured = [];
        foreach ($this->availableAuthProviders as $authProvider) {
            if (val($authProvider, $provider)) {
                $authProvidersConfigured[] = $authProvider;
            }
        }
        return $authProvidersConfigured;
    }
}

//

if (!function_exists('signInUrl')) {
    /**
     * Don't use Vanilla's native signin buttons if you are using the Firebase SDK.
     *
     * @param string $target
     * @param bool $force
     * @return string
     */
    function signInUrl($target = '', $force = false) {
        $firebase = new FireBasePlugin();
        // Check to see if there is even a sign in button.
        $provider = $firebase->provider();
        $configuredAuthProviders = $firebase->configuredProviders($provider);
        $isConfiguredForFirebaseUI = ($firebase->isConfigured($provider) && val('UseFirebaseUI', $provider) && $configuredAuthProviders);
        if ($isConfiguredForFirebaseUI) {
            return '/vanilla/firebasesignin'.($target ? '?Target='.urlencode($target) : '');
        }
        return '/entry/signin'.($target ? '?Target='.urlencode($target) : '');
    }
}
