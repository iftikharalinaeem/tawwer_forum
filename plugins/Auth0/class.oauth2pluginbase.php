<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */


/**
 * Class OAuth2PluginBase
 *
 * Base class to be extended by any plugin that wants to use Oauth2 protocol for SSO.
 * Will eventually be moved to a library that will be included by composer.
 */
class OAuth2PluginBase {

    /** @var string token provider by authenticator  */
    protected $accessToken;

    /** @var string key for GDN_UserAuthenticationProvider table  */
    protected $providerKey = null;

    /** @var  string passing scope to authenticator */
    protected $scope;

    /** @var string content type for API calls */
    protected $defaultContentType = 'application/x-www-form-urlencoded';

    /** @var array stored information to connect with provider (secret, etc.) */
    protected $provider = [];

    /** @var array optional additional get parameters to be passed in the authorize_uri */
    protected $authorizeUriParams = [];

    /** @var array optional additional post parameters to be passed in the accessToken request */
    protected $requestAccessTokenParams = [];

    /** @var array optional additional get params to be passed in the request for profile */
    protected $getProfileParams = [];
    /**
     * Set up OAuth2 access properties.
     *
     * @param string $providerKey Fixed key set in child class.
     * @param bool|string $accessToken Provided by the authentication provider.
     */
    public function __construct($providerKey, $accessToken = false) {
        $this->providerKey = $providerKey;
        $this->provider = provider();
        if ($accessToken) {
            // We passed in a connection
            $this->accessToken = $accessToken;
        }
    }

    public function setup() {

    }

    /**
     *  Return all the information saved in provider table.
     *
     * @return array Stored provider data (secret, client_id, etc.).
     */
    public function provider() {
        if (!$this->provider) {
            $this->provider = Gdn_AuthenticationProviderModel::GetProviderByKey($this->providerKey);
        }
        return $this->provider;
    }

    /**
     * Check if there is enough data to connect to an authentication provider.
     *
     * @return bool True if there is a secret and a client_id, false if not.
     */
    public function isConfigured() {
        $provider = $this->provider();
        return $provider['AssociationSecret'] && $provider['AssociationKey'];
    }

    /**
     * Create the URI that can return an authorization.
     *
     * @param array $state Optionally provide an array of variables to be sent to the provider.
     *
     * @return string Endpoint of the provider.
     */
    public function authorizeUri($state = array()) {
        $provider = $this->provider();

        $uri = $provider['AuthorizeUrl'];

        $redirect_uri = "/entry/".$this->getProviderKey();

        $defaultParams = [
            'response_type' => 'code',
            'client_id' => $provider['AssociationKey'],
            'redirect_uri' => url($redirect_uri, true),
            'scope' => $this->scope
        ];
        // allow child class to overwrite or add to the authorize URI.
        $get = array_merge($defaultParams, $this->authorizeUriParams);

        if (is_array($state)) {
            if (is_array($state)) {
                $get['state'] = http_build_query($state);
            }
        }

        return $uri.'?'.http_build_query($get);
    }

    /**
     * Generic API uses ProxyRequest class to fetch data from remote endpoints.
     *
     * @param $uri Endpoint on provider's server.
     * @param string $method HTTP method required by provider.
     * @param array $params Query string.
     * @param array $options Configuration options for the request (e.g. Content-Type).
     *
     * @return mixed|type.
     *
     * @throws Exception.
     * @throws Gdn_UserException.
     */
    protected function api($uri, $method = 'GET', $params = [], $options = []) {
        $proxy = new ProxyRequest();

        // Create default values of options to be passed to ProxyRequest.
        $defaultOptions['ConnectTimeout'] = 10;
        $defaultOptions['Timeout'] = 10;

        $headers = [];

        // Optionally over-write the content type
        if ($contentType = val('Content-Type', $options, $this->defaultContentType)) {
            $headers['Content-Type'] = $contentType;
        }

        // Obtionally add proprietary required Authorization headers
        if ($headerAuthorization = val('Authorization-Header-Message', $options, null)) {
            $headers['Authorization'] = $headerAuthorization;
        }

        // Merge the default options with the passed options over-writing default options with passed options.
        $proxyOptions = array_merge($defaultOptions, $options);

        $proxyOptions['URL'] = $uri;
        $proxyOptions['Method'] = $method;

        $this->log('Proxy Request Sent in API', array('headers' => $headers, 'proxyOptions' => $proxyOptions, 'params' => $params));
        $response = $proxy->request(
            $proxyOptions,
            $params,
            null,
            $headers
        );

        // Extract response only if it arrives as JSON
        if (stripos($proxy->ContentType, 'application/json') !== false) {
            $this->log('API JSON Response', array('response' => $response));
            $response = json_decode($proxy->ResponseBody, true);
        }

        // Return any errors
        if (!$proxy->responseClass('2xx')) {
            if (isset($response['error'])) {
                $message = "Request server says: ".$response['error_description']." (code: ".$response['error'].")";
            } else {
                $message = 'HTTP Error communicating Code: '.$proxy->ResponseStatus;
            }
            $this->log('API Response Error Thrown', array('response' => json_decode($response)));
            throw new Gdn_UserException($message, $proxy->ResponseStatus);
        }

        return $response;
    }

