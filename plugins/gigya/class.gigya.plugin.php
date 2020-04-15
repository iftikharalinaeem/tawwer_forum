<?php if (!defined('APPLICATION')) exit();

class GigyaPlugin extends SSOAddon {
   /// Constants ///
    const PROVIDER_KEY = 'gigya';
    private const AUTHENTICATION_SCHEME = 'gigya';

    /// Properties ///
    protected $Provider = null;

    protected $RedirectUrl = '/entry/connect/gigya';

    /// Methods ///
    /**
     * Get the AuthenticationSchemeAlias value.
     *
     * @return string The AuthenticationSchemeAlias.
     */
    protected function getAuthenticationScheme(): string {
        return self::AUTHENTICATION_SCHEME;
    }

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
   public function provider() {
      if ($this->Provider === null) {
         $this->Provider = Gdn_AuthenticationProviderModel::getProviderByKey(self::PROVIDER_KEY);
      }
      return $this->Provider;
   }

   public function setup() {
      $this->structure();
   }

   public function structure() {
      // Make sure we have the saml provider.
      $provider = Gdn_AuthenticationProviderModel::getProviderByKey(self::PROVIDER_KEY);

      if (!$provider) {
         $model = new Gdn_AuthenticationProviderModel();
         $provider = [
            'AuthenticationKey' => self::PROVIDER_KEY,
            'AuthenticationSchemeAlias' => self::AUTHENTICATION_SCHEME,
            'Name' => c('Garden.Title'),
            'AuthenticateUrl' => 'https://socialize.gigya.com/socialize.login',

         ];

         $model->save($provider);
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
   public function base_connectData_handler($sender, $args) {
      if (getValue(0, $args) != 'gigya')
         return;

      $form = $sender->Form;

      $user = json_decode($form->getFormValue('User', $form->getFormValue('User')), true);
      if (!$user) {
         throw new Gdn_UserException('Could not parse the user.');
      }

      // Validate the signature.
      $secret = val('AssociationSecret', $this->provider());
      self::validateUserSignature($user['UID'], $user['signatureTimestamp'], $secret, $user['UIDSignature'], true);

      // Map all of the standard jsConnect data.
      $map = ['UID' => 'UniqueID', 'email' => 'Email', 'photoURL' => 'Photo'];
      foreach ($map as $key => $value) {
         $form->setFormValue($value, getValue($key, $user, ''));
      }
      if (isset($user['firstName']) && isset($user['lastName'])) {
         $form->setFormValue('FullName', $user['firstName'].' '.$user['lastName']);
      }

      $form->setFormValue('Provider', self::PROVIDER_KEY);
      $form->setFormValue('ProviderName', 'Gigya');
      $form->addHidden('User', $form->getFormValue('User'));

      $sender->setData('Verified', TRUE);
      $sender->setData('SSOUser', $user);
   }

   /**
    * @param Gdn_Controller $sender
    */
   public function base_render_before($sender) {
      if (!Gdn::session()->isValid() && $sender->Head) {
         $js = val('HeadTemplate', $this->provider());
         $sender->Head->addString($js);

         // We need to add the gigya js file as a string so it goes after the header template.
         $url = asset('/plugins/gigya/js/gigya.js');
         $v = val('Version', Gdn::pluginManager()->getPluginInfo('gigya'));
         $url .= '?v='.urlencode($v);

         $sender->Head->addString("<script src='$url' type='text/javascript'></script>");
      }
   }

   /**
    * @param EntryController $sender
    * @param array $Args
    * @return mixed
    */
   public function entryController_signIn_create($sender, $method = FALSE, $arg1 = FALSE) {
      if (!val('IsDefault', $this->provider())) {
         return $sender->signIn($method, $arg1);
      } else {
         $template = val('BodyTemplate', $this->provider());
         $sender->setData('BodyTemplate', $template);

         $sender->title(t('Sign In'));
         $sender->render('SignIn', '', 'plugins/gigya');
      }
   }

   /**
    *
    * @param Gdn_Controller $sender
    */
   public function entryController_signIn_handler($sender, $args) {
      if (isset($sender->Data['Methods'])) {
         $template = val('BodyTemplate', $this->provider());

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
   public function settingsController_gigya_create($sender) {
      $sender->permission('Garden.Settings.Manage');

      // Grab the provider.
      $provider = $this->provider();
      if (!$provider) {
         throw notFoundException('Provider');
      }

      if ($sender->Form->authenticatedPostBack()) {
         $data = arrayTranslate($sender->Form->formValues(), ['ClientID', 'AssociationSecret', 'HeadTemplate', 'BodyTemplate', 'IsDefault']);
         $data['AuthenticationKey'] = self::PROVIDER_KEY;
         $model = new Gdn_AuthenticationProviderModel();
         if ($model->save($data)) {
            $sender->informMessage(t('Saved'));
         } else {
            $sender->Form->setValidationResults($model->validationResults());
         }
      } else {
         $sender->Form->setData($provider);
      }

      $sender->addSideMenu('social');
      $sender->setData('Title', sprintf(t('%s Settings'), 'Gigya'));
      $sender->render('Settings', '', 'plugins/gigya');
   }
}
