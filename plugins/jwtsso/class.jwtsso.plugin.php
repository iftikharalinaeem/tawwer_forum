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
    'Author' => 'Patrick Kelly',
    'AuthorEmail' => 'patrick.k@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com'
];

/**
 * Class JWTSSOPlugin
 *
 * Plugin to authenticate users by interpreting a JSON Web Token.
 */
class JWTSSOPlugin extends Gdn_Plugin {

    /** @var string key for GDN_UserAuthenticationProvider table  */
    protected $providerKey = null;

    /** @var array stored information to connect with provider (secret, etc.) */
    protected $provider = [];

    /** @var string the signed encrypted token  */
    protected $jwtRawToken = null;

    /** @var array the header from the token*/
    protected $jwtHeader = [];

    /** @var string the alg (encryption algorithm) from the header of the token*/
    protected $algClaim = null;

    /** @var array the payload from the token*/
    protected $jwtPayload = [];

    /** @var array the segments in their raw JSON format from the payload of the token*/
    protected $jwtJSONSegments = [];

    /** @var int the exp (expiry time) from the payload of the token*/
    protected $expClaim = 0;

    /** @var int the nbf (not before time) from the payload of the token*/
    protected $nbfClaim = 0;

    /** @var array supported alorithms */
    public  $supportedAlgs = [
        'HS256' => 'sha256',
        'HS512' => 'sha512',
        'HS384' => 'sha384'
    ];

    public function __construct() {
        $this->setProviderKey('JWTSSO');
    }

