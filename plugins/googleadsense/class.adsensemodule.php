<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
*/

/**
 * Renders a list of users who are taking part in a particular discussion.
 */
class AdSenseModule extends Gdn_Module {
	public $Content = '';
   
   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
		return '<div class="Center">'.$this->Content.'</div>';
   }
}