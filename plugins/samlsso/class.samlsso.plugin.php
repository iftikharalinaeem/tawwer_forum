<?php if (!defined('APPLICATION')) exit();

$PluginInfo['samlsso'] = array(
    'Name' => 'SAML SSO',
    'Description' => 'SAML SSO for Vanilla',
    'Version' => '1.0b',
    'RequiredApplications' => array('Vanilla' => '2.1a'),
    'RequiredTheme' => FALSE,
    'RequiredPlugins' => FALSE,
    'HasLocale' => FALSE,
    'SettingsUrl' => '/settings/samlsso',
    'SettingsPermission' => 'Garden.Settings.Manage'
);

class SamlSSOPlugin extends Gdn_Plugin {
   /// Properties ///
   
   const ProviderKey = 'saml';
   
   /// Methods ///
   
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
            'IdentifierFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress'
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
	 * @param XMLSecurityKey $key  The key we should validate the query against.
	 */
	public function validateSignature(array $data) {
      if (!array_key_exists('SAMLResponse', $data) || !array_key_exists('SigAlg', $data) || !array_key_exists('Signature', $data))
         return false;
      
      $settings = $this->GetSettings();
      
		$sigAlg = $data['SigAlg'];
		$signature = $data['Signature'];
      $signature = base64_decode($signature);
      
      $key = new XMLSecurityKey($sigAlg, array('type' => 'public'));
      $key->loadKey($settings->idpPublicCertificate, false, true);

      $msgData = array('SAMLResponse' => $data['SAMLResponse'], 'SigAlg' => $data['SigAlg']);
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
   
   public function EntryController_Saml_Create($Sender) {
      $settings = $this->GetSettings();
      $request = new OneLogin_Saml_AuthRequest($settings);
      $url = $request->getRedirectUrl();
      Redirect($url);
   }
   
   public function EntryController_OverrideSignOut_Handler($Sender, $Args) {
      $Provider = $Args['DefaultProvider'];
      if ($Provider['AuthenticationSchemeAlias'] != 'saml')
         return;
      
      // Prevent
      SaveToConfig('Garden.SSO.Signout', 'none', FALSE);
      
      $get = $Sender->Request->Get();
      $samlResponse = $Sender->Request->Get('SAMLResponse');
      $settings = $this->GetSettings();
      
      if ($samlResponse) {
         $valid = $this->validateSignature($get);
         
         if ($valid) {
            Gdn::Session()->End();
            Redirect('/');
         }
      } else {
         $request = new OneLogin_Saml_LogoutRequest($settings);
         $url = $request->getRedirectUrl();
         Redirect($url);
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
         if (!$response->isValid()) {
            throw new Gdn_UserException("The saml response was not valid.");
         }
         $id = $response->getNameId();
         $profile = $response->getAttributes();
         Gdn::Session()->Stash('samlsso', array('id' => $id, 'profile' => $profile));
      }
      
      $provider = $this->Provider();
      
      Trace($id, 'id');
      Trace($profile, 'profile');
      
      // TODO: Throw an event so that other plugins can add/remove stuff from the basic sso.
      
      $Form = $Sender->Form; //new Gdn_Form();
      $Form->SetFormValue('UniqueID', $this->rval('eduPersonTargetedID', $profile));
      $Form->SetFormValue('Provider', self::ProviderKey);
      $Form->SetFormValue('ProviderName', $provider['Name']);
      $Form->SetFormValue('ConnectName', $this->rval('uid', $profile));
      $Form->SetFormValue('Name', $this->rval('uid', $profile));
      $Form->SetFormValue('FullName', $this->rval('cn', $profile));
      $Form->SetFormValue('Email', $this->rval('mail', $profile));
      $Form->SetFormValue('Photo', $this->rval('photo', $profile));
      
      $this->EventArguments['Profile'] = $profile;
      $this->EventArguments['Form'] = $Form;
      $this->FireEvent('SamlData');
      
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