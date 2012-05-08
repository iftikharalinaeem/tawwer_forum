<?php if (!defined('APPLICATION')) exit();

$PluginInfo['vfHelpPlan'] = array(
   'Name' => 'Help Plan',
   'Description' => "Shows each user's VanillaForums.com plan level.",
   'Version' => '1.0',
   'MobileFriendly' => TRUE,
   'RequiredApplications' => array('VFCom' => '3.0.0'),
   'Author' => "Lincoln Russell",
   'AuthorEmail' => 'lincoln@vanillaforums.com',
   'AuthorUrl' => 'http://lincolnwebs.com'
);

class vfHelpPlanPlugin extends Gdn_Plugin {

   public function DiscussionController_AfterDiscussionMeta_Handler($Sender, $Args) {
      $UserID = GetValue('UserID', GetValue('Author', $Args));
      $this->AttachPlan($Sender, $UserID);
   }
   
   public function DiscussionController_CommentInfo_Handler($Sender, $Args) {
      $UserID = GetValue('UserID', GetValue('Author', $Args));
      $this->AttachPlan($Sender, $UserID);
   }
   
   public function Base_BeforeDiscussionMeta_Handler($Sender, $Args) {
      $UserID = GetValue('UserID', GetValue('FirstUser', $Args));
      $this->AttachPlan($Sender, $UserID);
   }
   
   protected function AttachPlan($Sender, $UserID) {
      $User = Gdn::UserModel()->GetID($UserID);
      if (GetValue('Admin', $User))
         return;
      $Plan = 'Free';
      
      if ($User && GetValue('AccountID', $User)) {
         // Get highest plan level they have a site using
         $PlanData = Gdn::SQL()->Select('f.Name')
   			->From('Site s')
   			->Join('SiteFeature sf', 'sf.SiteID = s.SiteID', 'left')
   			->Join('Feature f', 'f.FeatureID = sf.FeatureID', 'left')
   			->Where('s.AccountID', GetValue('AccountID', $User))
   			->Where('s.Deleted', 0)
   			->OrderBy('f.Price', 'desc')
   			->Get()
   			->FirstRow();
         
         $Plan = GetValue('Name', $PlanData);
      }
      echo '<span class="MItem PlanLevel '.$Plan.'">'.$Plan.'</span>';
   }

   public function Setup() { }
}