<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008-2015 Vanilla Forums Inc.
 * @license Proprietary
 */

$PluginInfo['AutoSignout'] = array(
	'Name' => 'Auto Signout Timer',
   'Description' => 'Automatically signs people out if they have not been active for a period of time',
   'Version' => '1.1',
   'RequiredApplications' => array('Vanilla' => '2.1'),
	'MobileFriendly' => TRUE,
   'SettingsUrl' => '/settings/autosignout',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com',
   'MobileFriendly' => TRUE,
   'Icon' => 'auto-signout-timer.png'
);

// v1.1 2015-05-04 Lincoln
//   * Block RememberMe function to prevent session renewal.

class AutoSignoutPlugin extends Gdn_Plugin {
   /**
    * Add styles.
    *
    * @param AssetModel $Sender
    */
   public function AssetModel_StyleCss_Handler($Sender) {
      $Sender->AddCssFile('autosignout.css', 'plugins/AutoSignout');
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
    * @param $Sender
    */
   public function EntryController_SignIn_Handler($Sender) {
      $Sender->Form->SetFormValue('RememberMe', FALSE);
   }

   /**
    * Block the "Remember Me" function on Register.
    *
    * @param $Sender
    */
   public function EntryController_RegisterValidation_Handler($Sender) {
      $Sender->Form->SetFormValue('RememberMe', FALSE);
   }

   /**
    * Signout notification page.
    *
    * @param $Sender
    */
   public function EntryController_AutoSignedOut_Create($Sender) {
      $Sender->SetData('Title', T("You've Been Signed Out"));
      $Sender->CssClass = 'SplashMessage NoPanel';
      $Sender->SetData('_NoMessages', TRUE);
      $Sender->Render('autosignedout', '', 'plugins/AutoSignout');
   }

   /**
    * Settings page.
    *
    * @param $Sender
    */
   public function SettingsController_AutoSignout_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');

      $Conf = new ConfigurationModule($Sender);
      $Conf->Initialize(array(
          'Plugins.AutoSignout.Minutes' => array('Description' => "Enter the number of minutes to wait before signing users out.", 'Default' => 30)
      ));

      $Sender->AddSideMenu();
      $Sender->SetData('Title', 'Auto Signout Settings');
      $Sender->ConfigurationModule = $Conf;
      $Conf->RenderAll();
   }
}
