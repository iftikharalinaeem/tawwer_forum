<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
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
   public function assetModel_styleCss_handler($sender) {
      $sender->addCssFile('autosignout.css', 'plugins/AutoSignout');
   }

   /**
    * Add page assets & configuration.
    *
    * @param Gdn_Controller $Sender
    */
   public function base_render_before($Sender) {
      // Add the javascript assets.
      $Sender->addJsFile('jquery.idle-timer.js', 'plugins/AutoSignout');
      $Sender->addJsFile('autosignout.js', 'plugins/AutoSignout');
      $Sender->addDefinition('AutoSignoutTime', c('Plugins.AutoSignout.Minutes', 30) * 60000);

      $Path = dirname(__FILE__).'/views/signoutwarning.php';
      ob_start();
      include $Path;
      $WarningAsset = ob_get_clean();

      $Sender->addAsset('Content', $WarningAsset, 'SignoutWarning');
   }

   /**
    * Block the "Remember Me" function on Sign In.
    *
    * @param $sender
    */
   public function entryController_signIn_handler($sender) {
      $sender->Form->setFormValue('RememberMe', FALSE);
   }

   /**
    * Block the "Remember Me" function on Register.
    *
    * @param $sender
    */
   public function entryController_registerValidation_handler($sender) {
      $sender->Form->setFormValue('RememberMe', FALSE);
   }

   /**
    * Signout notification page.
    *
    * @param $sender
    */
   public function entryController_autoSignedOut_create($sender) {
      $sender->setData('Title', t("You've Been Signed Out"));
      $sender->CssClass = 'SplashMessage NoPanel';
      $sender->setData('_NoMessages', TRUE);
      $sender->render('autosignedout', '', 'plugins/AutoSignout');
   }

   /**
    * Settings page.
    *
    * @param $sender
    */
   public function settingsController_autoSignout_create($sender) {
      $sender->permission('Garden.Settings.Manage');

      $conf = new ConfigurationModule($sender);
      $conf->initialize([
          'Plugins.AutoSignout.Minutes' => ['Description' => "Enter the number of minutes to wait before signing users out.", 'Default' => 30]
      ]);

      $sender->addSideMenu();
      $sender->setData('Title', 'Auto Signout Settings');
      $sender->ConfigurationModule = $conf;
      $conf->renderAll();
   }
}