    /**
     * Run when plugin is activated in dashboard or through utility update
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Create a row in the UserAuthenticationTable with default values for JSON Web Token SSO
     */
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
                'ProfileKeyUniqueID' => 'sub'
            ];
            $model->save($provider);
        }
        saveToConfig('Garden.SignIn.Popup', false);
    }


    /** ------------------- Settings Related Methods --------------------- */


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

        $supportedAlgs = array_keys($this->supportedAlgs);
        $algsItems = [];
        foreach ($supportedAlgs as $alg) {
            $algsItems[$alg] = $alg;
        }

        // Set up the form.
        $formFields = [
            'Algorithm' => ['LabelCode' => 'Algorithm', 'Control' => 'RadioList', 'Items' => $algsItems, 'Options' => ['Default' => 'HS256']],
            'AssociationKey' => ['LabelCode' => 'Client ID', 'Options' => ['Value' => $form->getValue('AssociationKey', betterRandomString(24)), 'readonly' => true], 'Description' => 'This is similar to an application ID. Please supply this to your Authentication Provider. It <b>must</b> be passed as the <code>sub</code> value in the payload of the token.'],
            'AuthorizeUrl' =>  ['LabelCode' => 'Authorize Url', 'Description' => 'Enter the endpoint to where users will be sent to sign in. This address <b>must</b> be passed as the <code>iss</code> value in the payload of the token.'],
            'Audience' =>  ['LabelCode' => 'Intended Audience','Options' => ['Value' => url('/', true), 'readonly' => true], 'Description' => 'This is a valid URL to this forum. Please supply this to your Authentication Provider. It <b>must</b> be passed as the <code>aud</code> value in the payload of the token.'],
            'AssociationSecret' =>  ['LabelCode' => 'Secret', 'Description' => 'Enter the shared secret used to encrypt and decrypt the JWT.'],
            'RegisterUrl' => ['LabelCode' => 'Register Url', 'Description' => 'Enter the endpoint to be appended to the base domain to direct a user to register.'],
            'SignOutUrl' => ['LabelCode' => 'Sign Out Url', 'Description' => 'Enter the endpoint to be appended to the base domain to log a user out.'],
            'ProfileKeyEmail' => ['LabelCode' => 'Email', 'Description' => 'The Key in the JSON payload to designate Emails'],
            'ProfileKeyPhoto' => ['LabelCode' => 'Photo', 'Description' => 'The Key in the JSON payload to designate Photo.'],
            'ProfileKeyName' => ['LabelCode' => 'Display Name', 'Description' => 'The Key in the JSON payload to designate Display Name.'],
            'ProfileKeyFullName' => ['LabelCode' => 'Full Name', 'Description' => 'The Key in the JSON payload to designate Full Name.'],
            'ProfileKeyUniqueID' => ['LabelCode' => 'User ID', 'Description' => 'The Key in the JSON payload to designate UserID.']
        ];

        // Allow a client to hook in and add fields that might be relevent to their set up.
        $sender->EventArguments['FormFields'] = $formFields;
        $sender->fireEvent('JWTSettingsFields');

        $formFields = $formFields;

        $formFields['IsDefault'] = ['LabelCode' => 'Make this connection your default signin method.', 'Control' => 'checkbox'];

        $sender->setData('_Form', $formFields);

        $sender->addSideMenu();
        if (!$sender->data('Title')) {
            $sender->setData('Title', sprintf(t('%s Settings'), 'JSON Web Token SSO'));
        }

        // If there are sufficient settings, create and display a sample JWT
        $provider = $this->provider();
        if (val('AssociationSecret', $provider)) {
            $sampleJwt = $this->generateSampleJWT($this->provider());
            $sender->setData('jwt', $sampleJwt);
        } else {
            $sender->setData('jwt', false);
        }

        // Create the URL to display to the forum connect endpoint.
        // Use Gdn::request instead of convience function so that we can return http and https.
        $connectURL = Gdn::request()->url('/entry/connect/'. $this->getProviderKey(), true, true);
        $sender->setData('ConnectURL', $connectURL);

        $sender->render('settings', '', 'plugins/plugins/jwtsso');
    }


    /**
     * Generate a JSON Web Token signed with the client's secret
     *
     * @param array $provider Row from GDN_AuthenticationProvider table
     * @return string JWT
     */
    public function generateSampleJWT($provider) {
        $baseUrl = val('BaseUrl', $provider);
        $baseUrl = (substr($baseUrl, -1) !== '/') ? $baseUrl.'/' : $baseUrl;
        $secret = val('AssociationSecret', $provider);
        $algorithm = val(val('Algorithm', $provider), $this->supportedAlgs, 'HS256');
        $jwtHeader = [
            'typ' => 'JWT',
            'alg' => val('Algorithm', $provider)
        ];

        $jwtPayload = [
            'iss' => $baseUrl,
            'sub' => val('AssociationKey', $provider),
            'aud' => val('Audience', $provider),
            'email' => c('JWTSSO.TestToken.Email', Gdn::session()->User->Email),
            'displayname' => c('JWTSSO.TestToken.Name', Gdn::session()->User->Name),
            'exp' => time() + c('JWTSSO.TestToken.ExpiryTime', 1000),
            'nbf' => time()
        ];

        $jwt = $this->signJWT(json_encode($jwtHeader), json_encode($jwtPayload), $secret, $algorithm);

        return $jwt;
    }

    /** ------------------- Connection Related Methods --------------------- */


    /**
     * Before rendering any page, do a quick check if the user has a viable bearer token, redirect to connect if so.
     *
     * @param Gdn_Controller $sender
     */
    public function base_render_before($sender) {
        // Return quietly if...
        // ...we are in the entry controller
        if ($sender->ControllerName === 'entrycontroller') {
            return;
        }

        // ...user is logged in
        if (Gdn::session()->UserID) {
            return;
        }

        list($token, $tokenType) = $this->getBearerToken();

        // ...there isn't a bearer token in the HTTP Header
        if ($tokenType !== 'bearer' || !$tokenType) {
            return;
        }

        $this->extractToken($token);

        // ...the token is expired.
        if (!$this->validateTime()) {
            return;
        }

        // If there is a valid, signed token, redirect to the entry connect script.
        if ($this->validatSignature()) {
            safeRedirect('/entry/connect/'.$this->providerKey.'/?Target='.urlencode(Gdn::request()->url()));
        }
        return;
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

        /* @var Gdn_Form $form */
        $form = $sender->Form; //new Gdn_Form();

        // get the Bearer token from the Authorization header
        list($token, $tokenType) = $this->getBearerToken();
        // if there isn't one, do not advance
        if ($tokenType !== 'bearer' || !$tokenType) {
            $form->addError('Unable to proceed, no JSON Web Token found in header.');
            $sender->render('connecterror');
            $this->log('no_bearer', ['tokentype' => $tokenType]);
            return;
        }

        $this->extractToken($token);

        if (!$this->validateTime()) {
            $form->addError('Unable to proceed, JSON Web Token is probably expired.');
            $sender->render('connecterror');
            $this->log('invalid_time', ['notbeforetime' => $this->nbfClaim, 'notaftertime' => $this->expClaim, 'now' => time(), 'payload' => $this->jwtPayload]);
            return;
        }

        if (!$this->validatSignature()) {
            $form->addError('Unable to proceed, invalid JSON Web Token.');
            $sender->render('connecterror');
            $this->log('invalid_signature', ['secret' => val('AssociationSecret', $this->provider), 'segments' => $this->jwtJSONSegments]);
            return;
        }

        $profile = $this->getProfileFromToken();
        $this->log('Profile', $profile);

        // Populate form with values from the profile.
        $originaFormValues = $form->formValues();
        $formValues = array_replace($originaFormValues, $profile);
        $form->formValues($formValues);

        // Save some original data in the attributes of the connection for later API calls.
        $attributes = [];
        $attributes[$this->getProviderKey()] = ['Profile' => $profile];
        $form->setFormValue('Attributes', $attributes);

        $sender->EventArguments['Profile'] = $profile;
        $sender->EventArguments['Form'] = $form;

        // Throw an event so that other plugins can add/remove stuff from the basic sso.
        $sender->fireEvent('JWTConnect');

        SpamModel::disabled(true);
        $sender->setData('Trusted', true);
        $sender->setData('Verified', true);
    }


    /**
     * Get the user's profile information embedded in the token.
     *
     * @return array
     */
    protected function getProfileFromToken() {
        $profile = $this->getJWTPayload();
        $profile = $this->translateProfileResults($profile);
        return $profile;
    }


    /**
     * Allow the admin to input the keys that their service uses to send data.
     *
     * @param array $rawProfile profile as it is returned from the provider.
     * @return array Profile array transformed by child class or as is.
     */
    public function translateProfileResults($rawProfile = []) {
        $provider = $this->provider();
        $translatedKeys = [
            val('ProfileKeyEmail', $provider, 'email') => 'Email',
            val('ProfileKeyPhoto', $provider, 'picture') => 'Photo',
            val('ProfileKeyName', $provider, 'displayname') => 'Name',
            val('ProfileKeyFullName', $provider, 'name') => 'FullName',
            'sub' => 'UniqueID'
        ];
        $profile = arrayTranslate($rawProfile, $translatedKeys, true);
        $profile['Provider'] = $this->providerKey;
        return $profile;
    }


    /** ------------------- Token Parsing Methods ---------------- */



    /**
     * Parse the HTTP headers to get a bearer token if one exists.
     *
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
                return [$token, $tokenType];
            }
        }

        if (empty($token)) {
            $allHeaders = getallheaders();
            if (val('Authorization', $allHeaders)) {
                if (preg_match("/(.*)\s?:\s?(.*)/", $allHeaders['Authorization'], $matches)) {
                    $tokenType = trim(strtolower($matches[1]));
                    $token = $matches[2];
                    return [$token, $tokenType];
                }
            }
        }

        // if no token is found return false.
        return [false, false];
    }


    /**
     * Parse out the segements of the JWT, assign the values as properties of this object.
     *
     * @param $token
     * @return array
     */
    public function extractToken($token) {
        $this->jwtRawToken = $token;
        $segments = explode('.', $token);
        $decodedSegment = [];
        foreach ($segments as $segment) {
            $decodedSegment[] = $this->base64url_decode($segment);
        }
        $this->setJWTHeader(json_decode($decodedSegment[0], true));
        $this->setJWTPayload(json_decode($decodedSegment[1], true));
        $this->jwtJSONSegments = $decodedSegment;
        $this->expClaim = val('exp', $this->jwtPayload);
        $this->nbfClaim = val('nbf', $this->jwtPayload);
        $this->algClaim = val('alg', $this->jwtHeader);
    }


    /**
     * Check if the token time stamp is valid.
     *
     * @return bool
     */
    private function validateTime() {
        if ($this->nbfClaim > time()) {
            return false;
        }
        if ($this->expClaim < time()) {
            return false;
        }
        return true;
    }


    /**
     * Create a Web Token with the received payload, sign it with the shared secret and compare the strings.
     *
     * @return bool
     */
    private function validatSignature() {
        $decodedSegments = $this->jwtJSONSegments;
        $algorithm = val($this->algClaim, $this->supportedAlgs);
        $compare = $this->signJWT($decodedSegments[0], $decodedSegments[1], val('AssociationSecret', $this->provider()), $algorithm);
        if ($this->jwtRawToken !== $compare) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Special encoding function strings for JWT
     *
     * @param $data
     * @return mixed
     */
    protected function base64url_decode($data) {
        return base64_decode($data);
    }


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
     * Sign the raw JSON Header and Payload from the token for comparison purposes.
     *
     * @param $rawJWTHeader JSON string
     * @param $rawJWTPayload JSON string
     * @param $secret the AssociationSecret shared with the client.
     * @return string JSON Web Token
     */
    public function signJWT($rawJWTHeader, $rawJWTPayload, $secret, $alg) {
        $segments = [];
        // Strip the slashes from json encoded arrays, when base64 encoded they come out completely different.
        $segments[] = $this->base64url_encode(stripslashes($rawJWTHeader));
        $segments[] = $this->base64url_encode(stripslashes($rawJWTPayload));
        $JWTString = implode('.', $segments);
        $key = base64_decode(strtr($secret, '-_', '+/'));
        $segments[] = trim($this->base64url_encode(hash_hmac($alg, $JWTString, $key, true)));
        $jwt = implode('.', $segments);
        return $jwt;
    }


    /**
     * Return the data from the token payload in an array
     *
     * @param $payload
     * @return bool
     */
    protected function setJWTPayload($payload) {
        if (!is_array($payload)) {
            return false;
        }
        $this->jwtPayload = $payload;
    }


    /**
     * Set the data from the token header in an array
     *
     * @param $head
     * @return bool
     */
    protected function setJWTHeader($head) {
        if (!is_array($head)) {
            return false;
        }
        $this->jwtHeader = $head;
    }


    /**
     * Return the data from the token payload in an array
     *
     * @return array
     */
    protected function getJWTPayload() {
        return $this->jwtPayload;
    }


    /**
     * Return the data from the token header in an array
     *
     * @return array
     */
    protected function getJWTHeader() {
        return $this->jwtHeader;
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
     * Convenience method for updating the log.
     *
     * @param $message
     * @param $data
     */
    public function log($message, $data) {
        if (c('Vanilla.SSO.Debug')) {
            if (!is_array($data)) {
                $data = (array) $data;
            }
            Logger::event(
                'jwt_logging',
                Logger::INFO,
                $message,
                $data
            );
        }
    }
}
