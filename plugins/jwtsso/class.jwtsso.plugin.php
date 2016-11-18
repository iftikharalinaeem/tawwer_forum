<?php
/**
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license Proprietary
 */

$PluginInfo['jwtsso'] = [
    'Name' => 'JSON Web Token SSO',
    'ClassName' => "JWTSSOPlugin",
    'Description' => 'Connect users to a forum using SSO by passing a JSON Web Token.',
    'Version' => '1.0.0',
    'RequiredApplications' => ['Vanilla' => '2.2'],
    'SettingsUrl' => '/settings/jwtsso',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'MobileFriendly' => true,
    'Author' => "Patrick Kelly",
    'AuthorEmail' => 'patrick.k@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com'
];

/**
 * Class JWTSSOPlugin
 *
 * Plugin to authenticate users by interpreting a JSON Web Token.
 */
class JWTSSOPlugin extends Gdn_Plugin {

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
    protected $profileRequestParams = [];

    /** @var  @var string optional set the settings view */
    protected $settingsView;

    public function __construct() {
        $this->setProviderKey('JWTSSO');
    }

    public function setup() {
        $this->structure();
    }

    public function structure() {
        // Make sure we have the a provider.
        $provider = $this->provider();
        if (!val('AuthenticationKey', $provider)) {
            $model = new Gdn_AuthenticationProviderModel();
            $provider = [
                'AuthenticationKey' => $this->providerKey,
                'AuthenticationSchemeAlias' => $this->providerKey,
                'Name' => $this->providerKey,
                'ProfileKeyEmail' => 'email', // Can be overwritten in settings, the key the authenticator uses for email in response.
                'ProfileKeyPhoto' => 'picture',
                'ProfileKeyName' => 'displayname',
                'ProfileKeyFullName' => 'name',
                'ProfileKeyUniqueID' => 'user_id'
            ];

            $model->save($provider);
        }

        saveToConfig('Garden.SignIn.Popup', false);
    }


    /** ------------------- Settings Related Methods --------------------- */

    /**
     * Allow child class to over-ride or add form fields to settings.
     *
     * @return array Form fields to appear in settings dashboard.
     */
    protected function getSettingsFormFields() {
        $formFields = [
            'RegisterUrl' => ['LabelCode' => 'Register Url', 'Description' => 'Enter the endpoint to be appended to the base domain to direct a user to register.'],
            'SignOutUrl' => ['LabelCode' => 'Sign Out Url', 'Description' => 'Enter the endpoint to be appended to the base domain to log a user out.'],
            'ProfileKeyEmail' => ['LabelCode' => 'Email', 'Description' => 'The Key in the JSON payload to designate Emails'],
            'ProfileKeyPhoto' => ['LabelCode' => 'Photo', 'Description' => 'The Key in the JSON payload to designate Photo.'],
            'ProfileKeyName' => ['LabelCode' => 'Display Name', 'Description' => 'The Key in the JSON payload to designate Display Name.'],
            'ProfileKeyFullName' => ['LabelCode' => 'Full Name', 'Description' => 'The Key in the JSON payload to designate Full Name.'],
            'ProfileKeyUniqueID' => ['LabelCode' => 'User ID', 'Description' => 'The Key in the JSON payload to designate UserID.']
        ];
        return $formFields;
    }


    /**
     * Create a controller to deal with plugin settings in dashboard.
     *
     * @param Gdn_Controller $sender.
     * @param Gdn_Controller $args.
     */
    public function settingsController_jwtsso_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');
        $model = new Gdn_AuthenticationProviderModel();

        /* @var Gdn_Form $form */
        $form = new Gdn_Form();
        $form->setModel($model);
        $sender->Form = $form;

