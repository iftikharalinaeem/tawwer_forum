<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
*/

// Define the plugin:
$PluginInfo['googleadsense'] = array(
   'Name' => 'Skimlinks',
   'Description' => 'Puts Skimlinks into vanilla pages.',
   'Version' => '1.0',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   //'SettingsUrl' => '/dashboard/plugin/cssthemes', // Url of the plugin's settings page.
   //'SettingsPermission' => 'Garden.Themes.Manage', // The permission required to view the SettingsUrl.
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://toddburry.com'
);

// require_once(dirname(__FILE__).DS.'class.adsensemodule.php');

class Gdn_AdSensePlugin implements Gdn_IPlugin {	
   public function DiscussionController_Render_Before($Sender) {
		// Only serve the ads if we are delvering the entire page.
		if($Sender->DeliveryType() != DELIVERY_TYPE_ALL)
			return;
      
      // Add skimlinks script at bottom of page.
		$Sender->AddAsset('Foot', '<script type="text/javascript" src="http://s.skimresources.com/js/7631X665150.skimlinks.js"></script>');
		
		// Get the add content.
		// $Config = Gdn::Config('Plugins.GoogleAdSense', FALSE);
		// if($Config === FALSE)
		// 	return;
		
      /*
		foreach($Config as $AssetName => $AdConfig) {
			// Only serve the ads for certain controllers.
			if(in_array(strtolower($Sender->ControllerName), ArrayValue('Controllers', $AdConfig, array()))) {
				$Content = ArrayValue('Html', $AdConfig, '');
				if(!empty($Content)) {
					$Panel = new AdSenseModule();
					$Panel->Content = $Content;
					$Sender->AddAsset($AssetName, $Panel, 'Ads');
				}
			}
		}
      */
		//// Only serve the ads for certain controllers.
		//if(in_array(strtolower($Sender->ControllerName), $this->PanelAds)) {
		//	$Content = Gdn::Config('Plugins.GoogleAdSense.PanelContent', '');
		//	if(!empty($Content)) {
		//		$Panel = new AdSenseModule();
		//		$Panel->Content = $Content;
		//		$Sender->AddAsset('Panel', $Panel, 'Ads');
		//	}
		//}
		//if(in_array(strtolower($Sender->ControllerName), $this->BannerAds)) {
		//	$Content = Gdn::Config('Plugins.GoogleAdSense.BannerContent', '');
		//	if(!empty($Content)) {
		//		$Panel = new AdSenseModule();
		//		$Panel->Content = $Content;
		//		$Sender->AddAsset('Foot', $Panel, 'Ads');
		//	}
		//}
	}
	/*
	public function Add($Array, $Content, $Target) {
	}
   */
	public function Setup() {
	}
}