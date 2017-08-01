<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
*/

class Gdn_AdSensePlugin implements Gdn_IPlugin {
   public function discussionController_render_before($sender) {
		// Only serve the ads if we are delvering the entire page.
		if($sender->deliveryType() != DELIVERY_TYPE_ALL)
			return;

      // Add skimlinks script at bottom of page.
		$sender->addAsset('Foot', '<script type="text/javascript" src="http://s.skimresources.com/js/7631X665150.skimlinks.js"></script>');

		// Get the add content.
		// $Config = Gdn::config('Plugins.GoogleAdSense', FALSE);
		// if($Config === FALSE)
		// 	return;

      /*
		foreach($Config as $AssetName => $AdConfig) {
			// Only serve the ads for certain controllers.
			if(in_array(strtolower($Sender->ControllerName), arrayValue('Controllers', $AdConfig, array()))) {
				$Content = arrayValue('Html', $AdConfig, '');
				if(!empty($Content)) {
					$Panel = new adSenseModule();
					$Panel->Content = $Content;
					$Sender->addAsset($AssetName, $Panel, 'Ads');
				}
			}
		}
      */
		//// Only serve the ads for certain controllers.
		//if(in_array(strtolower($Sender->ControllerName), $this->PanelAds)) {
		//	$Content = Gdn::config('Plugins.GoogleAdSense.PanelContent', '');
		//	if(!empty($Content)) {
		//		$Panel = new adSenseModule();
		//		$Panel->Content = $Content;
		//		$Sender->addAsset('Panel', $Panel, 'Ads');
		//	}
		//}
		//if(in_array(strtolower($Sender->ControllerName), $this->BannerAds)) {
		//	$Content = Gdn::config('Plugins.GoogleAdSense.BannerContent', '');
		//	if(!empty($Content)) {
		//		$Panel = new adSenseModule();
		//		$Panel->Content = $Content;
		//		$Sender->addAsset('Foot', $Panel, 'Ads');
		//	}
		//}
	}
	/*
	public function add($Array, $Content, $Target) {
	}
   */
	public function setup() {
	}
}