        if (!$form->AuthenticatedPostBack()) {
            $provider = $this->provider();
            $form->setData($provider);
        } else {

            $form->setFormValue('AuthenticationKey', $this->getProviderKey());

            $sender->Form->validateRule('AssociationSecret', 'ValidateRequired', 'You must provide a Secret');
            $sender->Form->validateRule('AuthorizeUrl', 'isUrl', 'You must provide a complete URL in the Authorize Url field.');

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
            'Algorithm' => ['LabelCode' => 'Algorithm', 'Control' => 'RadioList', 'Items' => ['HS256', 'RS256']],
            'AssociationKey' => ['LabelCode' => 'Client ID', 'Options' => ['Value' => $form->getValue('AssociationKey', betterRandomString(24)), 'readonly' => true], 'Description' => 'This is similar to an application ID. Please supply this to your Authentication Provider. It <b>must</b> be passed as the <code>sub</code> value in the payload of the token.'],
            'AuthorizeUrl' =>  ['LabelCode' => 'Authorize Url', 'Description' => 'Enter the endpoint to where users will be sent to sign in. This address <b>must</b> be passed as the <code>iss</code> value in the payload of the token.'],
            'Audience' =>  ['LabelCode' => 'Intended Audience','Options' => ['Value' => url('/', true), 'readonly' => true], 'Description' => 'This is a valid URL to this forum. Please supply this to your Authentication Provider. It <b>must</b> be passed as the <code>aud</code> value in the payload of the token.'],
            'AssociationSecret' =>  ['LabelCode' => 'Secret', 'Description' => 'Enter the shared secret used to encrypt and decrypt the JWT.']
        ];

        $formFields = $formFields + $this->getSettingsFormFields();

        $formFields['IsDefault'] = ['LabelCode' => 'Make this connection your default signin method.', 'Control' => 'checkbox'];

        $sender->setData('_Form', $formFields);

        $sender->addSideMenu();
        if (!$sender->data('Title')) {
            $sender->setData('Title', sprintf(T('%s Settings'), 'JSON Web Token SSO'));
        }

        $sampleJwt = $this->generatejwt($this->provider());

        $sender->setData('jwt', $sampleJwt);

        $view = ($this->settingsView) ? $this->settingsView : 'plugins/jwtsso';

        // Create send the possible redirect URLs that will be required by Oculus and display them in the dashboard.
        // Use Gdn::Request instead of convience function so that we can return http and https.
        $redirectUrls = Gdn::request()->url('/entry/'. $this->getProviderKey(), true, true);
        $sender->setData('redirectUrls', $redirectUrls);

