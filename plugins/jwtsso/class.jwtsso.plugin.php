<?php
/**
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license Proprietary
 */

$PluginInfo['jwtsso'] = [
    'Name' => 'JSON Web Token SSO',
    'ClassName' => "JWTSSOPlugin",
    'Description' => 'Connect users to a forum using SSO by passing a JSON Web Token.',
    'Version' => '1.0.2',
    'RequiredApplications' => ['Vanilla' => '2.2'],
    'SettingsUrl' => '/settings/jwtsso',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'UsePopupSettings' => false,
    'MobileFriendly' => true,
    'Author' => 'Patrick Kelly',
    'AuthorEmail' => 'patrick.k@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com'
];

const DEFAULT_PROVIDER_KEY = "JWTSSODefault";

const PROVIDER_SCHEME_ALIAS = "JWTSSO";

/**
 * Class JWTSSOPlugin
 *
 * Plugin to authenticate users by interpreting a JSON Web Token.
 */
class JWTSSOPlugin extends Gdn_Plugin {

    /** @var string unique key for a JWTSSO setup stored in GDN_UserAuthenticationProvider table  */
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

    /** @var int the iat (issued at time) from the payload of the token*/
    protected $iatClaim = 0;

    /** @var string the sub (clientID) from the payload of the token*/
    protected $subClaim = null;

    /** @var string or URL the aud (intended audience, usually web address) from the payload of the token*/
    protected $audClaim = null;

    /** @var string or URL the aud (issuer, usually web address) from the payload of the token*/
    protected $issClaim = null;

    /** @var string (unique hash) from the payload of the token*/
    protected $jtiClaim = null;

    /** @var array supported alorithms */
    public  $supportedAlgs = [
        'HS256' => 'sha256',
        'HS512' => 'sha512',
        'HS384' => 'sha384'
    ];

    public $translatedKeys = [];