    /**
     * Check if an access token has been returned from the provider server.
     *
     * @return bool True of there is an accessToken, fals if there is not.
     */
    public function isConnected() {
        if (!$this->accessToken) {
            return false;
        }
        return true;
    }

    /**
     * Renew or return access token.
     *
     * @param bool|string $newValue Pass existing token if it exists.
     *
     * @return bool|string|null String if there is an accessToken passed or found in session, false or null if not.
     */
    public function accessToken($newValue = false) {
        if (!$this->isConfigured() && $newValue === false) {
            return false;
        }

        if ($newValue !== false) {
            $this->accessToken = $newValue;
        }

        // If there is no token passed, try to retrieve one from the user's attributes.
        if ($this->accessToken === null) {
            $this->accessToken = valr($this->getProviderKey().'.AccessToken', Gdn::session()->User->Attributes);
        }

        return $this->accessToken;
    }

    /**
     * Request access token from provider.
     *
     * @param string $code code returned from initial handshake with provider.
     *
     * @return mixed Result of the API call to the provider, usually JSON.
     */
    public function requestAccessToken($code) {
        $provider = $this->provider();
        $uri = $provider['TokenUrl'];

        $defaultParams = array(
            'code' => $code,
            'client_id' => $provider['AssociationKey'],
            'redirect_uri' => url('/entry/'. $this->getProviderKey(), true),
            'client_secret' => $provider['AssociationSecret'],
            'grant_type' => 'authorization_code',
            'scope' => $this->scope
        );

        $post = array_merge($defaultParams, $this->requestAccessTokenParams);

        $this->log('Before calling API to request access token', array('requestAccessToken' => array('targetURI' => $uri, 'post' => $post)));

        return $this->api($uri, 'POST', $post);
    }

