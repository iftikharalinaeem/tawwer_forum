<?php if (!defined('APPLICATION')) exit();

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
      $provider = Gdn_AuthenticationProviderModel::GetProviderByKey(self::PROVIDER_KEY);

      if (!$provider) {
         $model = new Gdn_AuthenticationProviderModel();
         $provider = [
            'AuthenticationKey' => self::PROVIDER_KEY,
            'AuthenticationSchemeAlias' => self::PROVIDER_KEY,
            'Name' => C('Garden.Title'),
            'AuthenticateUrl' => 'https://socialize.gigya.com/socialize.login',

         ];

         $model->Save($provider);
      }
   }

   public static function validateUserSignature($uID, $timestamp, $secret, $signature, $throw = false) {
      // Validate the timestamp.
      if (abs(time() - $timestamp) > 180) {
         if ($throw) {
            throw new Gdn_UserException("The sso request does not have a valid timestamp.", 400);
         }
         return false;
      }

      // Validate the signature.
      $baseString = $timestamp."_".$uID;
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
    * @param EntryController $sender
    * @param array $args
    */
   public function Base_ConnectData_Handler($sender, $args) {
      if (GetValue(0, $args) != 'gigya')
         return;

      $form = $sender->Form;

      $user = json_decode($form->GetFormValue('User', $form->GetFormValue('User')), true);
      if (!$user) {
         throw new Gdn_UserException('Could not parse the user.');
      }

      // Validate the signature.
      $secret = val('AssociationSecret', $this->Provider());
      self::validateUserSignature($user['UID'], $user['signatureTimestamp'], $secret, $user['UIDSignature'], true);

      // Map all of the standard jsConnect data.
      $map = ['UID' => 'UniqueID', 'email' => 'Email', 'photoURL' => 'Photo'];
      foreach ($map as $key => $value) {
         $form->SetFormValue($value, GetValue($key, $user, ''));
      }
      if (isset($user['firstName']) && isset($user['lastName'])) {
         $form->SetFormValue('FullName', $user['firstName'].' '.$user['lastName']);
      }

      $form->SetFormValue('Provider', self::PROVIDER_KEY);
      $form->SetFormValue('ProviderName', 'Gigya');
      $form->AddHidden('User', $form->GetFormValue('User'));

      $sender->SetData('Verified', TRUE);
      $sender->SetData('SSOUser', $user);
   }

   /**
    * @param Gdn_Controller $sender
    */
   public function Base_Render_Before($sender) {
      if (!Gdn::Session()->IsValid() && $sender->Head) {
         $js = val('HeadTemplate', $this->Provider());
         $sender->Head->AddString($js);

         // We need to add the gigya js file as a string so it goes after the header template.
         $url = Asset('/plugins/gigya/js/gigya.js');
         $v = val('Version', Gdn::PluginManager()->GetPluginInfo('gigya'));
         $url .= '?v='.urlencode($v);

         $sender->Head->AddString("<script src='$url' type='text/javascript'></script>");
      }
   }

   /**
    * @param EntryController $sender
    * @param array $Args
    * @return mixed
    */
   public function EntryController_SignIn_Create($sender, $method = FALSE, $arg1 = FALSE) {
      if (!val('IsDefault', $this->Provider())) {
         return $sender->SignIn($method, $arg1);
      } else {
         $template = val('BodyTemplate', $this->Provider());
         $sender->SetData('BodyTemplate', $template);

         $sender->Title(T('Sign In'));
         $sender->Render('SignIn', '', 'plugins/gigya');
      }
   }

   /**
    *
    * @param Gdn_Controller $sender
    */
   public function EntryController_SignIn_Handler($sender, $args) {
      if (isset($sender->Data['Methods'])) {
         $template = val('BodyTemplate', $this->Provider());

         // Add the gigya signin code.
         if ($template) {
            $method = [
               'Name' => self::PROVIDER_KEY,
               'SignInHtml' => $template];

            $sender->Data['Methods'][] = $method;
         }
      }
   }

   /**
    * Manage the Gigya permissions.
    * @param SettingsController $sender
    */
   public function SettingsController_Gigya_Create($sender) {
      $sender->Permission('Garden.Settings.Manage');

      // Grab the provider.
      $provider = $this->Provider();
      if (!$provider) {
         throw NotFoundException('Provider');
      }

      if ($sender->Form->AuthenticatedPostBack()) {
         $data = ArrayTranslate($sender->Form->FormValues(), ['ClientID', 'AssociationSecret', 'HeadTemplate', 'BodyTemplate', 'IsDefault']);
         $data['AuthenticationKey'] = self::PROVIDER_KEY;
         $model = new Gdn_AuthenticationProviderModel();
         if ($model->Save($data)) {
            $sender->InformMessage(T('Saved'));
         } else {
            $sender->Form->SetValidationResults($model->ValidationResults());
         }
      } else {
         $sender->Form->SetData($provider);
      }

      $sender->AddSideMenu('social');
      $sender->SetData('Title', sprintf(T('%s Settings'), 'Gigya'));
      $sender->Render('Settings', '', 'plugins/gigya');
   }
}
