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

   public $showGuests = TRUE;

   public $selector = NULL;
   public $selectorID = NULL;
   public $selectorField = NULL;

   public function __construct(&$sender = '') {
      parent::__construct($sender);

      $this->selector = 'auto';
   }

   public function __set($name, $value) {
      switch ($name) {
         case 'CategoryID':
            $this->selector = 'category';
            $this->selectorID = $value;
            $this->selectorField = 'CategoryID';
            break;

         case 'DiscussionID':
            $this->selector = 'discussion';
            $this->selectorID = $value;
            $this->selectorField = 'DiscussionID';
            break;
      }
   }

   public function GetData() {

      if ($this->selector == 'auto') {

         $location = OnlinePlugin::WhereAmI(
            Gdn::Controller()->ResolvedPath,
            Gdn::Controller()->ReflectArgs
         );

         switch ($location) {
            case 'category':
            case 'discussion':
               $this->showGuests = FALSE;
               $this->selector = 'category';
               $this->selectorField = 'CategoryID';

               if ($location == 'discussion')
                  $this->selectorID = Gdn::Controller()->Data('Discussion.CategoryID');
               else
                  $this->selectorID = Gdn::Controller()->Data('Category.CategoryID');

               break;

            case 'limbo':
            case 'all':
               $this->showGuests = TRUE;
               $this->selector = 'all';
               $this->selectorID = NULL;
               $this->selectorField = NULL;
               break;
         }
      }

      $count = OnlinePlugin::Instance()->onlineCount($this->selector, $this->selectorID, $this->selectorField);
      $guestCount = OnlinePlugin::guests();
      if (!$guestCount) $guestCount = 0;

      return array($count, $guestCount);
   }

   public function ToString() {
      list($count, $guestCount) = $this->GetData();
      $combinedCount = $count + $guestCount;

      $trackedCount = $this->showGuests ? $combinedCount : $count;
      $formattedCount = Gdn_Format::BigNumber($trackedCount, 'html');

      $outputString = '';
      ob_start();
      ?>
      <div class="OnlineCount"><?php echo sprintf(T("%s viewing"),$formattedCount); ?></div>
      <?php

      $outputString = ob_get_contents();
      @ob_end_clean();

      return $outputString;
   }
}