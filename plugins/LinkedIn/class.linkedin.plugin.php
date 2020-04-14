<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

use Vanilla\Web\CurlWrapper;

/**
 * Class LinkedInPlugin
 */
class LinkedInPlugin extends SSOAddon {
    const ProviderKey = 'LinkedIn';
    private const AUTHENTICATION_SCHEME = 'linkedin';

    /// Methods ///
    protected $_AccessToken = null;

    /**
     * Get the AuthenticationSchemeAlias value.
     *
     * @return string The AuthenticationSchemeAlias.
     */
    protected function getAuthenticationScheme(): string {
        return self::AUTHENTICATION_SCHEME;
    }

    /** @var SsoUtils */
    private $ssoUtils;

    /**
     * Constructor.
     *
     * @param SsoUtils $ssoUtils
     */
    public function __construct(SsoUtils $ssoUtils) {
        parent::__construct();
        $this->ssoUtils = $ssoUtils;
    }

    /**
     * @param null $value
     * @return bool|mixed|null
     */
    public function accessToken($value = null) {
        if (!$this->isConfigured()) {
            return false;
        }

        if ($value !== null) {
            $this->_AccessToken = $value;
        } elseif ($this->_AccessToken === null) {
            if (Gdn::session()->isValid()) {
                $this->_AccessToken = getValueR(self::ProviderKey.'.AccessToken', Gdn::session()->User->Attributes);
            } else {
                $this->_AccessToken = false;
            }
        }

        return $this->_AccessToken;
    }

    /**
     * @param $path
     * @param bool $post
     * @return mixed
     * @throws Gdn_UserException
     */
    public function api($path, $post = false) {
        // Build the url.
        $url = 'https://api.linkedin.com/v2/'.ltrim($path, '/');

        $accessToken = $this->accessToken();
        if (!$accessToken) {
            throw new Gdn_UserException("You don't have a valid LinkedIn connection.");
        }

        if (strpos($url, '?') === false) {
            $url .= '?';
        } else {
            $url .= '&';
        }
        $url .= 'oauth2_access_token='.urlencode($accessToken);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($post !== false) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            trace("  POST $url");
        } else {
            trace("  GET  $url");
        }
        $response = CurlWrapper::curlExec($ch, false);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        Gdn::controller()->setJson('Type', $contentType);

        if (strpos($contentType, 'application/json') !== false) {
            $result = json_decode($response, true);

            if (isset($result['error'])) {
                Gdn::dispatcher()->passData('LinkedInResponse', $result);
                throw new Gdn_UserException($result['error']['message']);
            }
        } else {
            $result = $response;
        }

