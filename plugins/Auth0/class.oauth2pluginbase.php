<?php
/**
 * @copyright 2009-2015 Vanilla Forums Inc.
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

    /**
     * Set up OAuth2 access properties.
     *
     * @param string $providerKey
     * @param string $accessToken
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
     * return array
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
     * @return bool
     */
    public function isConfigured() {
        $provider = $this->provider();
        return $provider['AssociationSecret'] && $provider['AssociationKey'];
    }

    /**
     * Create the URI that can return an authorization.
     *
     * @param array $state
     * @return string
     */
    public function authorizeUri($state = array()) {
        $provider = $this->provider();

        $uri = $provider['AuthorizeUrl'];

        $redirect_uri = "/entry/".$this->getProviderKey();

        $get = [
            'response_type' => 'code',
            'client_id' => $provider['AssociationKey'],
            'redirect_uri' => url($redirect_uri, true)
        ];

        if (is_array($state)) {
            if (is_array($state)) {
                $get['state'] = http_build_query($state);
            }
        }

        return $uri.'?'.http_build_query($get);
    }

    /**
     * Generic API uses Proxy->Request.
     *
     * @param $uri
     * @param string $method
     * @param array $params
     * @param array $options
     * @return mixed|type
     * @throws Exception
     * @throws Gdn_UserException
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

        $response = $proxy->request(
            $proxyOptions,
            $params,
            null,
            $headers
        );

        // Extract response only if it arrives as JSON
        if (stripos($proxy->ContentType, 'application/json') !== false) {
            $response = json_decode($proxy->ResponseBody, true);
        }

        // Return any errors
        if (!$proxy->responseClass('2xx')) {
            if (isset($response['error'])) {
                $message = "Request server says: ".$response['error_description']." (code: ".$response['error'].")";
            } else {
                $message = 'HTTP Error communicating Code: '.$proxy->ResponseStatus;
            }

            throw new Gdn_UserException($message, $proxy->ResponseStatus);
        }

        return $response;
    }

    /**
     * Check if an access token has been returned from the provider server.
     *
     * @return bool
     */
    public function isConnected() {
        if (!$this->accessToken) {
            return false;
        }
        return TRUE;
    }

    /**
     * Renew or return access token.
     *
     * @param bool $newValue
     * @return bool|mixed|null
     */
    public function accessToken($newValue = false) {
        if (!$this->isConfigured()) {
            return false;
        }

        if ($newValue !== false) {
            $this->accessToken = $newValue;
        }

        if ($this->accessToken === null) {
            $this->accessToken = valr($this->getProviderKey().'.AccessToken', Gdn::session()->User->Attributes);
        }

        return $this->accessToken;
    }

    /**
     * Reestablish a valid token session using refresh_token.
     *
     * @throws Gdn_UserException
     * @see Refresh()
     */
//    public function reconnect() {
//            $response = $this->refresh($this->refreshToken);
//            if (!$response) {
//                return false;
//            }
//
//            // Update user connection.
//            $profile = valr('Attributes.'.$this->getProviderKey().'.Profile', Gdn::Session()->User);
//            $Attributes = array(
//                'RefreshToken' => $this->refreshToken,
//                'AccessToken' => $response['access_token'],
//                'InstanceUrl' => $response['instance_url'],
//                'Profile' => $profile,
//            );
//
//            Gdn::UserModel()->SaveAttribute(Gdn::Session()->UserID, $this->getProviderKey(), $Attributes);
//            $this->setAccessToken($response['access_token']);
//            $this->setInstanceUrl($response['instance_url']);
//    }

    /**
     * Revoke an access token.
     *
     * @param $Token
     * @return bool
     * @throws Gdn_UserException
     */
//    public function revoke($token) {
//        $Response = $this->HttpRequest(C('Plugins.Salesforce.AuthenticationUrl').'/services/oauth2/revoke?token='.$token);
//        if ($Response['HttpCode'] == 200) {
//            return TRUE;
//        }
//        return false;
//    }

    /**
     * Sends refresh_token to API and simply returns the response.
     *
     * @param $Token
     * @return bool|mixed On success, returns entire API response.
     * @throws Gdn_UserException
     * @see Reconnect() is probably what you want.
     */
