<?php
/**
 *
 *
 * @author Todd Burry
 * @copyright 2014-2015 Vanilla Forums
 * @package samlsso
 */

/**
 * Class SamlSSOPlugin
 */
class SamlSSOPlugin extends SSOAddon {

    /**  */
    const IdentifierFormat = 'urn:oasis:names:tc:SAML:2.0:nameid-format:unspecified';
    private const AUTHENTICATION_SCHEME = 'saml';

    /**
     * Get the AuthenticationSchemeAlias value.
     *
     * @return string The AuthenticationSchemeAlias.
     */
    protected function getAuthenticationSchemeAlias(): string {
        return self::AUTHENTICATION_SCHEME;
    }

    /**
     * Insert css file for custom styling of signin button/icon.
     *
     * @param AssetModel $sender.
     */
    public function assetModel_styleCss_handler($sender) {
        $sender->addCssFile('saml.css', 'plugins/samlsso');
    }

    /**
     * Force a SAML authentication to the identity provider.
     *
     * Required by some customizations; do not remove.
     *
     * @param string $authenticationKey The key in the AuthenticationProvider table
     * @param bool $passive Whether or not to make a passive request.
     * @param string $target The target url to redirect to after the signin.
     */
    public function authenticate($authenticationKey = null, $passive = false, $target = false) {
        $settings = $this->getSettings($authenticationKey);
        $request = new OneLogin_Saml_AuthRequest($settings);
        $request->isPassive = $passive;
        $request->relayState = $target;
        $url = $request->getRedirectUrl();
        Gdn::session()->stash('samlsso', null, true);
        Logger::event('saml_authrequest_sent', Logger::INFO, 'SAML request {requetid} sent to {requesthost}.',
             ['requestid' => $request->lastID, 'requesthost' => parse_url($url, PHP_URL_HOST), 'requesturl' => $url]);
        redirectTo($url, 302, false);
    }

    /**
     * Inject a sign-in icon into the ME menu.
     *
     * @param Gdn_Controller $sender.
     * @param Gdn_Controller $args.
     */
    public function base_beforeSignInButton_handler($sender, $args) {
        if (!$this->isConfigured() || $this->isDefault()) {
            return;
        }

        $providers = $this->getProvider();
        foreach ($providers as $provider) {
            if ($provider['Active']) {
                echo ' '.$this->signInButton($provider).' ';
            }
        }

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
        if (!$this->isConfigured()) {
            return;
        }

        $providers = $this->getProvider();
        foreach ($providers as $provider) {
            if ($provider['Active']) {
                $method = [
                    'Name' => $provider['Name'],
                    'SignInHtml' => $this->signInButton($provider),
                ];

                $sender->Data['Methods'][] = $method;
            }
        }
    }

    /**
     *
     */
    public static function requireFiles() {
        $root = dirname(__FILE__);
        require_once "$root/saml/xmlseclibs.php";
        require_once "$root/saml/AuthRequest.php";
        require_once "$root/saml/Metadata.php";
        require_once "$root/saml/Response.php";
        require_once "$root/saml/Settings.php";
        require_once "$root/saml/LogoutRequest.php";
        require_once "$root/saml/LogoutResponse.php";
        require_once "$root/saml/XmlSec.php";
    }

    /**
     * @param Gdn_Controller $sender
     */
    public function base_render_before($sender) {
        if ($this->isDefault()) {
            saveToConfig('Garden.SignIn.Popup', false, false);
        }
    }

    /**
     *
     *
     * @param EntryController $sender Sending instance.
     * @param array $args Event's arguments.
     */
    public function entryController_saml_create($sender, $args) {
        $settings = $this->getSettings(val(0, $args));
        $request = new OneLogin_Saml_AuthRequest($settings);
        $request->isPassive = (bool)$sender->Request->get('ispassive');

        if ($target = Gdn::request()->get('Target')) {
            $request->relayState = $target;
        }

        $url = $request->getRedirectUrl();
        Gdn::session()->stash('samlsso', null, true);
        Logger::event('saml_authrequest_sent', Logger::INFO, 'SAML request {requestid} sent to {requesthost}.',
             ['requestid' => $request->lastID, 'requesthost' => parse_url(''), 'requesturl' => $url]
        );
        redirectTo($url, 302, false);
    }