    /**
     * Set access token received from provider.
     *
     * @param string $accessToken Retrieved from provider to authenticate communication.
     *
     * @return $this Return this object for chaining purposes.
     */
    public function setAccessToken($accessToken) {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * Set getProfile params received from provider.
     *
     * @param string $accessToken Retrieved from provider to authenticate communication.
     *
     * @return $this Return this object for chaining purposes.
     */
    public function setGetProfile($accessToken) {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * Set provider key used to access settings stored in GDN_UserAuthenticationProvider.
     *
     * @param string $providerKey Key to retrieve provider data hardcoded into child class.
     *
     * @return $this Return this object for chaining purposes.
     */
    public function setProviderKey($providerKey) {
        $this->providerKey = $providerKey;
        return $this;
    }

    /**
     * Set scope to be passed to provider.
     *
     * @param string $scope.
     *
     * @return $this Return this object for chaining purposes.
     */
    public function setScope($scope) {
        $this->scope = $scope;
        return $this;
    }

    /**
     * Set additional params to be added to the get string in the AuthorizeUri string.
     *
     * @param string $params.
     *
     * @return $this Return this object for chaining purposes.
     */
    public function setAuthorizeUriParams($params) {
        $this->authorizeUriParams = $params;
        return $this;
    }

    /**
     * Set additional params to be added to the post array in the accessToken request.
     *
     * @param string $params.
     *
     * @return $this Return this object for chaining purposes.
     */
    public function setRequestAccessTokenParams($params) {
        $this->requestAccessTokenParams = $params;
        return $this;
    }


    /**
     * Set additional params to be added to the get string in the getProfile request.
     *
     * @param string $params.
     *
     * @return $this Return this object for chaining purposes.
     */
    public function setGetProfileParams($params) {
        $this->getProfileParams = $params;
        return $this;
    }

    /**
     * Create a controller to deal with plugin settings in dashboard.
     *
     * @param Gdn_Controller $sender.
     * @param Gdn_Controller $args.
     */
    public function settingsController_oAuth2_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');
        $model = new Gdn_AuthenticationProviderModel();

        /* @var Gdn_Form $form */
        $form = new Gdn_Form();
        $form->setModel($model);
        $sender->Form = $form;

        if (!$form->AuthenticatedPostBack()) {
            $provider = Gdn_AuthenticationProviderModel::GetProviderByKey($this->getProviderKey());
            $form->setData($provider);
        } else {
            $sender->Form->validateRule("BaseUrl", "isUrl", "You must provide a complete URL in the Domain field.");

            $form->setFormValue('AuthenticationKey', $this->getProviderKey());
            $form->setFormValue('SignInUrl', '...'); // kludge for default provider

            //Make sure we store a complete url.
            if (preg_match("#https://#", $form->getValue("BaseUrl")) === 0) {
                $form->setFormValue("BaseUrl", 'https://'. str_replace("http://", "", $form->getValue("BaseUrl")));
            }
            if ($form->Save()) {
                $sender->informMessage(T('Saved'));
            }
        }

        // Set up the form.
        $formFields = $this->getSettingsFormFields();
        $formFields['IsDefault'] = ['LabelCode' => 'Make this connection your default signin method.', 'Control' => 'checkbox'];


        $sender->setData('_Form', $formFields);

        $sender->addSideMenu();
        if (!$sender->data('Title')) {
            $sender->setData('Title', sprintf(T('%s Settings'), 'Oauth2 SSO'));
        }
        $sender->render('settings', '', 'plugins/'.$this->getProviderKey());
    }

    /**
     * Allow child class to over-ride or add form fields to settings.
     *
     * @return array Form fields to appear in settings dashboard.
     */
    protected function getSettingsFormFields() {
        $formFields = array(
            'AssociationKey' => ['LabelCode' => 'Client ID', 'Description' => ''],
            'AssociationSecret' => ['LabelCode' => 'Secret', 'Description' => ''],
            'SignOutRedirect' => ['LabelCode' => t('Sign Out URL'), 'Description' => t('Enter the <b><i>full</i></b> URL (i.e. with "https://") to where you want users to be redirected wherever they click on the logout button.')]
        );
        return $formFields;
    }

    /**
     * Inject into the process of the base connection.
     *
     * @param Gdn_Controller $sender.
     * @param Gdn_Controller $args.
     */
    public function base_connectData_handler($sender, $args) {
        if (val(0, $args) != $this->getProviderKey()) {
            return;
        }

        // Retrieve the profile that was saved to the session in the entry controller.
        $savedProfile = Gdn::Session()->stash($this->getProviderKey(), '', false);
        if(Gdn::session()->stash($this->getProviderKey(), '', false)) {
            $this->log('Base Connect Data Profile Saved in Session', array('profile' => $savedProfile));
        }
        $profile = val('Profile', $savedProfile);
        $accessToken = val('AccessToken', $savedProfile);
        trace($profile, "Profile");
        trace($accessToken, "Access Token");
        /* @var Gdn_Form $form */
        $form = $sender->Form; //new Gdn_Form();

        // Create a form and populate it with values from the profile.
        $originaFormValues = $form->formValues();
        $formValues = array_replace($originaFormValues, $profile);
        $form->formValues($formValues);
        trace($formValues, "Form Values");
        // Save some original data in the attributes of the connection for later API calls.
        $attributes = array();
        $attributes[$this->getProviderKey()] = array(
            'AccessToken' => $accessToken,
            'Profile' => $profile
        );
        $form->setFormValue('Attributes', $attributes);

        $sender->EventArguments['Profile'] = $profile;
        $sender->EventArguments['Form'] = $form;

        $this->log('Base Connect Data Before OAuth Event', array('profile' => $profile, 'form' => $form));
        // Throw an event so that other plugins can add/remove stuff from the basic sso.
        $sender->fireEvent('OAuth');

        SpamModel::disabled(TRUE);
        $sender->setData('Trusted', TRUE);
        $sender->setData('Verified', TRUE);
    }

    /**
     * Inject a sign-in icon into the ME menu.
     *
     * @param Gdn_Controller $sender.
     * @param Gdn_Controller $args.
     */
    public function base_beforeSignInButton_handler($sender, $args) {
        if(!$this->isConfigured() || $this->isDefault()) {
            return;
        }

        echo ' '.$this->signInButton('icon').' ';
    }

    /**
     * Check authentication provider table to see if this is the default method for logging in.
     *
     * @return bool Return the value of the IsDefault row of GDN_UserAuthenticationProvider .
     */
    public function isDefault() {
        $provider = $this->provider();
        return $provider['IsDefault'];
    }

    /**
     * Inject sign-in button into the sign in page.
     *
     * @param EntryController $sender.
     * @param EntryController $args.
     *
     * @return mixed|bool Return null if not configured
     */
    public function entryController_signIn_handler($sender, $args) {
        if(!$this->isConfigured()) {
            return;
        }

        if (isset($sender->Data['Methods'])) {
            // Add the sign in button method to the controller.
            $method = array(
                'Name' => $this->getProviderKey(),
                'SignInHtml' => $this->signInButton()
            );

            $sender->Data['Methods'][] = $method;
        }
    }

    /**
     * Redirect to provider's signin page if this is the default behaviour.
     *
     * @param EntryController $sender.
     * @param EntryController $args.
     *
     * @return mixed|bool Return null if not configured.
     */
    public function entryController_overrideSignIn_handler($sender, $args) {
        $provider = $args['DefaultProvider'];
        if ($provider['AuthenticationSchemeAlias'] != $this->getProviderKey() || !$this->isConfigured()) {
            return;
        }

        $url = $this->authorizeUri(array('target' => $args['Target']));
        $args['DefaultProvider']['SignInUrl'] = $url;
    }


    /**
     * Redirect to provider's sign out page if this is the default behaviour.
     *
     * @param EntryController $sender.
     * @param EntryController $args.
     *
     * @return mixed|bool Return null if not configured, signout url if it is.
     */
    public function entryController_overrideSignOut_handler($sender, $args) {
        $provider = $this->provider();
        if ($provider['AuthenticationSchemeAlias'] != $this->getProviderKey() || !$this->isConfigured() || $provider['SignOutUrl'] === null) {
            return;
        }

        $returnTo = parse_url(urldecode(gdn::request()->get('Target')));
        if (val('path', $returnTo)) {
            if (c('Auth0.SignOutRedirect.FullPath')) {
                $passedUrl = urldecode(Gdn::request()->get('Target'));
            } else {
                $passedUrl = val('scheme', $returnTo, 'http').'://'. val('host', $returnTo, Gdn::request()->url('/', true));
            }
        }

        // if the client has explicitly overwritten the signout redirect url...
        $redirect = val('SignOutRedirect', $provider, $passedUrl);

        $url = $provider['SignOutUrl'];
        $args['DefaultProvider']['SignOutUrl'] = $url . "?returnTo=" . urlencode($redirect);
    }


    /**
     * Redirect to provider's register page if this is the default behaviour.
     *
     * @param EntryController $sender.
     * @param EntryController $args.
     *
     * @return mixed|bool Return null if not configured.
     */
    public function entryController_overrideRegister_handler($sender, $args) {
        $provider = $args['DefaultProvider'];
        if ($provider['AuthenticationSchemeAlias'] != $this->getProviderKey() || !$this->isConfigured()) {
            return;
        }

        $url = $this->authorizeUri(array('target' => $args['Target']));
        $args['DefaultProvider']['RegisterUrl'] = $url;
    }

    /**
     * Create a controller to handle entry request.
     *
     * @param Gdn_Controller $sender.
     * @param $code string Retrieved from the response of the authentication provider, used to fetch an authentication token.
     * @param $state string Values passed by us and returned in the response of the authentication provider.
     *
     * @throws Exception.
     * @throws Gdn_UserException.
     */
    public function entryController_oAuth2_create($sender, $code, $state) {
        $this->log('entryController_oAuth2_create', array());
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

        $this->log('Getting Profile', array());
        $profile = $this->getProfile();
        $this->log('Profile', $profile);

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
     * Allow child plugins to translate the keys that are returned in profile from the provider into our keys.
     *
     * @param array $rawProfile profile as it is returned from the provider.
     *
     * @return array Profile array transformed by child class or as is.
     */
    public function translateProfileResults($rawProfile = array()) {
        return $rawProfile;
    }

    /**
     * Get profile data from authentication provider through API.
     *
     * @return array User profile from provider.
     */
    public function getProfile() {
        $provider = $this->provider();

        $uri = $this->requireVal('ProfileUrl', $provider, "provider");

        $defaultParams = array(
            'access_token' => $this->accessToken()
        );

        $get = array_merge($defaultParams, $this->getProfileParams);

        $rawProfile = $this->api($uri, 'GET', $get);

        $profile = $this->translateProfileResults($rawProfile);

        $this->log('getProfile API call', array('ProfileUrl' => $uri, 'Params' => $get, 'RawProfile' => $rawProfile, 'Profile' => $profile));

        return $profile;
    }

    /**
     * Extract values from arrays.
     *
     * @param string $key Needle.
     * @param array $arr Haystack.
     * @param string $context Context to make error messages clearer.
     *
     * @return mixed Extracted value from array.
     *
     * @throws Exception.
     */
    function requireVal($key, $arr, $context = null) {
        $result = val($key, $arr);
        if (!$result) {
            throw new \Exception("Key $key missing from $context collection.", 500);
        }
        return $result;
    }

    /**
     *  Get provider key.
     *
     * @return string Provider key.
     */
    public function getProviderKey() {
        return $this->providerKey;
    }

    public function log($message, $data) {
        if(c('Vanilla.SSO.Debug')) {
            Logger::event(
                'sso_logging',
                Logger::INFO,
                $message,
                $data
            );
        }
    }
}
