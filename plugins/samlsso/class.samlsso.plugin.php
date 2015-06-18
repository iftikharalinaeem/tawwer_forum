<?php if (!defined('APPLICATION')) exit();

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
    'MobileFriendly' => TRUE
);

class SamlSSOPlugin extends Gdn_Plugin {
   /// Properties ///

   const ProviderKey = 'saml';

   /// Methods ///

   /**
    * Force a saml authentication to the identity provider.
    * @param bool $passive Whether or not to make a passive request.
    * @param string $target The target url to redirect to after the signin.
    */
   public function authenticate($passive = false, $target = false) {
      $settings = $this->GetSettings();
      $request = new OneLogin_Saml_AuthRequest($settings);
      $request->isPassive = $passive;
      $request->relayState = $target;
      $url = $request->getRedirectUrl();
      Gdn::Session()->Stash('samlsso', NULL, TRUE);
      Logger::event('saml_authrequest_sent', Logger::DEBUG, 'SAML request {requetid} sent to {requesthost}.',
          array('requestid' => $request->lastID, 'requesthost' => parse_url($url, PHP_URL_HOST), 'requesturl' => $url));
      Redirect($url);
   }

   /**
    * @return OneLogin_Saml_Settings
    */
   public function GetSettings() {
      self::RequireFiles();
      $settings = new OneLogin_Saml_Settings();
      $provider = $this->Provider();
      $settings->idpSingleSignOnUrl = $provider['SignInUrl'];
      $settings->idpSingleSignOutUrl = $provider['SignOutUrl'];
      $settings->idpPublicCertificate = $provider['AssociationSecret'];
      $settings->requestedNameIdFormat = $provider['IdentifierFormat'];
      $settings->spIssuer = GetValue('EntityID', $provider, $provider['Name']);
      $settings->spReturnUrl = Url('/entry/connect/saml', TRUE);
      $settings->spSignoutReturnUrl = Url('/entry/signout', TRUE);
      $settings->spPrivateKey = GetValue('SpPrivateKey', $provider);
      $settings->spCertificate = GetValue('SpCertificate', $provider);

      return $settings;
   }

   protected $_Provider = null;
   /**
    *
    */
   public function Provider() {
      if ($this->_Provider === null) {
         $this->_Provider = Gdn_AuthenticationProviderModel::GetProviderByKey('saml');
      }
      return $this->_Provider;
   }

