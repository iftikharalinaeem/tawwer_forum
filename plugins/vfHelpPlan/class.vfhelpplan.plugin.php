<?php if (!defined('APPLICATION')) exit();

class vfHelpPlanPlugin extends Gdn_Plugin {

   public function DiscussionController_AfterDiscussionMeta_Handler($sender, $args) {
      $userID = GetValue('UserID', GetValue('Author', $args));
      $this->AttachPlan($sender, $userID);
   }
   
   public function DiscussionController_CommentInfo_Handler($sender, $args) {
      $userID = GetValue('UserID', GetValue('Author', $args));
      $this->AttachPlan($sender, $userID);
   }
   
   public function Base_BeforeDiscussionMeta_Handler($sender, $args) {
      $userID = GetValue('UserID', GetValue('FirstUser', $args));
      $this->AttachPlan($sender, $userID);
   }
   
   protected function AttachPlan($sender, $userID) {
      if (!CheckPermission('Garden.Moderation.Manage'))
         return;
      
      $user = Gdn::UserModel()->GetID($userID);
      if (GetValue('Admin', $user))
         return;
      $plan = 'Free';
      
      if ($user && GetValue('AccountID', $user)) {
         // Get highest plan level they have a site using
         $planData = Gdn::SQL()->Select('f.Name')
   			->From('Site s')
   			->Join('SiteFeature sf', 'sf.SiteID = s.SiteID', 'left')
   			->Join('Feature f', 'f.FeatureID = sf.FeatureID', 'left')
   			->Where('s.AccountID', GetValue('AccountID', $user))
   			->Where('s.Deleted', 0)
   			->OrderBy('f.Price', 'desc')
   			->Get()
   			->FirstRow();
         
         $plan = GetValue('Name', $planData);
      }
      echo '<span class="MItem PlanLevel '.$plan.'">'.$plan.'</span>';
   }

   public function Setup() { }
}
