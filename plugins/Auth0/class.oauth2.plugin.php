<?php
/**
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */


class OAuth2Plugin {

    /**
     * @var string OAuth Access Token
     */
    protected $accessToken;

    protected $providerKey = null;

    protected $scope;

    protected $configurationModule;

    protected $defaultContentType = 'application/x-www-form-urlencoded';

    /**
     * @var array
     */
    protected $provider = [];

    /**
     * Set up OAuth2 access properties.
     *
     * @param bool $accessToken
     * @param bool $InstanceUrl
     */
    public function __construct($providerKey, $accessToken = FALSE, $InstanceUrl = FALSE) {
        $this->providerKey = $providerKey;
        $this->provider = provider();
        if ($accessToken) {
            // We passed in a connection
            $this->AccessToken = $accessToken;
        }
    }

    public function setup() {

    }

    /**
     *  Return all the information saved in provider table
     */
    public function provider() {
        if (!$this->provider) {
            $this->provider = Gdn_AuthenticationProviderModel::GetProviderByKey($this->providerKey);
        }
        return $this->provider;
    }

    /**
     * @return bool
     */
    public function isConfigured() {
        $provider = $this->provider();
        return $provider['AssociationSecret'] && $provider['AssociationKey'];
    }

    /**
     * Create the URI that can return an authorization
     *
     * @param $class_name
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
     * Generic API call used to authenticate
     * @param $uri
     * @param string $method
     * @param array $params
     * @param array $options
     * @return mixed|type
     * @throws Exception
     * @throws Gdn_UserException
     */
    protected function api($uri, $method = 'GET', $params = [], $options = []) {
        $Proxy = new ProxyRequest();

        $defaultOptions['ConnectTimeout'] = 10;
        $defaultOptions['Timeout'] = 10;

        $headers = [];

        if ($contentType = val('Content-Type', $options, $this->defaultContentType)) {
            $headers['Content-Type'] = $contentType;
        }

        if ($headerAuthorization = val('Authorization-Header-Message', $options, null)) {
            $headers['Authorization'] = $headerAuthorization;
        }

        $proxyOptions = array_merge($defaultOptions, $options);

        $proxyOptions['URL'] = $uri;
        $proxyOptions['Method'] = $method;

        $response = $Proxy->Request(
            $proxyOptions,
            $params,
            null,
            $headers
        );

        if (stripos($Proxy->ContentType, 'application/json') !== false) {
            $response = json_decode($Proxy->ResponseBody, true);
        }

        if (!$Proxy->responseClass('2xx')) {
            if (isset($response['error'])) {
                $message = "Request server says: ".$response['error_description']." (code: ".$response['error'].")";
            } else {
                $message = 'HTTP Error communicating Code: '.$Proxy->ResponseStatus;
            }

            throw new Gdn_UserException($message, $Proxy->ResponseStatus);
        }

        return $response;
    }