//    public function refresh($token) {
//        $provider = $this->provider();
//        if($provider['TokenRequestEndpoint']) {
//            $refreshUri = $provider['TokenRequestEndpoint'];
//        } else {
//            $refreshUri = $provider['BaseUrl'].'/oath/token';
//        }
//        $response = $this->httpRequest(
//            $refreshUri,
//            array(
//                'grant_type' => 'refresh_token',
//                'client_id' => $provider['AssociationKey'],
//                'client_secret' => $provider['AssociationSecret'],
//                'refresh_token' => $token
//            )
//        );
//
//        if ($response['HttpCode'] == 400) {
//            throw new Gdn_UserException('Someone has Revoked your Connection.  Please reconnect manually,');
//            return false;
//        }
//        if (strpos($response['ContentType'], 'application/json') !== false) {
//            $refreshResponse = json_decode($response['Response'], true);
//
//            return $refreshResponse;
//        }
//        return false;
//    }

    /**
     * Request access token from provider.
     *
     * @param string $code code returned from initial handshake with provider
     * @return mixed
     */
    public function requestAccessToken($code) {
        $provider = $this->provider();

        $uri = $provider['TokenUrl'];

        $post = array(
            'code' => $code,
            'client_id' => $provider['AssociationKey'],
            'redirect_uri' => url('/entry/'. $this->getProviderKey(), true),
            'client_secret' => $provider['AssociationSecret'],
            'grant_type' => 'authorization_code'
        );

        return $this->api($uri, 'POST', $post);
    }

    /**
     * Set access token received from provider.
     *
     * @param string $accessToken
     * @return $this
     */
    public function setAccessToken($accessToken) {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * Set provider key used to access settings stored in GDN_UserAuthenticationProvider.
     *
     * @param string $providerKey
     * @return $this
     */
    public function setProviderKey($providerKey) {
        $this->providerKey = $providerKey;
        return $this;
    }

    /**
     * Set scope to be passed to provider.
     *
     * @param string $scope
     * @return $this
     */
    public function setScope($scope) {
        $this->scope = $scope;
        return $this;
    }

    /**
     * Create a controller to deal with plugin settings in dashboard.
     *
     * @param Gdn_Controller $sender
     * @param array $args arguments passed from sender
     */
    public function settingsController_oAuth2_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');

        $model = new Gdn_AuthenticationProviderModel();

        $form = new Gdn_Form();
        $form->setModel($model);
        $sender->form = $form;

        if (!$form->AuthenticatedPostBack()) {
            $provider = Gdn_AuthenticationProviderModel::GetProviderByKey($this->getProviderKey());
            $form->setData($provider);
        } else {
            $form->setFormValue('AuthenticationKey', $this->getProviderKey());
            $form->setFormValue('SignInUrl', '...'); // kludge for default provider

            if ($form->Save()) {
                $sender->informMessage(T('Saved'));
            }
        }

        // Set up the form.
        $_form = $this->getSettingsFormFields();
        $_form['IsDefault'] = ['LabelCode' => 'Make this connection your default signin method.', 'Control' => 'checkbox'];


        $sender->setData('_form', $_form);

        $sender->addSideMenu();
        if (!$sender->data('Title')) {
            $sender->setData('Title', sprintf(T('%s Settings'), 'Oauth2 SSO'));
        }

        $sender->render('settings', '', 'plugins/'.$this->getProviderKey());
    }

    /**
     * Allow child plugins to over-ride or add form fields to settings.
     *
     * @return array
     */
    protected function getSettingsFormFields() {
        $_form = array(
            'AssociationKey' => ['LabelCode' => 'Client ID', 'Options' => ['Class' => 'InputBox BigInput'], 'Description' => ''],
            'AssociationSecret' => ['LabelCode' => 'Secret', 'Options' => ['Class' => 'InputBox BigInput'], 'Description' => '']
        );
        return $_form;
    }

    /**
     * Inject into the process of the base connection.
     *
     * @param Gdn_Controller $sender
     * @param array $args arguments passed from sender
     */
    public function base_connectData_handler($sender, $args) {
        if (val(0, $args) != $this->getProviderKey()) {
            return;
        }

        // Retrieve the profile that was saved to the session in the entry controller.
        $savedProfile = Gdn::Session()->stash($this->getProviderKey(), '', false);

        $profile = val('Profile', $savedProfile);
        $accessToken = val('AccessToken', $savedProfile);

        /* @var Gdn_Form $form */
        $form = $sender->Form; //new Gdn_Form();

        // Create a form and populate it with values from the profile.
        $originaFormValues = $form->formValues();
        $formValues = array_replace($originaFormValues, $profile);
        $form->formValues($formValues);

        // Save some original data in the attributes of the connection for later API calls.
        $attributes = array();
        $attributes[$this->getProviderKey()] = array(
            'AccessToken' => $accessToken,
            'Profile' => $profile
        );
        $form->setFormValue('Attributes', $attributes);

        $this->EventArguments['Profile'] = $profile;
        $this->EventArguments['Form'] = $form;

        // Throw an event so that other plugins can add/remove stuff from the basic sso.
        $sender->fireEvent('OAuth');

        SpamModel::disabled(TRUE);
        $sender->setData('Trusted', TRUE);
        $sender->setData('Verified', TRUE);
    }

    /**
     * Inject a sign-in icon into the ME menu
     *
     * @param Gdn_Controller $sender
     * @param array $args arguments passed from sender
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
     * @return bool
     */
    public function isDefault() {
        $provider = $this->provider();
        return $provider['IsDefault'];
    }

    /**
     * Inject sign-in button into the sign in page.
     *
     * @param Gdn_Controller $sender
     * @param array $args arguments passed from sender
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
     * @param Gdn_Controller $sender
     * @param array $args arguments passed from sender
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
     * Redirect to provider's register page if this is the default behaviour.
     *
     * @param Gdn_Controller $sender
     * @param array $args arguments passed from sender
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
     * @param Gdn_Controller $sender
     * @param $code
     * @param $state
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function entryController_oAuth2_create($sender, $code, $state) {
        if ($error = $sender->request->get('error')) {
            throw new Gdn_UserException($error);
        }

        Gdn::session()->stash($this->getProviderKey()); // remove any stashed.

        $response = $this->requestAccessToken($code);
        if (!$response) {
            throw new Gdn_UserException('The OAuth server did not return a valid response.');
        }

        if (!empty($response['error'])) {
            throw new Gdn_UserException($response->error, $response->error_description);
        } elseif (empty($response['access_token'])) {
            throw new Gdn_UserException("The OAuth server did not return an access token.", 400);
        } else {
            $this->accessToken($response['access_token']);
        }

        $profile = $this->getProfile();

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

                // This is an sso request, we need to redispatch to /entry/connect/Auth0
                Gdn::session()->stash($this->getProviderKey(), array('AccessToken' => $response['access_token'], 'Profile' => $profile));
                $url = '/entry/connect/'.$this->getProviderKey(); // to see this page got to Base_ConnectData_Handler() in this class
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
     * @param array $rawProfle profile as it is returned from the provider
     * @return array
     */
    public function translateProfileResults($rawProfle = array()) {
        return $rawProfle;
    }

    /**
     * Get profile data from authentication provider through API.
     *
     * @return array user profile from provider
     */
    public function getProfile() {
        $provider = $this->provider();

        $uri = $this->requireVal('ProfileUrl', $provider, "provider");

        $uri .= (strpos($uri, '?') === false) ? "?" : "&";

        $uri .= "access_token=".urlencode($this->accessToken());

        $rawProfile = $this->api($uri);
        $profile = $this->translateProfileResults($rawProfile);
        return $profile;
    }

    /**
     * Extract values from arrays.
     *
     * @param string $key needle
     * @param array $arr haystack
     * @param string $context pass context to error message
     * @return mixed
     * @throws Exception
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
     * @return string provider key
     */
    public function getProviderKey() {
        return $this->providerKey;
    }

}
