<?php

class GroupsHooks extends Gdn_Plugin {
      /**
    * Run structure & default badges.
    */
   public function Setup() {
      include(dirname(__FILE__).'/structure.php');
   }
   
   /** 
    * Add the "Groups" link to the main menu.
    */
   public function Base_Render_Before($Sender) {
      if (is_object($Menu = GetValue('Menu', $Sender))) {
         $Menu->AddLink('Groups', T('Groups'), '/groups/', FALSE, array('class' => 'Groups'));
      }
   }
   
   public function DiscussionModel_BeforeSaveDiscussion_Handler($Sender, $Args) {
      $GroupID = Gdn::Request()->Get('groupid');
      if ($GroupID) {
         $Model = new GroupModel();
         $Group = $Model->GetID($GroupID);
         
         if ($Group) {
            // TODO: Check permissions.
            $Args['FormPostValues']['CategoryID'] = $Group['CategoryID'];
            $Args['FormPostValues']['GroupID'] = $GroupID;
         }
      }
   }
   
   /**
    * 
    * @param DiscussionController $Sender
    */
   public function DiscussionController_Render_Before($Sender) {
      $GroupID = $Sender->Data('Discussion.GroupID');
      if ($GroupID) {
         // This is a group discussion. Modify the breadcrumbs.
         $Model = new GroupModel();
         $Group = $Model->GetID($GroupID);
         if ($Group) {
            $Sender->SetData('Breadcrumbs', array());
            $Sender->AddBreadcrumb(T('Groups'), '/groups');
            $Sender->AddBreadcrumb($Group['Name'], GroupUrl($Group));
         }
      }
   }
   
   /**
    * 
    * @param PostController $Sender
    */
   public function PostController_Render_Before($Sender) {
      $GroupID = Gdn::Request()->Get('groupid');
      if ($GroupID) {
         // TODO: Check permissions.
         $Model = new GroupModel();
         $Group = $Model->GetID($GroupID);
         if ($Group) {
            // Hide the category drop-down.
            $Sender->ShowCategorySelector = FALSE;
         }
         
      }
   }
   
   /**
    * Configure Groups/Events notification preferences
    * 
    * @param type $Sender
    */
   public function ProfileController_AfterPreferencesDefined_Handler($Sender) {
      $Sender->Preferences['Notifications']['Email.Groups'] = T('PreferenceGroupsEmail', 'Notify me when there is Group activity.');
      $Sender->Preferences['Notifications']['Popup.Groups'] = T('PreferenceGroupsPopup', 'Notify me when there is Group activity.');
      
      $Sender->Preferences['Notifications']['Email.Events'] = T('PreferenceEventsEmail', 'Notify me when there is Event activity.');
      $Sender->Preferences['Notifications']['Popup.Events'] = T('PreferenceEventsPopup', 'Notify me when there is Event activity.');
   }
}