    /**
     * @return bool
     */
    public function isConnected() {
        if (!$this->accessToken || !$this->instanceUrl) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Renew or return access token
     *
     * @param bool $NewValue
     * @return bool|mixed|null
     */
    public function accessToken($NewValue = false) {
        if (!$this->isConfigured()) {
            return false;
        }

        if ($NewValue !== false) {
            $this->accessToken = $NewValue;
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
//        return FALSE;
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
     * Request access token from provider
     * @param $code
     * @return mixed
     * @throws Gdn_UserException
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
     * Set Access Token
     * @param $accessToken
     * @return $this
     */
    public function setAccessToken($accessToken) {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * Set Provider Key
     * @param $providerKey
     * @return $this
     */
    public function setProviderKey($providerKey) {
        $this->providerKey = $providerKey;
        return $this;
    }

    /**
     * Set Scope
     * @param $scope
     * @return $this
     */
    public function setScope($scope) {
        $this->scope = $scope;
        return $this;
    }

    public function settingsController_oAuth2_Create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');

        $model = new Gdn_AuthenticationProviderModel();

        $form = new Gdn_Form();
        $form->SetModel($model);
        $sender->Form = $form;

        if (!$form->AuthenticatedPostBack()) {
            $provider = Gdn_AuthenticationProviderModel::GetProviderByKey($this->getProviderKey());
            $form->SetData($provider);
        } else {
            $form->SetFormValue('AuthenticationKey', $this->getProviderKey());
            $form->setFormValue('SignInUrl', '...'); // kludge for default provider
//            $form->setFormValue('RegisterUrl', '...'); // kludge for default provider

            if ($form->Save()) {
                $sender->informMessage(T('Saved'));
            }
        }

        // Set up the form.
        $_Form = $this->getSettingsFormFields();
        $_Form['IsDefault'] = ['LabelCode' => 'Make this connection your default signin method.', 'Control' => 'checkbox'];


        $sender->setData('_Form', $_Form);

        $sender->addSideMenu();
        if (!$sender->data('Title')) {
            $sender->setData('Title', sprintf(T('%s Settings'), 'Oauth2 SSO'));
        }

        $sender->render('settings', '', 'plugins/'.$this->getProviderKey());
    }

    /**
     * Allow child plugins to over-ride or add form fields to settings
     * @return array
     */
    protected function getSettingsFormFields() {
        $_Form = array(
            'AssociationKey' => ['LabelCode' => 'Client ID', 'Options' => ['Class' => 'InputBox BigInput'], 'Description' => ''],
            'AssociationSecret' => ['LabelCode' => 'Secret', 'Options' => ['Class' => 'InputBox BigInput'], 'Description' => '']
        );
        return $_Form;
    }

    /**
     * Inject into the process of the base connection
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_connectData_handler($sender, $args) {
        if (val(0, $args) != $this->getProviderKey()) {
            return;
        }
        $savedProfile = Gdn::Session()->Stash($this->getProviderKey(), '', false);

        $profile = val('Profile', $savedProfile);
        $accessToken = val('AccessToken', $savedProfile);

        /* @var Gdn_Form $form */
        $form = $sender->Form; //new Gdn_Form();

        $formValues = $form->formValues();
        $formValues = array_replace($formValues, $profile);
        $form->formValues($formValues);
        // Save some original data in the attributes of the connection for later API calls.
        $Attributes = array();
        $Attributes[$this->getProviderKey()] = array(
            'AccessToken' => $accessToken,
            'Profile' => $profile
        );
        $form->setFormValue('Attributes', $Attributes);

        $this->EventArguments['Profile'] = $profile;
        $this->EventArguments['Form'] = $form;

        // Throw an event so that other plugins can add/remove stuff from the basic sso.
        $sender->fireEvent('OAuth');

        SpamModel::Disabled(TRUE);
        $sender->setData('Trusted', TRUE);
        $sender->setData('Verified', TRUE);
    }

    /**
     *
     *
     * @param $sender
     * @param $args
     */
    public function base_beforeSignInButton_handler($sender, $args) {
        if(!$this->isConfigured() || $this->isDefault()) {
            return;
        }

        echo ' '.$this->signInButton('icon').' ';

    }

    public function isDefault() {
        $provider = $this->provider();
        return $provider['IsDefault'];

    }

    /**
     * Inject signin button into the sign in page
     *
     * @param Gdn_Controller $sender
     */
    public function entryController_signIn_handler($sender, $args) {
        if(!$this->isConfigured()) {
            return;
        }

        if (isset($sender->Data['Methods'])) {

            // Add the sign in button method to the controller.
            $Method = array(
                'Name' => $this->getProviderKey(),
                'SignInHtml' => $this->signInButton()
            );

            $sender->Data['Methods'][] = $Method;
        }
    }

    /**
     * Redirect to provider's signin page if this is the default behaviour
     *
     * @param $Sender
     * @param $Args
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
     * Redirect to provider's register page if this is the default behaviour
     *
     * @param $sender
     * @param $args
     */
    public function EntryController_OverrideRegister_Handler($sender, $args) {
        $provider = $args['DefaultProvider'];
        if ($provider['AuthenticationSchemeAlias'] != $this->getProviderKey() || !$this->isConfigured()) {
            return;
        }

        $url = $this->authorizeUri(array('target' => $args['Target']));
        $args['DefaultProvider']['RegisterUrl'] = $url;
    }

    /**
     * Create a controller to handle entry request
     *
     * @param $sender
     * @param $code
     * @param $state
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function entryController_oAuth2_create($sender, $code, $state) {

        if ($Error = $sender->Request->get('error')) {
            throw new Gdn_UserException($Error);
        }

        // Get an access token.
        Gdn::session()->stash($this->getProviderKey()); // remove any stashed.

        $response = $this->requestAccessToken($code);
        if (!$response) {
            throw new Gdn_UserException('The OAuth server did not return a valid response. line 506 ');
        }

        if (!empty($response['error'])) {
            throw new Gdn_UserException("<h3>Authentication Error Response</h3>".$response->error."<br>".$response->error_description);
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
                    'AccessToken' => $response->access_token,
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
     * Allow child plugins to transalte the keys that are returned in profile to our keys
     *
     * @param array $rawProfle
     * @return array
     */
    public function translateProfileResults($rawProfle = array()) {
        return $rawProfle;
    }

    /**
     * Get profile data from remote provider
     * @return array
     */
    public function getProfile() {
        $provider = $this->provider();

        $uri = $this->requireVal('ProfileUrl', $provider, "provider");

        if (strpos($uri, '?') === false) {
            $uri .= '?';
        } else {
            $uri .= '&';
        }

        $uri .= "access_token=".urlencode($this->accessToken());

        $rawProfile = $this->api($uri);
        $profile = $this->translateProfileResults($rawProfile);
        return $profile;
    }

    function requireVal($key, $arr, $context = null) {
        $result = val($key, $arr);
        if (!$result) {
            throw new \Exception("Key $key missing from $context collection.", 500);
        }
        return $result;
    }

    /**
     *  Get provider key
     * @return null
     */
    public function getProviderKey() {
        return $this->providerKey;
    }


    public function getUrl() {
        return;
    }
}
