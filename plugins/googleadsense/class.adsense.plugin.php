<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
*/

// Define the plugin:
//$PluginInfo['GoogleAdSense'] = array(
//   'Description' => 'Puts Google Adsense on a page.',
//   'Version' => '1.0',
//   'RequiredApplications' => FALSE,
//   'RequiredTheme' => FALSE, 
//   'RequiredPlugins' => FALSE,
//   'HasLocale' => TRUE,
//   'RegisterPermissions' => FALSE,
//   //'SettingsUrl' => '/garden/plugin/cssthemes', // Url of the plugin's settings page.
//   //'SettingsPermission' => 'Garden.Themes.Manage', // The permission required to view the SettingsUrl.
//   'Author' => "Todd Burry",
//   'AuthorEmail' => 'todd@vanillaforums.com',
//   'AuthorUrl' => 'http://toddburry.com'
//);

require_once(dirname(__FILE__).DS.'class.adsensemodule.php');

class Gdn_AdSensePlugin implements Gdn_IPlugin {	
	/**
	 * @param Gdn_Controller $Sender
	 */
   public function Base_Render_Before($Sender) {
		// Only serve the ads if we are delvering the entire page.
		if($Sender->DeliveryType() != DELIVERY_TYPE_ALL)
			return;
		
		// Get the add content.
		$Config = Gdn::Config('Plugins.GoogleAdSense', FALSE);
		if($Config === FALSE)
			return;
		
		foreach($Config as $AssetName => $AdConfig) {
			// Only serve the ads for certain controllers.
			if(in_array(strtolower($Sender->ControllerName), ArrayValue('Controllers', $AdConfig, array()))) {
				$Content = ArrayValue('Html', $AdConfig, '');
				if(!empty($Content)) {
					$Panel = new Gdn_AdSenseModule();
					$Panel->Content = $Content;
					$Sender->AddAsset($AssetName, $Panel, 'Ads');
				}
			}
		}
		
		//// Only serve the ads for certain controllers.
		//if(in_array(strtolower($Sender->ControllerName), $this->PanelAds)) {
		//	$Content = Gdn::Config('Plugins.GoogleAdSense.PanelContent', '');
		//	if(!empty($Content)) {
		//		$Panel = new Gdn_AdSenseModule();
		//		$Panel->Content = $Content;
		//		$Sender->AddAsset('Panel', $Panel, 'Ads');
		//	}
		//}
		//if(in_array(strtolower($Sender->ControllerName), $this->BannerAds)) {
		//	$Content = Gdn::Config('Plugins.GoogleAdSense.BannerContent', '');
		//	if(!empty($Content)) {
		//		$Panel = new Gdn_AdSenseModule();
		//		$Panel->Content = $Content;
		//		$Sender->AddAsset('Foot', $Panel, 'Ads');
		//	}
		//}
	}
	
	public function Add($Array, $Content, $Target) {
	}
	
	public function Setup() {
	}
}