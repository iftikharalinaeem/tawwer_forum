<?php if (!defined('APPLICATION')) exit();

/**
 * Online Plugin - OnlineCountModule
 * 
 * This module displays a count of users who are currently online.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Misc
 */

class OnlineCountModule extends Gdn_Module {
   
   public $Selector = NULL;
   public $SelectorID = NULL;
   public $SelectorField = NULL;

	public function __construct(&$Sender = '') {
		parent::__construct($Sender);
      
      $this->Selector = 'all';
	}
   
   public function __set($Name, $Value) {
      switch ($Name) {
         case 'CategoryID':
            $this->Selector = 'category';
            $this->SelectorID = $Value;
            $this->SelectorField = 'CategoryID';
            break;
         
         case 'DiscussionID':
            $this->Selector = 'discussion';
            $this->SelectorID = $Value;
            $this->SelectorField = 'DiscussionID';
            break;
      }
   }

	public function GetData() {
      $Count = OnlinePlugin::Instance()->OnlineCount($this->Selector, $this->SelectorID, $this->SelectorField);
      $GuestCount = OnlinePlugin::Guests();
      
      return array($Count, $GuestCount);
   }
   
   public function ToString() {
      list($Count, $GuestCount) = $this->GetData();
      $CombinedCount = $Count + $GuestCount;
      $FormattedCount = Gdn_Format::BigNumber($CombinedCount, 'html');
      
      $OutputString = '';
		ob_start();
		?>
      <div class="OnlineCount"><?php echo sprintf(T("%s viewing"),$FormattedCount); ?></div>
      <?php
      
		$OutputString = ob_get_contents();
		@ob_end_clean();
      
		return $OutputString;
   }
}