    /**
     *
     *
     * @param EntryController $sender Sending instance.
     * @param array $args Event's arguments.
     */
    public function entryController_overrideSignIn_handler($sender, $args) {
        $provider = $args['DefaultProvider'];
        if ($provider['AuthenticationSchemeAlias'] != self::AUTHENTICATION_SCHEME) {
            return;
        }

        if (val('Target', $args)) {
            $redirectURL ='/entry/saml/'.$provider['AuthenticationKey'].'?'. http_build_query(['Target' => val('Target', $args)]);
        } else {
            $redirectURL = '/entry/saml/'.$provider['AuthenticationKey'];
        }
        redirectTo($redirectURL);
    }

    /**
     * Handle the SSO connection.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     * @throws Exception
     */
    public function base_connectData_handler($sender, $args) {
        if (val(0, $args) != 'saml') {
            return;
        }

        $authenticationKey = $sender->Request->get('authKey');
        // Backward compatibility
        if (!$authenticationKey) {
            $authenticationKey = 'saml';
        }

        if (!$sender->Request->isPostBack()) {
            throw forbiddenException('GET');
        }

        $saml = Gdn::session()->stash('samlsso', '', false);
        if ($saml) {
            // The SAML session has been retrieved.
            $id = val('id', $saml);
            $profile = val('profile', $saml);
        } else {
            // Grab the SAML session from the SAML response.
            $settings = $this->getSettings($authenticationKey);
            $response = new OneLogin_Saml_Response($settings, $sender->Request->post('SAMLResponse'));

            Logger::event('saml_response_received', Logger::INFO, "SAML response received.", (array)$response->assertion);

            try {
                if (!$response->isValid()) {
                    throw new Gdn_UserException('The SAML response was not valid.');
                }
            } catch (Exception $ex) {
                Logger::event('saml_response_invalid', Logger::ERROR, $ex->getMessage(), ['code' => $ex->getCode()]);
                throw $ex;
            }
            $id = $response->getNameId();
            $profile = $response->getAttributes();
            Gdn::session()->stash('samlsso', ['id' => $id, 'profile' => $profile]);
        }

        $provider = $this->getProvider($authenticationKey);

        $form = $sender->Form; //new gdn_Form();
        $form->setFormValue('UniqueID', $id);
        $form->setFormValue('Provider', $authenticationKey);
        $form->setFormValue('ProviderName', $provider['Name']);
        $fields = [
            'uid' => 'Name',
            'cn' => 'FullName',
            'mail' => 'Email',
            'photo' => 'Photo',
        ];
        // Do not set the fields if they are not present.
        foreach ($fields as $samlKey => $field) {
            if ($value = $this->rval($samlKey, $profile)) {
                $form->setFormValue($field, $value);
            }
        }


        $roles = $this->rval('roles', $profile);
        if ($roles) {
             $form->setFormValue('Roles', $roles);
        }

        // Set the target from common items.
        if ($relay_state = $sender->Request->post('RelayState')) {
            if ((isUrl($relay_state) || preg_match('`^[/a-z]`i', $relay_state)) && strripos($relay_state, '/entry/connect/saml') === false) {
                $form->setFormValue('Target', $relay_state);
            }
        }

        Logger::event('saml_profile', Logger::INFO, 'Profile Received from SAML', ['profile' => $profile]);

        // Loop through profile, which is an array of arrays, and convert the values to string.
        $convertedProfile = [];
        foreach ($profile as $name => $value) {
            $convertedProfile[$name] = (is_array($value)) ? implode(",", $value) : $value;
        }

        // Populate the form with values from the profile so they can be saved in UserMeta.
        $formValues = array_replace($form->formValues(), $convertedProfile);

        // Remove UserID if it is passed over SSO, it will trigger unwanted behaviour in entry/connect
        unset($formValues['UserID']);

        $form->formValues($formValues);

        $this->EventArguments['Profile'] = $profile;
        $this->EventArguments['Form'] = $form;

        // Throw an event so that other plugins can add/remove stuff from the basic sso.
        $this->fireEvent('SamlData');

        SpamModel::disabled(true);
        $sender->setData('Trusted', true);
        $sender->setData('Verified', true);
    }

