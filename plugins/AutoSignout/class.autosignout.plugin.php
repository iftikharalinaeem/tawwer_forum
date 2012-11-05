<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['AutoSignout'] = array(
	'Name' => 'Auto Signout Timer',
   'Description' => 'Automatically signs people out if they have not been active for a period of time',
   'Version' => '1.0b',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
	'MobileFriendly' => TRUE,
   'SettingsUrl' => '/dashboard/settings/autosignout',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class AutoSignoutPlugin extends Gdn_Plugin {
   /// Methods ///
   
   /// Event Handlers ///
   
   /**
    * 
    * @param AssetModel $Sender
    */
   public function AssetModel_StyleCss_Handler($Sender) {
      $Sender->AddCssFile('autosignout.css', 'plugins/AutoSignout');
   }
   
   /**
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
   
   public function EntryController_AutoSignedOut_Create($Sender) {
      $Sender->SetData('Title', T("You've Been Signed Out"));
      
      $Sender->CssClass = 'SplashMessage NoPanel';
      $Sender->SetData('_NoMessages', TRUE);
      
      $Sender->Render('autosignedout', '', 'plugins/AutoSignout');
   }
   
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