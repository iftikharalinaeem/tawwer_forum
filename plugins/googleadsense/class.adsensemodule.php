<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
*/

/**
 * Renders a list of users who are taking part in a particular discussion.
 */
class AdSenseModule extends Module {
	public $Content = '';
	
   public function __construct(&$Sender = NULL) {
      parent::__construct($Sender);
   }
   
   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
		return '<div class="Center">'.$this->Content.'</div>';
   }
}