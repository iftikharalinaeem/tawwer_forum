<?php
/**
 *
 *
 * @author Todd Burry
 * @copyright 2014-2015 Vanilla Forums
 * @package samlsso
 */

$PluginInfo['samlsso'] = array(
     'Name' => 'SAML SSO',
     'Description' => 'Allows Vanilla to SSO to a SAML 2.0 compliant identity provider.',
     'Version' => '1.3.0',
     'RequiredApplications' => array('Vanilla' => '2.1'),
     'RequiredTheme' => false,
     'RequiredPlugins' => false,
     'HasLocale' => false,
     'SettingsUrl' => '/settings/samlsso',
     'SettingsPermission' => 'Garden.Settings.Manage',
     'MobileFriendly' => true
);

/**
 * Class SamlSSOPlugin
 */
class SamlSSOPlugin extends Gdn_Plugin {

    /**  */
    const ProviderKey = 'saml';

    /** @var null  */
    protected $_Provider = null;

    /**
     * Force a saml authentication to the identity provider.
     *
     * @param bool $passive Whether or not to make a passive request.
     * @param string $target The target url to redirect to after the signin.
     */
    public function authenticate($passive = false, $target = false) {
        $settings = $this->getSettings();
        $request = new OneLogin_Saml_AuthRequest($settings);
        $request->isPassive = $passive;
        $request->relayState = $target;
        $url = $request->getRedirectUrl();
        Gdn::session()->stash('samlsso', null, true);
        Logger::event('saml_authrequest_sent', Logger::INFO, 'SAML request {requetid} sent to {requesthost}.',
             array('requestid' => $request->lastID, 'requesthost' => parse_url($url, PHP_URL_HOST), 'requesturl' => $url));
        redirect($url);
    }

    /**
     * @return OneLogin_Saml_Settings
     */
    public function getSettings() {
        self::requireFiles();
        $settings = new OneLogin_Saml_Settings();
        $provider = $this->provider();
        $settings->idpSingleSignOnUrl = $provider['SignInUrl'];
        $settings->idpSingleSignOutUrl = $provider['SignOutUrl'];
        $settings->idpPublicCertificate = $provider['AssociationSecret'];
        $settings->requestedNameIdFormat = $provider['IdentifierFormat'];
        $settings->spIssuer = val('EntityID', $provider, $provider['Name']);
        $settings->spReturnUrl = url('/entry/connect/saml', true);
        $settings->spSignoutReturnUrl = url('/entry/signout', true);
        $settings->spPrivateKey = val('SpPrivateKey', $provider);
        $settings->spCertificate = val('SpCertificate', $provider);

        return $settings;
    }

