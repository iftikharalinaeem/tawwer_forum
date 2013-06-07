<?php if (!defined('APPLICATION')) exit();

$PluginInfo['samlsso'] = array(
    'Name' => 'SAML SSO',
    'Description' => 'SAML SSO for Vanilla',
    'Version' => '1.0a',
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
      $settings->idpPublicCertificate = self::TrimCert($provider['AssociationSecret']);
      $settings->requestedNameIdFormat = $provider['IdentifierFormat'];
      $settings->spIssuer = GetValue('EntityID', $provider, $provider['Name']);
      $settings->spReturnUrl = Url('/entry/connect/saml', TRUE);
      $settings->spSignoutReturnUrl = Url('/entry/signout', TRUE);
      
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
//      $x509 = openssl_x509_read($cert);
//      openssl_x509_export($x509, $cert);
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
		assert('array_key_exists("SAMLResponse", $data)');
		assert('array_key_exists("SigAlg", $data)');
		assert('array_key_exists("Signature", $data)');

      $settings = $this->GetSettings();

		$query = $data['SAMLResponse'];
		$sigAlg = $data['SigAlg'];
		$signature = $data['Signature'];
      $signature = base64_decode($signature);
      
      $key = new XMLSecurityKey($sigAlg, array('type' => 'public'));
      $key->loadKey($settings->idpPublicCertificate);

		switch ($sigAlg) {
		case XMLSecurityKey::RSA_SHA1:
			if ($key->type !== XMLSecurityKey::RSA_SHA1) {
				throw new Exception('Invalid key type for validating signature on query string.');
			}
			if (!$key->verifySignature($query,$signature)) {
				return false;
			}
			break;
		default:
			throw new Exception('Unknown signature algorithm: ' . var_export($sigAlg, TRUE));
		}
	}
   
   /// Event Handlers ///
   
   public function EntryController_OverrideSignIn_Handler($Sender, $Args) {
      $Provider = $Args['DefaultProvider'];
      if ($Provider['AuthenticationSchemeAlias'] != 'saml')
         return;
      
      $settings = $this->GetSettings();
      $request = new OneLogin_Saml_AuthRequest($settings);
      $url = $request->getRedirectUrl();
      Redirect($url);
   }
   
   public function EntryController_OverrideSignOut_Handler($Sender, $Args) {
      $Provider = $Args['DefaultProvider'];
      if ($Provider['AuthenticationSchemeAlias'] != 'saml')
         return;
      
      $get = $Sender->Request->Get();
      $samlResponse = $Sender->Request->Get('SAMLResponse');
      $settings = $this->GetSettings();
      
      if ($samlResponse) {
         $valid = $this->validateSignature($get);
         
         echo "valid";
         var_dump($valid);
         
         $respnseXml = gzinflate(base64_decode($samlResponse));
         $response = new OneLogin_Saml_Response($settings, base64_encode($respnseXml));
         decho($response);
         die();
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
      
      $Sender->SetData('Verified', TRUE);
   }
   
   protected function rval($name, $array, $default = false) {
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
   public function SettingsController_SamlSSO_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $this->Sender = $Sender;
      
      $Model = new Gdn_AuthenticationProviderModel();
      $Form = new Gdn_Form();
      $Form->SetModel($Model);
      $Sender->Form = $Form;
      
      if ($Form->AuthenticatedPostBack()) {
         $Form->SetFormValue('AuthenticationKey', 'saml');
         
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
         'AssociationSecret' => array('LabelCode' => 'Certificate', 'Options' => array('Multiline' => TRUE, 'Class' => 'TextBox BigInput')),
         'IdentifierFormat' => array('Options' => array('Class' => 'InputBox BigInput')),
         'IsDefault' => array('Control' => 'CheckBox', 'LabelCode' => 'Make this connection your default signin method.')
         );
      $Sender->SetData('_Form', $_Form);

      $Sender->AddSideMenu();
      $Sender->SetData('Title', sprintf(T('%s Settings'), 'SAML SSO'));
      $this->Render('Settings');
   }
}