        return $result;
    }

    /**
     * @param bool $redirectUri
     * @return string
     */
    public function authorizeUri($redirectUri = false) {
        $appID = c('Plugins.LinkedIn.ApplicationID');
        $scope = c('Plugins.LinkedIn.Scope', 'r_liteprofile r_emailaddress');

        if (!$redirectUri) {
            $redirectUri = $this->redirectUri();
        }

        // Get a state token.
        $stateToken = $this->ssoUtils->getStateToken();

        $query = [
            'client_id' => $appID,
            'response_type' => 'code',
            'scope' => $scope,
            'state' => json_encode(['token' => $stateToken]),
            'redirect_uri' => $redirectUri,
        ];

        return 'https://www.linkedin.com/uas/oauth2/authorization?'.http_build_query($query);
    }

    /**
     * @param $code
     * @param $redirectUri
     * @return mixed
     * @throws Gdn_UserException
     */
    protected function getAccessToken($code, $redirectUri) {
        $get = [
            'grant_type' => 'authorization_code',
            'client_id' => c('Plugins.LinkedIn.ApplicationID'),
            'client_secret' => c('Plugins.LinkedIn.Secret'),
            'code' => $code,
            'redirect_uri' => $redirectUri];

        $url = 'https://www.linkedin.com/uas/oauth2/accessToken?'.http_build_query($get);

        // Get the redirect URI.
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($c, CURLOPT_URL, $url);

        $contents = CurlWrapper::curlExec($c, false);
        $info = curl_getinfo($c);
        if (strpos(val('content_type', $info, ''), 'application/json') !== false) {
            $tokens = json_decode($contents, true);
        } else {
            parse_str($contents, $tokens);
        }

        if (val('error', $tokens)) {
            throw new Gdn_UserException('LinkedIn returned the following error: '.val('error_description', $tokens, 'Unknown error.'), 400);
        }

        $accessToken = val('access_token', $tokens);

        return $accessToken;
    }

    /**
     * @return array|mixed
     */
    public function getProfile() {
        $profile = $this->api('me?projection=(id,firstName,lastName,profilePicture(displayImage~:playableStreams))');
        $emailArray =  $this->api('/emailAddress?q=members&projection=(elements*(handle~))');
        $language = $profile['firstName']['preferredLocale']['language'] ?? 'en';
        $country = $profile['firstName']['preferredLocale']['country'] ?? 'US';
        $preferredLocale = $language.'_'.$country;
        $firstName = $profile['firstName']['localized'][$preferredLocale];
        $lastName =  $profile['lastName']['localized'][$preferredLocale];
        $profile['email'] = $emailArray['elements'][0]['handle~']['emailAddress'];
        $profile['fullname'] = $firstName.' '.$lastName;
        $profilePictureArray  = $profile['profilePicture']['displayImage~']['elements'];
        $profile['photo'] = $this->getUserPhotoUrl($profilePictureArray);
        return $profile;
    }

    /**
     * Get the user's profile picture.
     *
     * @param array $elements Profile picture values.
     * @return string The user's profile picture url.
     */
    public function getUserPhotoUrl($profilePictureArray) {
        $photoUrl = '';
        if(is_array($profilePictureArray)){
            $profilePictureArray = end($profilePictureArray);
            $photoUrl = $profilePictureArray['identifiers'][0]['identifier'];
        }
        return $photoUrl;
    }

    /**
     * @param string $type
     * @return string
     */
    public function signInButton($type = 'button') {
        $url = url('entry/linkedin');

        $result = socialSignInButton('LinkedIn', $url, $type);
        return $result;
    }

    /**
     * @return bool
     */
    public function isConfigured() {
        $appID = c('Plugins.LinkedIn.ApplicationID');
        $secret = c('Plugins.LinkedIn.Secret');
        if (!$appID || !$secret) {
            return false;
        }
        return true;
    }

    /**
     * @return string
     */
    public static function profileConnectUrl() {
        return url('profile/linkedinconnect', true).'?userID='.Gdn::session()->UserID;
    }

    protected $_RedirectUri = null;

    /**
     * @param null $newValue
     * @return null|string
     */
    public function redirectUri($newValue = null) {
        if ($newValue !== null) {
            $this->_RedirectUri = $newValue;
        } elseif ($this->_RedirectUri === null) {
            $redirectUri = url('/entry/connect/linkedin', true);
            if (strpos($redirectUri, '=') !== false) {
                $p = strrchr($redirectUri, '=');
                $uri = substr($redirectUri, 0, -strlen($p));
                $p = urlencode(ltrim($p, '='));
                $redirectUri = $uri.'='.$p;
            }

            $path = Gdn::request()->path();

            $target = val('Target', $_GET, $path ? $path : '/');
            if (ltrim($target, '/') == 'entry/signin' || ltrim($target, '/') == 'entry/linkedin' || empty($target)) {
                $target = '/';
            }
            $args = ['Target' => $target];


            $redirectUri .= strpos($redirectUri, '?') === false ? '?' : '&';
            $redirectUri .= http_build_query($args);
            $this->_RedirectUri = $redirectUri;
        }

        return $this->_RedirectUri;
    }

    /**
     * @throws Gdn_UserException
     */
    public function setup() {
        $error = '';
        if (!function_exists('curl_init')) {
            $error = concatSep("\n", $error, 'This plugin requires curl.');
        }
        if ($error) {
            throw new Gdn_UserException($error, 400);
        }

        $this->structure();
    }

    /**
     * Update database structure.
     */
    public function structure() {
        // Save the facebook provider type.
        Gdn::sql()->replace(
            'UserAuthenticationProvider',
            ['AuthenticationSchemeAlias' => self::AUTHENTICATION_SCHEME, 'URL' => '...', 'AssociationSecret' => '...', 'AssociationHashMethod' => '...'],
            ['AuthenticationKey' => self::ProviderKey],
            true
        );
    }

    /// Event Handlers ///

    /**
     * @param $sender
     * @param $args
     */
    public function base_signInIcons_handler($sender, $args) {
        echo ' '.$this->signInButton('icon').' ';
    }

    /**
     * @param $sender
     * @param $args
     */
    public function base_beforeSignInButton_handler($sender, $args) {
        echo ' '.$this->signInButton('icon').' ';
    }

    /**
     * @param $sender
     * @param $args
     * @throws Gdn_UserException
     */
    public function base_connectData_handler($sender, $args) {
        if (val(0, $args) != 'linkedin') {
            return;
        }

        $state = json_decode(Gdn::request()->get('state', ''), true);
        $suppliedStateToken = val('token', $state);
        $this->ssoUtils->verifyStateToken('linkedIn', $suppliedStateToken);

        if (isset($_GET['error'])) {
            throw new Gdn_UserException(val('error_description', $_GET, t('There was an error connecting to LinkedIn')));
        }

        $code = $sender->Request->get('code');
        $accessToken = $sender->Form->getFormValue('AccessToken');

        // Get the access token.
        if (!$accessToken && $code) {
            // Exchange the token for an access token.
            $code = urlencode($code);
            $accessToken = $this->getAccessToken($code, $this->redirectUri());
            $this->accessToken($accessToken);
            $newToken = true;
        } elseif ($accessToken) {
            $this->accessToken($accessToken);
        }

        $profile = $this->getProfile();

        $form = $sender->Form; //new gdn_Form();
        $id = val('id', $profile);
        $form->setFormValue('UniqueID', $id);
        $form->setFormValue('Provider', self::ProviderKey);
        $form->setFormValue('ProviderName', 'LinkedIn');
        $form->setFormValue('FullName', val('fullname', $profile));
        $form->setFormValue('Email', val('email', $profile));
        $form->setFormValue('Photo', val('photo', $profile));
        $form->addHidden('AccessToken', $accessToken);

        // Save some original data in the attributes of the connection for later API calls.
        $attributes = [];
        $attributes[self::ProviderKey] = [
            'AccessToken' => $accessToken,
            'Profile' => $profile
        ];
        $form->setFormValue('Attributes', $attributes);

        $sender->setData('Verified', true);
    }

    /**
     * @param $sender
     * @param $args
     */
    public function base_getConnections_handler($sender, $args) {
        if (!$this->isConfigured()) {
            return;
        }

        $profile = getValueR('User.Attributes.'.self::ProviderKey.'.Profile', $args);

        $sender->Data["Connections"][self::ProviderKey] = [
            'Icon' => $this->getWebResource('icon.png', '/'),
            'Name' => self::ProviderKey,
            'ProviderKey' => self::ProviderKey,
            'ConnectUrl' => $this->authorizeUri(self::profileConnectUrl()),
            'Profile' => [
                'Name' => val('fullname', $profile),
                'Photo' => val('photo', $profile)
            ]
        ];
    }

    /**
     * @param $sender
     * @param $args
     */
    public function entryController_signIn_handler($sender, $args) {
        if (isset($sender->Data['Methods'])) {
            // Add the facebook method to the controller.
            $method = [
                'Name' => self::ProviderKey,
                'SignInHtml' => $this->signInButton('button')
            ];

            $sender->Data['Methods'][] = $method;
        }
    }

    /**
     *
     * @param ProfileController $sender
     * @param $code
     */
    public function profileController_linkedInConnect_create($sender, $code = false) {
        $sender->permission('Garden.SignIn.Allow');

        $state = json_decode(Gdn::request()->get('state', ''), true);
        $suppliedStateToken = val('token', $state);
        $this->ssoUtils->verifyStateToken('linkedInSocial', $suppliedStateToken);

        $userID = $sender->Request->get('UserID');

        $sender->getUserInfo('', '', $userID, true);
        $sender->_SetBreadcrumbs(t('Connections'), userUrl($sender->User, '', 'connections'));

        // Get the access token.
        $accessToken = $this->getAccessToken($code, self::profileConnectUrl());
        trace($accessToken, 'AccessToken');
        $this->accessToken($accessToken);

        // Get the profile.
        $profile = $this->getProfile();

        // Save the authentication.
        Gdn::userModel()->saveAuthentication([
            'UserID' => $sender->User->UserID,
            'Provider' => self::ProviderKey,
            'UniqueID' => $profile['id']]);

        // Save the information as attributes.
        $attributes = [
            'AccessToken' => $accessToken,
            'Profile' => $profile
        ];
        Gdn::userModel()->saveAttribute($sender->User->UserID, self::ProviderKey, $attributes);

        $this->EventArguments['Provider'] = self::ProviderKey;
        $this->EventArguments['User'] = $sender->User;
        $this->fireEvent('AfterConnection');

        redirectTo(userUrl($sender->User, '', 'connections'));
    }

    /**
     * @param $sender
     * @param $args
     */
    public function socialController_linkedIn_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');
        $sender->setData('Title', t('Linked In Settings'));

        $cf = new ConfigurationModule($sender);
        $cf->initialize([
            'Plugins.LinkedIn.ApplicationID' => ['LabelCode' => 'API Key'],
            'Plugins.LinkedIn.Secret' => ['LabelCode' => 'Secret Key']
        ]);

        $sender->addSideMenu('social');
        $sender->setData('Title', sprintf(t('%s Settings'), 'LinkedIn'));
        $sender->ConfigurationModule = $cf;
        $sender->render('Settings', '', 'plugins/LinkedIn');
    }

    /**
     * Create an entry/linkedIn endpoint that redirects to the authorization URI.
     */
    public function entryController_linkedIn_create() {
        redirectTo($this->authorizeUri(), 302, false);
    }
}