    public function __construct() {
        $provider = $this->provider();
        $this->translatedKeys = [
            val('ProfileKeyEmail', $provider, 'email') => 'Email',
            val('ProfileKeyPhoto', $provider, 'picture') => 'Photo',
            val('ProfileKeyName', $provider, 'displayname') => 'Name',
            val('ProfileKeyFullName', $provider, 'name') => 'FullName',
            val('ProfileKeyUniqueID', $provider, 'sub')  => 'UniqueID'
        ];
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
                'AuthenticationKey' => DEFAULT_PROVIDER_KEY,
                'AuthenticationSchemeAlias' => PROVIDER_SCHEME_ALIAS,
                'Name' => DEFAULT_PROVIDER_KEY,
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
     * @param SettingsController $sender.
     * @param SettingsController $args.
     */
    public function settingsController_jwtsso_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');
        $model = new Gdn_AuthenticationProviderModel();

        /* @var Gdn_Form $form */
        $form = new Gdn_Form();
        $form->setModel($model);
        $sender->Form = $form;
        $generate = false;
        if (!$form->AuthenticatedPostBack()) {
            $provider = $this->provider();
            $form->setData($provider);
        } else {
            if ($form->getFormValue('Generate') || $sender->Request->post('Generate')) {
                $generate = true;
                $secret = md5(mt_rand());
                $sender->setFormSaved(false);
            } else {
                $form->setFormValue('AuthenticationKey', $this->getProviderKey());

                $sender->Form->validateRule('AssociationSecret', 'ValidateRequired', 'You must provide a Secret');
                $sender->Form->validateRule('SignInUrl', 'isUrl', 'You must provide a complete URL in the Sign In  URL field.');
                $sender->Form->validateRule('BaseUrl', 'isUrl', 'You must provide a complete URL in the Issuer URL field.');

                if ($form->save()) {
                    $sender->informMessage(t('Saved'));
                }
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
            'SignInUrl' =>  ['LabelCode' => 'Sign In URL', 'Control' => 'TextBox', 'Description' => 'Enter the endpoint to where users will be sent to sign in. This address <b>must</b> be passed as the <code>iss</code> value in the payload of the token.'],
            'BaseUrl' =>  ['LabelCode' => 'Issuer', 'Control' => 'TextBox', 'Description' => 'Enter the URL of the server that is issuing this token.'],
            'Audience' =>  ['LabelCode' => 'Intended Audience', 'Control' => 'TextBox', 'Options' => ['Value' => $form->getValue('Audience', url('/', true)),], 'Description' => 'This is a valid URL to this forum. Please supply this to your Authentication Provider. It <b>must</b> be passed as the <code>aud</code> value in the payload of the token.'],
            'AssociationSecret' =>  ['LabelCode' => 'Secret', 'Control' => 'TextBox', 'Description' => 'Enter the shared secret, either supplied by your authentication provider or create one and share it with your authentication provider. You can click on "<b>Generate Secret</b>" below.'],
            'RegisterUrl' => ['LabelCode' => 'Register URL', 'Control' => 'TextBox', 'Description' => 'Enter the endpoint to be appended to the base domain to direct a user to register.'],
            'SignOutUrl' => ['LabelCode' => 'Sign Out URL', 'Control' => 'TextBox', 'Description' => 'Enter the endpoint to be appended to the base domain to log a user out.'],
            'ProfileKeyUniqueID' => ['LabelCode' => 'UniqueID', 'Description' => 'The Key in the JSON payload to designate the User\'s UniqueID'],
            'ProfileKeyEmail' => ['LabelCode' => 'Email', 'Description' => 'The Key in the JSON payload to designate Emails'],
            'ProfileKeyPhoto' => ['LabelCode' => 'Photo', 'Description' => 'The Key in the JSON payload to designate Photo.'],
            'ProfileKeyName' => ['LabelCode' => 'Display Name', 'Description' => 'The Key in the JSON payload to designate Display Name.'],
            'ProfileKeyFullName' => ['LabelCode' => 'Full Name', 'Description' => 'The Key in the JSON payload to designate Full Name.']
        ];

        // Allow a client to hook in and add fields that might be relevent to their set up.
        $sender->EventArguments['FormFields'] = &$formFields;
        $sender->fireEvent('JWTSettingsFields');

        $formFields['IsDefault'] = ['LabelCode' => 'Make this connection your default signin method.', 'Control' => 'checkbox'];

        $sender->setData('_Form', $formFields);

        $sender->setHighlightRoute();
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
        $connectURL = Gdn::request()->url('/entry/connect/'. PROVIDER_SCHEME_ALIAS.'/?authKey='.$this->getProviderKey(), true, true);
        $sender->setData('ConnectURL', $connectURL);

        // For auto generating a secret for the client.
        if ($generate && $sender->deliveryType() === DELIVERY_TYPE_VIEW) {
            $sender->setJson('AssociationSecret', $secret);
            $sender->render('Blank', 'Utility', 'Dashboard');
        } else {
            $sender->render('settings', '', 'plugins/jwtsso');
        }
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
            'sub' => c('JWTSSO.TestToken.ForeignKey', '12345'),
            'aud' => val('Audience', $provider),
            'email' => c('JWTSSO.TestToken.Email', '+++'.Gdn::session()->User->Email),
            'displayname' => c('JWTSSO.TestToken.Name'),
            'exp' => time() + c('JWTSSO.TestToken.ExpiryTime', 600),
            'nbf' => time()
        ];

        $jwt = $this->signJWT(json_encode($jwtHeader), json_encode($jwtPayload), $secret, $algorithm);

        return $jwt;
    }

    /**
     * Inject Javascript file into dashboard to a allow adding auto-generated secret and clientID;
     *
     * @param $sender SettingsController
     */
    public function settingsController_render_before($sender) {
        $sender->addJsFile('jwt-settings.js', 'plugins/jwtsso');
        $sender->addCssFile('jwt-settings.css', 'plugins/jwtsso');
    }


    /** ------------------- Connection Related Methods --------------------- */

