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
   
   public $ShowGuests = TRUE;
   
   public $Selector = NULL;
   public $SelectorID = NULL;
   public $SelectorField = NULL;

	public function __construct(&$Sender = '') {
		parent::__construct($Sender);
      
      $this->Selector = 'auto';
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
      
      if ($this->Selector == 'auto') {
            
         $Location = OnlinePlugin::WhereAmI(
            Gdn::Controller()->ResolvedPath, 
            Gdn::Controller()->ReflectArgs
         );

         switch ($Location) {
            case 'category':
            case 'discussion':
               $this->ShowGuests = FALSE;
               $this->Selector = 'category';
               $this->SelectorField = 'CategoryID';

               if ($Location == 'discussion')
                  $this->SelectorID = Gdn::Controller()->Data('Discussion.CategoryID');
               else
                  $this->SelectorID = Gdn::Controller()->Data('Category.CategoryID');

               break;

            case 'limbo':
            case 'all':
               $this->ShowGuests = TRUE;
               $this->Selector = 'all';
               $this->SelectorID = NULL;
               $this->SelectorField = NULL;
               break;
         }
      }
      
      $Count = OnlinePlugin::Instance()->OnlineCount($this->Selector, $this->SelectorID, $this->SelectorField);
      $GuestCount = OnlinePlugin::Guests();
      
      return array($Count, $GuestCount);
   }
   
   public function ToString() {
      list($Count, $GuestCount) = $this->GetData();
      $CombinedCount = $Count + $GuestCount;
      
      $TrackedCount = $this->ShowGuests ? $CombinedCount : $Count;
      $FormattedCount = Gdn_Format::BigNumber($TrackedCount, 'html');
      
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