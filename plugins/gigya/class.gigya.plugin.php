<?php if (!defined('APPLICATION')) exit();

$PluginInfo['gigya'] = array(
   'Name' => 'Gigya Sign In',
   'Description' => 'Adds single sign-on (SSO) to Gigya connect platform.',
   'Version' => '1.0',
   'SettingsUrl' => '/settings/gigya',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class GigyaPlugin extends Gdn_Plugin {
   /// Constants ///
   const PROVIDER_KEY = 'gigya';


   /// Properties ///
   protected $Provider = null;

   protected $RedirectUrl = '/entry/connect/gigya';

   /// Methods ///

   static function calcSignature($baseString, $key) {
      $baseString = utf8_encode($baseString);
      $rawHmac = hash_hmac("sha1", utf8_encode($baseString), base64_decode($key), true);
      $signature = base64_encode($rawHmac);
      return $signature;
   }

   /**
    * Gets the gigya provider.
    * @return array
    */
   public function Provider() {
      if ($this->Provider === null) {
         $this->Provider = Gdn_AuthenticationProviderModel::GetProviderByKey(self::PROVIDER_KEY);
      }
      return $this->Provider;
   }

   public function Setup() {
      $this->Structure();
   }

   public function Structure() {
      // Make sure we have the saml provider.
      $Provider = Gdn_AuthenticationProviderModel::GetProviderByKey(self::PROVIDER_KEY);

      if (!$Provider) {
         $Model = new Gdn_AuthenticationProviderModel();
         $Provider = array(
            'AuthenticationKey' => self::PROVIDER_KEY,
            'AuthenticationSchemeAlias' => self::PROVIDER_KEY,
            'Name' => C('Garden.Title'),
            'AuthenticateUrl' => 'https://socialize.gigya.com/socialize.login',

         );

         $Model->Save($Provider);
      }
   }

   public static function validateUserSignature($UID, $timestamp, $secret, $signature, $throw = false) {
      // Validate the timestamp.
      if (abs(time() - $timestamp) > 180) {
         if ($throw) {
            throw new Gdn_UserException("The sso request does not have a valid timestamp.", 400);
         }
         return false;
      }

      // Validate the signature.
      $baseString = $timestamp."_".$UID;
      $expectedSig = self::calcSignature($baseString, $secret);
      $result = $expectedSig == $signature;

      if (!$result && $throw) {
         throw new Gdn_UserException("The signature was invalid.", 400);
      }
      return $result;
   }



/// Event Handlers ///

   /**
    *
    * @param EntryController $Sender
    * @param array $Args
    */
   public function Base_ConnectData_Handler($Sender, $Args) {
      if (GetValue(0, $Args) != 'gigya')
         return;

      $Form = $Sender->Form;

      $User = json_decode($Form->GetFormValue('User', $Form->GetFormValue('User')), true);
      if (!$User) {
         throw new Gdn_UserException('Could not parse the user.');
      }

      // Validate the signature.
      $secret = val('AssociationSecret', $this->Provider());
      self::validateUserSignature($User['UID'], $User['signatureTimestamp'], $secret, $User['UIDSignature'], true);

      // Map all of the standard jsConnect data.
      $Map = array('UID' => 'UniqueID', 'email' => 'Email', 'photoURL' => 'Photo');
      foreach ($Map as $Key => $Value) {
         $Form->SetFormValue($Value, GetValue($Key, $User, ''));
      }
      if (isset($User['firstName']) && isset($User['lastName'])) {
         $Form->SetFormValue('FullName', $User['firstName'].' '.$User['lastName']);
      }

      $Form->SetFormValue('Provider', self::PROVIDER_KEY);
      $Form->SetFormValue('ProviderName', 'Gigya');
      $Form->AddHidden('User', $Form->GetFormValue('User'));

      $Sender->SetData('Verified', TRUE);
      $Sender->SetData('SSOUser', $User);
   }

   /**
    * @param Gdn_Controller $Sender
    */
   public function Base_Render_Before($Sender) {
      if (!Gdn::Session()->IsValid() && $Sender->Head) {
         $js = val('HeadTemplate', $this->Provider());
         $Sender->Head->AddString($js);

         // We need to add the gigya js file as a string so it goes after the header template.
         $url = Asset('/plugins/gigya/js/gigya.js');
         $v = val('Version', Gdn::PluginManager()->GetPluginInfo('gigya'));
         $url .= '?v='.urlencode($v);

         $Sender->Head->AddString("<script src='$url' type='text/javascript'></script>");
      }
   }

   /**
    * @param EntryController $Sender
    * @param array $Args
    * @return mixed
    */
   public function EntryController_SignIn_Create($Sender, $Method = FALSE, $Arg1 = FALSE) {
      if (!val('IsDefault', $this->Provider())) {
         return $Sender->SignIn($Method, $Arg1);
      } else {
         $Template = val('BodyTemplate', $this->Provider());
         $Sender->SetData('BodyTemplate', $Template);

         $Sender->Title(T('Sign In'));
         $Sender->Render('SignIn', '', 'plugins/gigya');
      }
   }

   /**
    *
    * @param Gdn_Controller $Sender
    */
   public function EntryController_SignIn_Handler($Sender, $Args) {
      if (isset($Sender->Data['Methods'])) {
         $Template = val('BodyTemplate', $this->Provider());

         // Add the gigya signin code.
         if ($Template) {
            $Method = array(
               'Name' => self::PROVIDER_KEY,
               'SignInHtml' => $Template);

            $Sender->Data['Methods'][] = $Method;
         }
      }
   }

   /**
    * Manage the Gigya permissions.
    * @param SettingsController $Sender
    */
   public function SettingsController_Gigya_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');

      // Grab the provider.
      $Provider = $this->Provider();
      if (!$Provider) {
         throw NotFoundException('Provider');
      }

      if ($Sender->Form->AuthenticatedPostBack()) {
         $Data = ArrayTranslate($Sender->Form->FormValues(), array('ClientID', 'AssociationSecret', 'HeadTemplate', 'BodyTemplate', 'IsDefault'));
         $Data['AuthenticationKey'] = self::PROVIDER_KEY;
         $Model = new Gdn_AuthenticationProviderModel();
         if ($Model->Save($Data)) {
            $Sender->InformMessage(T('Saved'));
         } else {
            $Sender->Form->SetValidationResults($Model->ValidationResults());
         }
      } else {
         $Sender->Form->SetData($Provider);
      }

      $Sender->AddSideMenu('social');
      $Sender->SetData('Title', sprintf(T('%s Settings'), 'Gigya'));
      $Sender->Render('Settings', '', 'plugins/gigya');
   }
}