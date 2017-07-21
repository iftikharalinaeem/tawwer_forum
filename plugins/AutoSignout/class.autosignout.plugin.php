<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

// v1.1 2015-05-04 Lincoln
//   * Block RememberMe function to prevent session renewal.

class AutoSignoutPlugin extends Gdn_Plugin {
   /**
    * Add styles.
    *
    * @param AssetModel $sender
    */
   public function AssetModel_StyleCss_Handler($sender) {
      $sender->AddCssFile('autosignout.css', 'plugins/AutoSignout');
   }

   /**
    * Add page assets & configuration.
    *
    * @param Gdn_Controller $Sender
    */
   public function Base_Render_Before($Sender) {
      // Add the javascript assets.
      $Sender->AddJsFile('jquery.idle-timer.js', 'plugins/AutoSignout');
      $Sender->AddJsFile('autosignout.js', 'plugins/AutoSignout');
      $Sender->AddDefinition('AutoSignoutTime', C('Plugins.AutoSignout.Minutes', 30) * 60000);

      $Path = dirname(__FILE__).'/views/signoutwarning.php';
      ob_start();
      include $Path;
      $WarningAsset = ob_get_clean();

      $Sender->AddAsset('Content', $WarningAsset, 'SignoutWarning');
   }

   /**
    * Block the "Remember Me" function on Sign In.
    *
    * @param $sender
    */
   public function EntryController_SignIn_Handler($sender) {
      $sender->Form->SetFormValue('RememberMe', FALSE);
   }

   /**
    * Block the "Remember Me" function on Register.
    *
    * @param $sender
    */
   public function EntryController_RegisterValidation_Handler($sender) {
      $sender->Form->SetFormValue('RememberMe', FALSE);
   }

   /**
    * Signout notification page.
    *
    * @param $sender
    */
   public function EntryController_AutoSignedOut_Create($sender) {
      $sender->SetData('Title', T("You've Been Signed Out"));
      $sender->CssClass = 'SplashMessage NoPanel';
      $sender->SetData('_NoMessages', TRUE);
      $sender->Render('autosignedout', '', 'plugins/AutoSignout');
   }

   /**
    * Settings page.
    *
    * @param $sender
    */
   public function SettingsController_AutoSignout_Create($sender) {
      $sender->Permission('Garden.Settings.Manage');

      $conf = new ConfigurationModule($sender);
      $conf->Initialize([
          'Plugins.AutoSignout.Minutes' => ['Description' => "Enter the number of minutes to wait before signing users out.", 'Default' => 30]
      ]);

      $sender->AddSideMenu();
      $sender->SetData('Title', 'Auto Signout Settings');
      $sender->ConfigurationModule = $conf;
      $conf->RenderAll();
   }
}