    /**
     *
     */
    public function provider() {
        if ($this->_Provider === null) {
            $this->_Provider = Gdn_AuthenticationProviderModel::getProviderByKey('saml');
        }
        return $this->_Provider;
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
     *
     */
    public function setup() {
        $this->structure();
    }

    /**
     *
     *
     * @param $cert
     * @return mixed|string
     */
    public static function trimCert($cert) {
        $cert = preg_replace('`-----[^-]*-----`i', '', $cert);
        $cert = trim(str_replace(array("\r", "\n"), '', $cert));
        return $cert;
    }

    /**
     *
     *
     * @param $cert
     * @param string $type
     * @return string
     */
    public static function untrimCert($cert, $type = 'CERTIFICATE') {
        if (strpos($cert, '---BEGIN') === false) {
            // Convert the secret to a proper x509 certificate.
            $x509cert = trim(str_replace(array("\r", "\n"), "", $cert));
            $x509cert = "-----BEGIN $type-----\n".chunk_split($x509cert, 64, "\n")."-----END $type-----\n";
            $cert = $x509cert;
        }
        return $cert;
    }

    /**
     *
     */
    public function structure() {
        // Make sure we have the saml provider.
        $Provider = Gdn_AuthenticationProviderModel::getProviderByKey('saml');

        if (!$Provider) {
            $Model = new Gdn_AuthenticationProviderModel();
            $Provider = array(
                'AuthenticationKey' => 'saml',
                'AuthenticationSchemeAlias' => 'saml',
                'Name' => C('Garden.Title'),
                'IdentifierFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:unspecified'
                );

            $Model->save($Provider);
        }
    }

   /**
	 * Validate the signature on a HTTP-Redirect message.
	 *
	 * Throws an exception if we are unable to validate the signature.
	 *
	 * @param array $data  The data we need to validate the query string.
	 * @param string $name  The key we should validate the query against.
	 */
	public function validateSignature(array $data, $name = 'SAMLResponse', &$id = null) {
        if (!array_key_exists($name, $data) || !array_key_exists('SigAlg', $data) || !array_key_exists('Signature', $data)) {
            return false;
        }

        $settings = $this->getSettings();

		$sigAlg = $data['SigAlg'];
		$signature = $data['Signature'];
        $signature = base64_decode($signature);

        // Get the id from the saml.
        $saml = gzinflate(base64_decode($data[$name]));
        $xml = new SimpleXMLElement($saml);
        $id = (string)$xml['ID'];

        $key = new XMLSecurityKey($sigAlg, array('type' => 'public'));
        $key->loadKey($settings->idpPublicCertificate, false, true);

        $msgData = array($name => $data[$name]);
        if (array_key_exists('RelayState', $data)) {
            $msgData['RelayState'] = $data['RelayState'];
        }
        $msgData['SigAlg'] = $data['SigAlg'];
        $msg = http_build_query($msgData);

        $valid = $key->verifySignature($msg, $signature);

        return $valid;
	}

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function entryController_overrideSignIn_handler($Sender, $Args) {
        $Provider = $Args['DefaultProvider'];
        if ($Provider['AuthenticationSchemeAlias'] != 'saml')
            return;

        $this->entryController_saml_create($Sender);
    }

    /**
     *
     *
     * @param EntryController $Sender
     */
    public function entryController_saml_create($Sender) {
        $settings = $this->getSettings();
        $request = new OneLogin_Saml_AuthRequest($settings);
        $request->isPassive = (bool)$Sender->Request->get('ispassive');

        if ($target = Gdn::request()->get('Target'))
            $request->relayState = $target;

        $url = $request->getRedirectUrl();
        Gdn::session()->stash('samlsso', null, true);
        Logger::event('saml_authrequest_sent', Logger::INFO, 'SAML request {requestid} sent to {requesthost}.',
             array('requestid' => $request->lastID, 'requesthost' => parse_url(''), 'requesturl' => $url));
        redirect($url);
    }

    /**
     *
     *
     * @param $sender
     * @param $args
     * @throws Gdn_UserException
     */
    public function entryController_overrideSignOut_handler($sender, $args) {
        $provider = $args['DefaultProvider'];
        if ($provider['AuthenticationSchemeAlias'] != 'saml' || !$provider['SignOutUrl']) {
            return;
        }

        saveToConfig('Garden.SSO.Signout', 'none', false);

        $get = $sender->Request->get();
        $samlRequest = $sender->Request->get('SAMLRequest');
        $samlResponse = $sender->Request->get('SAMLResponse');
        $settings = $this->getSettings();

        if ($samlRequest) {
            // The user signed out from the other site.
            $valid = $this->validateSignature($get, 'SAMLRequest', $id);

            if ($valid) {
                Gdn::session()->end();

                $response = new OneLogin_Saml_LogoutResponse($settings, $id, array('RelayState' => Gdn::request()->get('RelayState')));
                $url = $response->getRedirectUrl();
                Redirect($url);
            } else {
                throw new Gdn_UserException('The SAMLRequest signature was not valid.');
            }
        } elseif ($samlResponse) {
            // The user signed out from vanilla and is now coming back.
            $valid = $this->validateSignature($get, 'SAMLResponse');

            if ($valid) {
                Gdn::session()->end();
                Redirect('/');
            }
        } else {
            if (!val('SignoutWithSAML', $provider)
                 && (Gdn::session()->validateTransientKey($args['TransientKey']) || Gdn::request()->isPostBack())) {

                 Gdn::session()->end();
            }

            // The user is signing out from Vanilla and must make a request.
            if (val('idpSingleSignOutUrl', $settings)) {
                 $request = new OneLogin_Saml_LogoutRequest($settings);
                 $url = $request->getRedirectUrl();
                 redirect($url);
            }
        }
    }

    /**
     *
     *
     * @param Gdn_Controller $Sender
     * @param array $Args
     */
    public function base_connectData_handler($Sender, $Args) {
        if (GetValue(0, $Args) != 'saml')
            return;

        if (!$Sender->Request->isPostBack())
            throw ForbiddenException('GET');

        $saml = Gdn::session()->stash('samlsso', '', false);
        if ($saml) {
            // The saml session has been retreived.
            $id = $saml['id'];
            $profile = $saml['profile'];
        } else {
            // Grab the saml session from the saml response.
            $settings = $this->getSettings();
            $response = new OneLogin_Saml_Response($settings, $Sender->Request->post('SAMLResponse'));
//            $xml = $response->document->saveXML();

            Logger::event('saml_response_received', Logger::INFO, "SAML response received.");

            try {
                if (!$response->isValid()) {
                    throw new Gdn_UserException('The saml response was not valid.');
                }
            } catch (Exception $ex) {
                Logger::event('saml_response_invalid', Logger::ERROR, $ex->getMessage(), array('code' => $ex->getCode()));
                throw $ex;
            }
            $id = $response->getNameId();
            $profile = $response->getAttributes();
            Gdn::session()->stash('samlsso', array('id' => $id, 'profile' => $profile));
        }

        $provider = $this->provider();

        $Form = $Sender->Form; //new Gdn_Form();
        $Form->setFormValue('UniqueID', $id);
        $Form->setFormValue('Provider', self::ProviderKey);
        $Form->setFormValue('ProviderName', $provider['Name']);
        $Form->setFormValue('Name', $this->rval('uid', $profile));
        $Form->setFormValue('FullName', $this->rval('cn', $profile));
        $Form->setFormValue('Email', $this->rval('mail', $profile));
        $Form->setFormValue('Photo', $this->rval('photo', $profile));

        // Don't overwrite ConnectName if it already exists.
        if (!$Form->getFormValue('ConnectName', false)) {
            $Form->setFormValue('ConnectName', $this->rval('uid', $profile));
        }

        $roles = $this->rval('roles', $profile);
        if ($roles) {
             $Form->setFormValue('Roles', $roles);
        }

        // Set the target from common items.
        if ($relay_state = $Sender->Request->Post('RelayState')) {
            if (IsUrl($relay_state) || preg_match('`^[/a-z]`i', $relay_state))
                $Form->setFormValue('Target', $relay_state);
        }

        $this->EventArguments['Profile'] = $profile;
        $this->EventArguments['Form'] = $Form;

        // Throw an event so that other plugins can add/remove stuff from the basic sso.
        $this->fireEvent('SamlData');

        SpamModel::disabled(true);
        $Sender->setData('Trusted', true);
        $Sender->setData('Verified', true);
    }

    /**
     * @param Gdn_Controller $sender
     */
    public function base_render_before($sender) {
        saveToConfig('Garden.SignIn.Popup', false, false);
    }

    /**
     *
     *
     * @param $name
     * @param $array
     * @param bool|false $default
     * @return array|bool|mixed
     */
    public function rval($name, $array, $default = false) {
        if (isset($array[$name])) {
            $val = $array[$name];

            if (is_array($val))
                $val = array_pop($val);

            return $val;
        }
        return $default;
    }

    /**
     *
     *
     * @param SettingsController $Sender
     * @param string $Action
     */
    public function settingsController_samlSSO_create($Sender, $Action = '') {
        $Sender->permission('Garden.Settings.Manage');
        $this->Sender = $Sender;

        switch ($Action) {
            case 'metadata':
            case 'metadata.xml':
                return $this->metaData($Sender);
                break;
        }

        $Model = new Gdn_AuthenticationProviderModel();
        $Form = new Gdn_Form();
        $Form->setModel($Model);
        $Sender->Form = $Form;

        if ($Form->authenticatedPostBack()) {
            $Form->setFormValue('AuthenticationKey', 'saml');

            // Make sure the key is in the correct form.
            $secret = $Form->getFormValue('AssociationSecret');
            $Form->setFormValue('AssociationSecret', self::untrimCert($secret));

            $key = $Form->getFormValue('PrivateKey');
            $Form->SetFormValue('PrivateKey', self::untrimCert($key, 'RSA PRIVATE KEY'));

            $key = $Form->getFormValue('PublicKey');
            $Form->setFormValue('PublicKey', self::untrimCert($key, 'RSA PUBLIC KEY'));

            if ($Form->save()) {
                $Sender->informMessage(T('Saved'));
            }
        } else {
            $Provider = Gdn_AuthenticationProviderModel::getProviderByKey('saml');
            Trace($Provider);
            $Form->setData($Provider);
        }

        // Set up the form.
        $_Form = array(
            'EntityID' => array(),
            'Name' => array(),
            'SignInUrl' => array('Options' => array('Class' => 'InputBox BigInput')),
            'SignOutUrl' => array('Options' => array('Class' => 'InputBox BigInput')),
            'RegisterUrl' => array('Options' => array('Class' => 'InputBox BigInput')),
            'AssociationSecret' => array('LabelCode' => 'IDP Certificate', 'Options' => array('Multiline' => true, 'Class' => 'TextBox BigInput')),
            'IdentifierFormat' => array('Options' => array('Class' => 'InputBox BigInput')),
            'IsDefault' => array('Control' => 'CheckBox', 'LabelCode' => 'Make this connection your default signin method.'),
            'SpPrivateKey' => array('LabelCode' => 'SP Private Key', 'Description' => 'If you want to sign your requests then you need this key.', 'Options' => array('Multiline' => TRUE, 'Class' => 'TextBox BigInput')),
            'SpCertificate' => array('LabelCode' => 'SP Certificate', 'Description' => 'This is the certificate that you will give to your IDP.', 'Options' => array('Multiline' => TRUE, 'Class' => 'TextBox BigInput')),
            'SignoutWithSAML' => array('Control' => 'CheckBox', 'LabelCode' => 'Only sign out with valid SAML logout requests.'),
            'Metadata' => array('Control' => 'Callback', 'Callback' => function($form) {
                    return $form->label('Metadata').
                        '<div class="Info">'.
                            'You can get the metadata for this service provider here: '.
                            Anchor('get metadata', Url('/settings/samlsso/metadata.xml'), '', array('target' => '_blank')).'.'.
                        '</div>';
                })
            );

        $Sender->setData('_Form', $_Form);
        $Sender->addSideMenu();
        $Sender->setData('Title', sprintf(t('%s Settings'), 'SAML SSO'));
        $this->render('Settings');
    }

    /**
     *
     *
     * @param $Sender
     */
    protected function metaData($Sender) {
        $Settings = $this->getSettings();
        $Meta = new OneLogin_Saml_Metadata($Settings);
        $Meta->validSeconds = strtotime(C('Plugins.samlsso.ValidSeconds', '+5 years'), 0);

        header('Content-Type: application/xml; charset=UTF-8');
        die($Meta->getXml());
    }
}
