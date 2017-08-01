<?php if (!defined('APPLICATION')) exit();

class vfHelpPlanPlugin extends Gdn_Plugin {

   public function discussionController_afterDiscussionMeta_handler($sender, $args) {
      $userID = getValue('UserID', getValue('Author', $args));
      $this->attachPlan($sender, $userID);
   }
   
   public function discussionController_commentInfo_handler($sender, $args) {
      $userID = getValue('UserID', getValue('Author', $args));
      $this->attachPlan($sender, $userID);
   }
   
   public function base_beforeDiscussionMeta_handler($sender, $args) {
      $userID = getValue('UserID', getValue('FirstUser', $args));
      $this->attachPlan($sender, $userID);
   }
   
   protected function attachPlan($sender, $userID) {
      if (!checkPermission('Garden.Moderation.Manage'))
         return;
      
      $user = Gdn::userModel()->getID($userID);
      if (getValue('Admin', $user))
         return;
      $plan = 'Free';
      
      if ($user && getValue('AccountID', $user)) {
         // Get highest plan level they have a site using
         $planData = Gdn::sql()->select('f.Name')
   			->from('Site s')
   			->join('SiteFeature sf', 'sf.SiteID = s.SiteID', 'left')
   			->join('Feature f', 'f.FeatureID = sf.FeatureID', 'left')
   			->where('s.AccountID', getValue('AccountID', $user))
   			->where('s.Deleted', 0)
   			->orderBy('f.Price', 'desc')
   			->get()
   			->firstRow();
         
         $plan = getValue('Name', $planData);
      }
      echo '<span class="MItem PlanLevel '.$plan.'">'.$plan.'</span>';
   }

   public function setup() { }
}