    /**
     * Inject into the process of the base connection.
     *
     * @param Gdn_Controller $sender.
     * @param Gdn_Controller $args.
     */
    public function base_connectData_handler($sender, $args) {
        $authenticationKey = $sender->Request->get('authKey');
        if (!$authenticationKey) {
            $this->log('not_configured', ['provider' => $this->provider()]);
            throw new Gdn_UserException('JWT authentication is not configured properly. Missing provider key', 400);
        }

        if (val(0, $args) != PROVIDER_SCHEME_ALIAS || $this->getProviderKey() != $authenticationKey) {
            $this->log('not_configured', ['provider' => $this->provider(), 'passedArg' => val(0, $args)]);
            throw new Gdn_UserException('JWT authentication is not configured properly. Unknown provider: "'.val(0, $args).'/'.$authenticationKey.'"', 400);
        }

        /* @var Gdn_Form $form */
        $form = $sender->Form; //new Gdn_Form();

        if (!$this->isConfigured()) {
            $this->log('not_configured', ['provider' => $this->provider()]);
            throw new Gdn_UserException('JWT authentication is not configured.', 400);
        }

        // get the Bearer token from the Authorization header
        list($token, $tokenType) = $this->getBearerToken();
        // if there isn't one, do not advance
        if ($tokenType !== 'bearer' || !$tokenType) {
            $this->log('no_bearer', ['tokentype' => $tokenType]);
            throw new Gdn_UserException('Unable to proceed, no JSON Web Token found in header.', 400);
        }

        $this->extractToken($token);

        // If we ever remove time validation, we should also disable passing the token in GET as per best practices.
        if (!$this->validateTime()) {
            $this->log('invalid_time', ['notbeforetime' => $this->nbfClaim, 'issuedattime' => $this->iatClaim, 'notaftertime' => $this->expClaim, 'now' => time(), 'payload' => $this->jwtPayload]);
            throw new Gdn_UserException('Unable to proceed, JSON Web Token is probably expired.', 400);
        }

        if (!$this->validateAudience()) {
            $this->log('invalid_audience', ['aud' => $this->audClaim, 'payload' => $this->jwtPayload, 'provider' => $this->provider()]);
            throw new Gdn_UserException('Unable to proceed, verify that your JSON Web Token has the correct "aud" value.', 400);
        }

        if (!$this->validateIssuer()) {
            $this->log('invalid_issuer', ['iss' => $this->issClaim, 'payload' => $this->jwtPayload, 'provider' => $this->provider()]);
            throw new Gdn_UserException('Unable to proceed, verify that your JSON Web Token has the correct "iss" value.', 400);
        }

        if (!$this->validateSignature()) {
            $this->log('invalid_signature', ['secret' => val('AssociationSecret', $this->provider), 'segments' => $this->jwtJSONSegments]);
            throw new Gdn_UserException('Unable to proceed, verify the signature on your JSON Web Token.', 400);
        }

        // Allow custom plugins to translate custom keys being passed in SSO
        $translatedKeys = [];
        $sender->EventArguments['translatedKeys'] = &$translatedKeys;
        $sender->fireEvent('BeforeExtractProfile');
        $this->translatedKeys = array_merge($this->translatedKeys, $translatedKeys);

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
        $profile = arrayTranslate($rawProfile, $this->translatedKeys, true);
        $profile['Provider'] = $this->getProviderKey();
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
        $authFieldRegex = '/^(bearer)\s?(?::\s?)?((?:[a-z\d_-]+\.){2}[a-z\d_-]+)$/i';
        if ($auth = Gdn::request()->getValueFrom(Gdn_Request::INPUT_SERVER, 'HTTP_AUTHORIZATION', null)) {
            if (preg_match($authFieldRegex, $auth, $matches)) {
                $tokenType = trim(strtolower($matches[1]));
                $token = $matches[2];
                return [$token, $tokenType];
            }
        }

        // If it wasn't in HTTP_AUTHORIZATION
        if (empty($token)) {
            $allHeaders = getallheaders();
            if (val('Authorization', $allHeaders)) {
                if (preg_match($authFieldRegex, $allHeaders['Authorization'], $matches)) {
                    $tokenType = trim(strtolower($matches[1]));
                    $token = $matches[2];
                    return [$token, $tokenType];
                }
            }
        }

        // If there was no token passed in the header, look in the get.
        // We can do this because we also only accept tokens for a limited time, if we change that we should disable this.
        if (empty($token)) {
            if ($authToken = Gdn::request()->getValueFrom(Gdn_Request::INPUT_GET, 'authorize', null)) {
                return [$authToken, 'bearer'];
            }
        }


        // if no token is found return false.
        return [false, false];
    }