   public static function RequireFiles() {
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

   public function Setup() {
      $this->Structure();
   }

   public static function TrimCert($cert) {
      $cert = preg_replace('`-----[^-]*-----`i', '', $cert);
      $cert = trim(str_replace(array("\r", "\n"), '', $cert));
      return $cert;
   }

   public static function UntrimCert($cert, $type = 'CERTIFICATE') {
      if (strpos($cert, '---BEGIN') === FALSE) {
         // Convert the secret to a proper x509 certificate.
         $x509cert = trim(str_replace(array("\r", "\n"), "", $cert));
         $x509cert = "-----BEGIN $type-----\n".chunk_split($x509cert, 64, "\n")."-----END $type-----\n";
         $cert = $x509cert;
      }
      return $cert;
   }

   public function Structure() {
      // Make sure we have the saml provider.
      $Provider = Gdn_AuthenticationProviderModel::GetProviderByKey('saml');

      if (!$Provider) {
         $Model = new Gdn_AuthenticationProviderModel();
         $Provider = array(
            'AuthenticationKey' => 'saml',
            'AuthenticationSchemeAlias' => 'saml',
            'Name' => C('Garden.Title'),
            'IdentifierFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:unspecified'
            );

         $Model->Save($Provider);
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
      if (!array_key_exists($name, $data) || !array_key_exists('SigAlg', $data) || !array_key_exists('Signature', $data))
         return false;

      $settings = $this->GetSettings();

		$sigAlg = $data['SigAlg'];
		$signature = $data['Signature'];
      $signature = base64_decode($signature);

      // Get the id from the saml.
      $saml = gzinflate(base64_decode($data[$name]));
      $xml = new SimpleXMLElement($saml);
      $id = (string)$xml['ID'];

      $key = new XMLSecurityKey($sigAlg, array('type' => 'public'));
//      if ($name === 'SAMLResponse') {
         $key->loadKey($settings->idpPublicCertificate, false, true);
//      } else {
//         $key->loadKey($settings->spPrivateKey, false, true);
//      }

      $msgData = array($name => $data[$name]);
      if (array_key_exists('RelayState', $data)) {
         $msgData['RelayState'] = $data['RelayState'];
      }
      $msgData['SigAlg'] = $data['SigAlg'];
      $msg = http_build_query($msgData);

      $valid = $key->verifySignature($msg, $signature);

      return $valid;
	}

   /// Event Handlers ///

   public function EntryController_OverrideSignIn_Handler($Sender, $Args) {
      $Provider = $Args['DefaultProvider'];
      if ($Provider['AuthenticationSchemeAlias'] != 'saml')
         return;

      $this->EntryController_Saml_Create($Sender);
   }

   /**
    * @param EntryController $Sender
    */
   public function EntryController_Saml_Create($Sender) {
      $settings = $this->GetSettings();
      $request = new OneLogin_Saml_AuthRequest($settings);
      $request->isPassive = (bool)$Sender->Request->Get('ispassive');

      if ($target = Gdn::Request()->Get('Target'))
         $request->relayState = $target;

      $url = $request->getRedirectUrl();
      Gdn::Session()->Stash('samlsso', NULL, TRUE);
      Logger::event('saml_authrequest_sent', Logger::DEBUG, 'SAML request {requestid} sent to {requesthost}.',
          array('requestid' => $request->lastID, 'requesthost' => parse_url(''), 'requesturl' => $url));
      Redirect($url);
   }

   public function EntryController_OverrideSignOut_Handler($sender, $args) {
      $provider = $args['DefaultProvider'];
      if ($provider['AuthenticationSchemeAlias'] != 'saml' || !$provider['SignOutUrl']) {
         return;
      }

      SaveToConfig('Garden.SSO.Signout', 'none', FALSE);

      $get = $sender->Request->Get();
      $samlRequest = $sender->Request->Get('SAMLRequest');
      $samlResponse = $sender->Request->Get('SAMLResponse');
      $settings = $this->GetSettings();

      if ($samlRequest) {
         // The user signed out from the other site.
         $valid = $this->validateSignature($get, 'SAMLRequest', $id);

         if ($valid) {
            Gdn::Session()->End();

            $response = new OneLogin_Saml_LogoutResponse($settings, $id, array('RelayState' => Gdn::Request()->Get('RelayState')));
            $url = $response->getRedirectUrl();
            Redirect($url);
         } else {
            throw new Gdn_UserException('The SAMLRequest signature was not valid.');
         }
      } elseif ($samlResponse) {
         // The user signed out from vanilla and is now coming back.
         $valid = $this->validateSignature($get, 'SAMLResponse');

         if ($valid) {
            Gdn::Session()->End();
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
    * @param EntryController $Sender
    */
   /**
    *
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
   public function Base_ConnectData_Handler($Sender, $Args) {
      if (GetValue(0, $Args) != 'saml')
         return;

      if (!$Sender->Request->IsPostBack())
         throw ForbiddenException('GET');

      $saml = Gdn::Session()->Stash('samlsso', '', false);
      if ($saml) {
         // The saml session has been retreived.
         $id = $saml['id'];
         $profile = $saml['profile'];
      } else {
         // Grab the saml session from the saml response.
         $settings = $this->GetSettings();
         $response = new OneLogin_Saml_Response($settings, $Sender->Request->Post('SAMLResponse'));
//         $xml = $response->document->saveXML();

         Logger::event('saml_response_received', Logger::DEBUG, "SAML response received.");

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
         Gdn::Session()->Stash('samlsso', array('id' => $id, 'profile' => $profile));
      }

      $provider = $this->Provider();

      $Form = $Sender->Form; //new Gdn_Form();
      $Form->SetFormValue('UniqueID', $id);
      $Form->SetFormValue('Provider', self::ProviderKey);
      $Form->SetFormValue('ProviderName', $provider['Name']);
      $Form->SetFormValue('ConnectName', $this->rval('uid', $profile));
      $Form->SetFormValue('Name', $this->rval('uid', $profile));
      $Form->SetFormValue('FullName', $this->rval('cn', $profile));
      $Form->SetFormValue('Email', $this->rval('mail', $profile));
      $Form->SetFormValue('Photo', $this->rval('photo', $profile));

      $roles = $this->rval('roles', $profile);
      if ($roles) {
          $Form->SetFormValue('Roles', $roles);
      }

      // Set the target from common items.
      if ($relay_state = $Sender->Request->Post('RelayState')) {
         if (IsUrl($relay_state) || preg_match('`^[/a-z]`i', $relay_state))
            $Form->SetFormValue('Target', $relay_state);
      }

      $this->EventArguments['Profile'] = $profile;
      $this->EventArguments['Form'] = $Form;

      // Throw an event so that other plugins can add/remove stuff from the basic sso.
      $this->FireEvent('SamlData');

      SpamModel::Disabled(TRUE);
      $Sender->SetData('Trusted', TRUE);
      $Sender->SetData('Verified', TRUE);
   }

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
    * @param SettingsController $Sender
    */
   public function SettingsController_SamlSSO_Create($Sender, $Action = '') {
      $Sender->Permission('Garden.Settings.Manage');
      $this->Sender = $Sender;

      switch ($Action) {
         case 'metadata':
         case 'metadata.xml':
            return $this->MetaData($Sender);
            break;
      }

      $Model = new Gdn_AuthenticationProviderModel();
      $Form = new Gdn_Form();
      $Form->SetModel($Model);
      $Sender->Form = $Form;

      if ($Form->AuthenticatedPostBack()) {
         $Form->SetFormValue('AuthenticationKey', 'saml');

         // Make sure the key is in the correct form.
         $secret = $Form->GetFormValue('AssociationSecret');
         $Form->SetFormValue('AssociationSecret', self::UntrimCert($secret));

         $key = $Form->GetFormValue('PrivateKey');
         $Form->SetFormValue('PrivateKey', self::UntrimCert($key, 'RSA PRIVATE KEY'));

         $key = $Form->GetFormValue('PublicKey');
         $Form->SetFormValue('PublicKey', self::UntrimCert($key, 'RSA PUBLIC KEY'));

         if ($Form->Save()) {
            $Sender->InformMessage(T('Saved'));
         }
      } else {
         $Provider = Gdn_AuthenticationProviderModel::GetProviderByKey('saml');
         Trace($Provider);
         $Form->SetData($Provider);
      }

      // Set up the form.
      $_Form = array(
         'EntityID' => array(),
         'Name' => array(),
         'SignInUrl' => array('Options' => array('Class' => 'InputBox BigInput')),
         'SignOutUrl' => array('Options' => array('Class' => 'InputBox BigInput')),
         'RegisterUrl' => array('Options' => array('Class' => 'InputBox BigInput')),
         'AssociationSecret' => array('LabelCode' => 'IDP Certificate', 'Options' => array('Multiline' => TRUE, 'Class' => 'TextBox BigInput')),
         'IdentifierFormat' => array('Options' => array('Class' => 'InputBox BigInput')),
         'IsDefault' => array('Control' => 'CheckBox', 'LabelCode' => 'Make this connection your default signin method.'),
         'SpPrivateKey' => array('LabelCode' => 'SP Private Key', 'Description' => 'If you want to sign your requests then you need this key.', 'Options' => array('Multiline' => TRUE, 'Class' => 'TextBox BigInput')),
         'SpCertificate' => array('LabelCode' => 'SP Certificate', 'Description' => 'This is the certificate that you will give to your IDP.', 'Options' => array('Multiline' => TRUE, 'Class' => 'TextBox BigInput')),
         'SignoutWithSAML' => array('Control' => 'CheckBox', 'LabelCode' => 'Only sign out with valid SAML logout requests.'),
         'Metadata' => array('Control' => 'Callback', 'Callback' => function($form) {
               return $form->Label('Metadata').
                  '<div class="Info">'.
                     'You can get the metadata for this service provider here: '.
                     Anchor('get metadata', Url('/settings/samlsso/metadata.xml'), '', array('target' => '_blank')).'.'.
                  '</div>';
            })
         );
      $Sender->SetData('_Form', $_Form);

      $Sender->AddSideMenu();
      $Sender->SetData('Title', sprintf(T('%s Settings'), 'SAML SSO'));
      $this->Render('Settings');
   }

   protected function MetaData($Sender) {
      $Settings = $this->GetSettings();
      $Meta = new OneLogin_Saml_Metadata($Settings);
      $Meta->validSeconds = strtotime(C('Plugins.samlsso.ValidSeconds', '+5 years'), 0);

      header('Content-Type: application/xml; charset=UTF-8');
      die($Meta->getXml());
   }
}