    /**
     *
     *
     * @param EntryController $sender Sending instance.
     * @param array $args Event's arguments.
     * @throws Gdn_UserException
     */
    public function entryController_overrideSignOut_handler($sender, $args) {
        $provider = $args['DefaultProvider'];
        if ($provider['AuthenticationSchemeAlias'] != self::AUTHENTICATION_SCHEME || !$provider['SignOutUrl']) {
            return;
        }

        $authenticationKey = $provider['AuthenticationKey'];
        saveToConfig('Garden.SSO.Signout', 'none', false);

        $get = $sender->Request->get();
        $samlRequest = $sender->Request->get('SAMLRequest');
        $samlResponse = $sender->Request->get('SAMLResponse');
        $settings = $this->getSettings($authenticationKey);

        if ($samlRequest) {
            // The user signed out from the other site.
            $valid = $this->validateSignature($get, $settings, 'SAMLRequest', $id);

            if ($valid) {
                Gdn::session()->end();

                $response = new OneLogin_Saml_LogoutResponse($settings, $id, ['RelayState' => Gdn::request()->get('RelayState')]);
                $url = $response->getRedirectUrl();
                redirectTo($url, 302, false);
            } else {
                throw new Gdn_UserException('The SAMLRequest signature was not valid.');
            }
        } elseif ($samlResponse) {
            // The user signed out from vanilla and is now coming back.
            $valid = $this->validateSignature($get, $settings, 'SAMLResponse');

            if ($valid) {
                Gdn::session()->end();
                redirectTo('/');
            }
        } else {
            $saml = Gdn::session()->stash('samlsso', '', false);
            if (!val('SignoutWithSAML', $provider)
                 && (Gdn::session()->validateTransientKey($args['TransientKey']) || Gdn::request()->isPostBack())) {

                 Gdn::session()->end();
            }

            // The user is signing out from Vanilla and must make a request.
            if (val('idpSingleSignOutUrl', $settings)) {
                 $request = new OneLogin_Saml_LogoutRequest($settings);
                 $url = $request->getRedirectUrl($saml['id']);
                 redirectTo($url, 302, false);
            }
        }
    }

    /**
     *
     *
     * @param SettingsController $sender Sending instance.
     * @param string $authenticationKey SAML authentication key
     * @param string $action
     */
    public function settingsController_samlSSO_create($sender, $authenticationKey = '', $action = '') {
        $sender->permission('Garden.Settings.Manage');
        $this->Sender = $sender;

        if ($authenticationKey && in_array($action, ['metadata', 'metadata.xml'])) {
            return $this->metaData($authenticationKey);
        }

        $providers = $this->getProvider();
        $sender->setData('Providers', $providers);

        $sender->setData('Title', sprintf(t('%s Settings'), 'SAML SSO'));
        $this->render('settings');
    }

    /**
     * Extract the profile values from the response, translating the keys to match our keys.
     *
     * @param string $name
     * @param array $array
     * @param bool|false $default
     * @return array|bool|mixed
     */
    public function rval($name, $array, $default = false) {
        $authenticationKey = Gdn::request()->get('authKey');
        // Backward compatibility
        if (!$authenticationKey) {
            $authenticationKey = 'saml';
        }
        $provider = $this->getProvider($authenticationKey);
        $name = val($name, $provider['KeyMap'], $name);
        if (isset($array[$name])) {
            $val = $array[$name];

            if (is_array($val))
                $val = array_pop($val);

            return $val;
        }
        return $default;
    }

    /**
     * Validate the signature on a HTTP-redirect message.
     *
     * Throws an exception if we are unable to validate the signature.
     *
     * @param array $data  The data we need to validate the query string.
     * @param OneLogin_Saml_Settings $settings SAML plugin settings
     * @param string $name  The key we should validate the query against.
     * @param string $id
     * @return bool True if valid, false otherwise.
     */
    public function validateSignature(array $data, $settings, $name, &$id = null) {
        if (!array_key_exists($name, $data) || !array_key_exists('SigAlg', $data) || !array_key_exists('Signature', $data)) {
            return false;
        }

        $sigAlg = $data['SigAlg'];
        $signature = $data['Signature'];
        $signature = base64_decode($signature);

        // Get the id from the SAML.
        $saml = gzinflate(base64_decode($data[$name]));
        $xml = new SimpleXMLElement($saml);
        $id = (string)$xml['ID'];

        $key = new XMLSecurityKey($sigAlg, ['type' => 'public']);
        $key->loadKey($settings->idpPublicCertificate, false, true);

        $msgData = [$name => $data[$name]];
        if (array_key_exists('RelayState', $data)) {
            $msgData['RelayState'] = $data['RelayState'];
        }
        $msgData['SigAlg'] = $data['SigAlg'];
        $msg = http_build_query($msgData);

        $valid = $key->verifySignature($msg, $signature);

        return $valid;
    }


