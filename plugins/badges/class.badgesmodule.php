<?php
/**
 * @copyright 2011 Vanilla Forums Inc
 * @package Reputation
 */

/**
 * Renders a list of badges given to a particular user.
 */
class BadgesModule extends Gdn_Module {
   
   public function __construct($Sender = '') {
      // Default to current user if none is set
      $this->User = Gdn::Controller()->Data('Profile', Gdn::Session()->User);
      
      if (!$this->User)
         return;
      
      // Get badge list
      $UserBadgeModel = new UserBadgeModel();
      $this->Badges = $UserBadgeModel->GetBadges(GetValue('UserID', $this->User))->ResultArray();
      
      // Optionally only show highest badge in each class
      if (C('Reputation.Badges.FilterModuleByClass'))
         $this->Badges = BadgeModel::FilterByClass($this->Badges);


      parent::__construct($Sender, 'plugin/badges');
   }
   
   public function AssetTarget() {
      return C('Badges.BadgesModule.Target', 'Panel');
   }
   
   public function ToString() {
      if (!$this->User)
         return;
      
      return parent::ToString();
   }
}