        $sender->render('settings', '', 'plugins/'.$view);
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
    public function entryController_jwtsso_create($sender, $code, $state) {
        if ($error = $sender->Request->get('error')) {
            throw new Gdn_UserException($error);
        }

        // get the Bearer token from the Authorization header
        list($token, $tokenType) = $this->getBearerToken();

        // if there isn't one, do not advance
        if ($tokenType !== 'bearer' || !$tokenType) {
            die('no bearer token');
        }


        $decodedSegments = $this->extractToken($token);
        $secret = val('AssociationSecret', $this->provider());

        if (!$this->validateJWT($secret, $decodedSegments, $token)) {
            die('not signed');
        }

        Gdn::session()->stash($this->getProviderKey()); // remove any stashed provider data.

        $this->log('Getting Profile', array());
        $profile = $this->getProfile();
        $this->log('Profile', $profile);

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



    /** ------------------- Token Parsing Methods ---------------- */



    /**
     * Special encoding function strings for JWT
     *
     * @param $data
     * @return mixed
     */
    protected function base64url_encode($data) {
        return str_replace('=', '', strtr(base64_encode($data), '+/', '-_'));
    }


    /**
     * Create a Web Token with the recieved payload, sigin it with the same secret and compare the strings.
     *
     * @param $secret
     * @param $decodedSegments
     * @param $token
     * @return bool
     */
    public function validateJWT($secret, $decodedSegments, $token) {
        $compare = $this->signJWT($decodedSegments[0], $decodedSegments[1], $secret);
        if ($token !== $compare) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Generate a Java Web Token signed with the client's secret
     *
     * @param array $provider Row from GDN_AuthenticationProvider table
     * @param array $profile Unserialized array from GDN_User Attributes
     * @return string JWT
     */
    public function generateJwt($provider) {
        $baseUrl = val('BaseUrl', $provider);
        $baseUrl = (substr($baseUrl, -1) !== '/') ? $baseUrl.'/' : $baseUrl;
        $secret = val('AssociationSecret', $provider);
        $jwtHeader = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        $jwtBody = [
            'iss' => $baseUrl,
            'sub' => val('AssociationKey', $provider),
            'aud' => val('Audience', $provider),
            'exp' => time() + 1000,
            'iat' => time()
        ];

        $jwt = $this->signJWT($jwtHeader, json_encode($jwtBody), json_encode($secret));

        return $jwt;
    }


    /** ------------------- Provider Methods --------------------- */


    /**
     * Set provider key used to access settings stored in GDN_UserAuthenticationProvider.
     *
     * @param string $providerKey Key to retrieve provider data.
     * @return $this Return this object for chaining purposes.
     */
    public function setProviderKey($providerKey) {
        $this->providerKey = $providerKey;
        return $this;
    }


    /**
     *  Get provider key.
     *
     * @return string Provider key.
     */
    public function getProviderKey() {
        return $this->providerKey;
    }


    /**
     *  Return all the information saved in provider table.
     *
     * @return array Stored provider data (secret, client_id, etc.).
     */
    public function provider() {
        if (!$this->provider) {
            $this->provider = Gdn_AuthenticationProviderModel::getProviderByKey($this->providerKey);
        }

        return $this->provider;
    }


    /**
     * Check authentication provider table to see if this is the default method for logging in.
     *
     * @return bool Return the value of the IsDefault row of GDN_UserAuthenticationProvider .
     */
    public function isDefault() {
        $provider = $this->provider();
        return val('IsDefault', $provider);
    }


    /**
     * @param $jwtHeader
     * @param $jwtBody
     * @param $secret
     * @return string
     */
    public function signJWT($jwtHeader, $jwtBody, $secret) {
        $segments = [];
        // Strip the slashes from json encoded arrays, when base64 encoded they come out completely different.
        $segments[] = $this->base64url_encode(stripslashes($jwtHeader));
        $segments[] = $this->base64url_encode(stripslashes($jwtBody));
        $jwtString = implode('.', $segments);
        $key = base64_decode(strtr($secret, '-_', '+/'));
        $segments[] = $this->base64url_encode(hash_hmac('sha256', $jwtString, $key, true));
        $jwt = implode('.', $segments);
        return $jwt;
    }


    /**
     * @param $token
     * @return array
     */
    public function extractToken($token) {
        $segments = explode('.', $token);
        print_r($segments);
        $decodedSegment = [];
        foreach ($segments as $segment) {
            $decodedSegment[] = $this->base64url_decode($segment);
        }
        return $decodedSegment;
    }


    /**
     * @param $m
     * @return array
     */
    public function getBearerToken() {
        // First look for a header.
        $matches = [];
        $token = '';
        if ($auth = val('HTTP_AUTHORIZATION', $_SERVER, '')) {
            if (preg_match("/(.*)\s?:\s?(.*)/", $auth, $matches)) {
                $tokenType = trim(strtolower($matches[1]));
                $token = $matches[2];
                return array($token, $tokenType);
            }
        }

        if (empty($token)) {
            $allHeaders = getallheaders();
            if (val('Authorization', $allHeaders)) {
                if (preg_match("/(.*)\s?:\s?(.*)/", $allHeaders['Authorization'], $matches)) {
                    $tokenType = trim(strtolower($matches[1]));
                    $token = $matches[2];
                    return array($token, $tokenType);
                }
            }
        }

        // if no token is found return false.
        return [false, false];
    }


    /**
     * Convenience method for updating the log
     * @param $message
     * @param $data
     */
    public function log($message, $data) {
        if (c('Vanilla.SSO.Debug')) {
            if (!is_array($data)) {
                $data = (array) $data;
            }
            Logger::event(
                'sso_logging',
                Logger::INFO,
                $message,
                $data
            );
        }
    }
}