    /**
     * Return SAML settings.
     *
     * @param string $authenticationKey SAML authentication key
     * @return OneLogin_Saml_Settings
     */
    public function getSettings($authenticationKey = null) {
        self::requireFiles();
        $settings = new OneLogin_Saml_Settings();
        $provider = $this->getProvider($authenticationKey);

        if (!$provider) {
            throw new Gdn_UserException("The SAML connection appears to be misconfigured: Connection ID of '".htmlspecialchars($authenticationKey)."' could not be found. Please contact the forum admin.", 404);
        }

        $settings->idpSingleSignOnUrl = $provider['SignInUrl'];
        $settings->idpSingleSignOutUrl = $provider['SignOutUrl'];
        $settings->idpPublicCertificate = $provider['AssociationSecret'];
        $settings->requestedNameIdFormat = $provider['IdentifierFormat'];
        $settings->spIssuer = val('EntityID', $provider, $provider['Name']);
        $settings->spReturnUrl = url('/entry/connect/saml?authKey='.urlencode($provider['AuthenticationKey']), true);
        $settings->spSignoutReturnUrl = url('/entry/signout', true);
        $settings->spPrivateKey = val('SpPrivateKey', $provider);
        $settings->spCertificate = val('SpCertificate', $provider);

        return $settings;
    }

    /**
     * Check if there is enough data to connect to an authentication provider.
     *
     * @return bool True if there is a secret and a client_id, false if not.
     */
    protected function isConfigured() {
        $providers = $this->getProvider();
        foreach($providers as $provider) {
            if ($provider['AssociationSecret'] && $provider['EntityID'] && $provider['SignInUrl'] && $provider['Active']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check authentication provider table to see if this is the default method for logging in.
     *
     * @return mixed Return the default samlProvider or false.
     */
    public function isDefault() {
        return (bool)$this->getDefaultProvider();
    }

    /**
     * Check authentication provider table to see if this is the default method for logging in.
     *
     * @return mixed Return the default samlProvider or false.
     */
    public function getDefaultProvider() {
        static $result;

        if ($result === null) {
            $result = false;

            $providers = $this->getProvider();
            foreach ($providers as $provider) {
                if ($provider['IsDefault']) {
                    $result = $provider;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Return a specific or all SAML provider.
     *
     * @param string $authenticationKey SAML authentication key
     * @return array A specific or all SAML provider.
     */
    private function getProvider($authenticationKey = null) {
        if ($authenticationKey !== null) {
            $where = ['AuthenticationKey' => $authenticationKey];
        } else {
            $where = ['AuthenticationSchemeAlias' => self::AUTHENTICATION_SCHEME];
        }

        $dataSet = Gdn::sql()->getWhere('UserAuthenticationProvider', $where);
        $dataSet->expandAttributes();
        $result = $dataSet->resultArray();

        if ($authenticationKey) {
            return val(0, $result);
        } else {
            return $result;
        }
    }

    /**
     * Create signup button specific to this plugin.
     *
     * @param array $provider
     * @return string Resulting HTML element (button).
     */
    protected function signInButton($provider) {
        return anchor(
            sprintf(t('Sign In with %s'), $provider['Name']),
            '/entry/saml/'.$provider['AuthenticationKey'],
            'Button Primary SignInLink',
            ['rel' => 'nofollow']
        );
    }

    /**
     *
     *
     * @param string $authenticationKey SAML authentication key
     */
    protected function metaData($authenticationKey) {
        $settings = $this->getSettings($authenticationKey);
        $meta = new OneLogin_Saml_Metadata($settings);
        $meta->validSeconds = strtotime(c('Plugins.samlsso.ValidSeconds', '+5 years'), 0);

        header('Content-Type: application/xml; charset=UTF-8');
        die($meta->getXml());
    }

    /**
     *
     *
     * @param string $cert
     * @return string
     */
    public static function trimCert($cert) {
        $cert = preg_replace('`-----[^-]*-----`i', '', $cert);
        $cert = trim(str_replace(["\r", "\n"], '', $cert));
        return $cert;
    }


    /**
     *
     *
     * @param string $cert
     * @param string $type
     * @return string
     */
    public static function untrimCert($cert, $type = 'CERTIFICATE') {
        if (strpos($cert, '---BEGIN') === false) {
            // Convert the secret to a proper x509 certificate.
            $x509cert = trim(str_replace(["\r", "\n"], "", $cert));
            $x509cert = "-----BEGIN $type-----\n".chunk_split($x509cert, 64, "\n")."-----END $type-----\n";
            $cert = $x509cert;
        }
        return $cert;
    }
}