    /**
     * Parse out the segements of the JWT, assign the values as properties of this object.
     *
     * @param $token
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
        $this->subClaim = val('sub', $this->jwtPayload);
        $this->issClaim = val('iss', $this->jwtPayload);
        $this->audClaim = val('aud', $this->jwtPayload);
        $this->expClaim = val('exp', $this->jwtPayload);
        $this->nbfClaim = val('nbf', $this->jwtPayload);
        $this->jtiClaim = val('jti', $this->jwtPayload);
        $this->iatClaim = val('ait', $this->jwtHeader);
        $this->algClaim = val('alg', $this->jwtHeader);
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
     * Special encoding function strings for JWT
     *
     * @param $data
     * @return mixed
     */
    protected function base64url_decode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
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


    /** ------------------- Validation Methods --------------------- */

    private function isConfigured() {
        $provider = $this->provider();
        return (val('AssociationSecret', $provider, false));
    }


    /**
     * Check if the token time stamp is valid.
     *
     * @return bool
     */
    private function validateTime() {
        $leeway = c('JWTSSO.leewaytime', 300);
        // If the token arrives before the Not Before claim or when it was claimed to be issued...
        // Allow 5 minutes +/- to account for inaccurate server times.
        if ($this->nbfClaim - $leeway > time() + $leeway || $this->iatClaim - $leeway > time() + $leeway) {
            return false;
        }
        if ($this->expClaim + $leeway < time() - $leeway) {
            return false;
        }
        return true;
    }


    /**
     * Check if the 'aud' passed is the same as the Audience stored in the provider.
     *
     * @return bool
     */
    private function validateAudience() {
        $provider = $this->provider();
        return trim($this->audClaim, '/') === trim(val('Audience', $provider), '/');
    }


    /**
     * Check if the 'iss' passed is the same as the Issuer (baseURL) stored in the provider
     *
     * @return bool
     */
    private function validateIssuer() {
        $provider = $this->provider();
        return trim($this->issClaim, '/') === trim(val('BaseUrl', $provider), '/');
    }


    /**
     * Create a Web Token with the received payload, sign it with the shared secret and compare the strings.
     *
     * @return bool
     */
    private function validateSignature() {
        $decodedSegments = $this->jwtJSONSegments;
        $algorithm = val($this->algClaim, $this->supportedAlgs);
        $compare = $this->signJWT($decodedSegments[0], $decodedSegments[1], val('AssociationSecret', $this->provider()), $algorithm);
        if ($this->jwtRawToken !== $compare) {
            return false;
        } else {
            return true;
        }
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
        if (!$this->providerKey) {
            $this->provider();
            $this->setProviderKey(val('AuthenticationKey', $this->provider, DEFAULT_PROVIDER_KEY));
        }
        return $this->providerKey;
    }


    /**
     *  Return all the information saved in provider table.
     *
     * @return array Stored provider data (secret, client_id, etc.).
     */
    protected function provider() {
        if (!$this->provider && $this->providerKey) {
            $this->provider = Gdn_AuthenticationProviderModel::getProviderByKey($this->providerKey);
        } else {
            $this->provider = Gdn_AuthenticationProviderModel::getProviderByScheme(PROVIDER_SCHEME_ALIAS);
        }
        return $this->provider;
    }


    /**
     * Check authentication provider table to see if this is the default method for logging in.
     *
     * @return bool Return the value of the IsDefault row of GDN_UserAuthenticationProvider .
     */
    protected function isDefault() {
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
