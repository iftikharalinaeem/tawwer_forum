<?php

class GroupsHooks extends Gdn_Plugin {
   /**
    * Run structure & default badges.
    */
   public function Setup() {
      include(dirname(__FILE__).'/structure.php');
   }
   
   protected function SetBreadcrumbs($Group = NULL) {
      if (!$Group)
         $Group = Gdn::Controller()->Data('Group', NULL);
      
      if ($Group) {
         $Sender = Gdn::Controller();
         $Sender->SetData('Breadcrumbs', array());
         $Sender->AddBreadcrumb(T('Groups'), '/groups');
         $Sender->AddBreadcrumb($Group['Name'], GroupUrl($Group));
         
         $Sender->SetData('_CancelUrl', GroupUrl($Group));
      }
   }
   
   public function AssetModel_StyleCss_Handler($Sender, $Args) {
      $Sender->AddCssFile('groups.css', 'groups');
   }
   
   /** 
    * Add the "Groups" link to the main menu.
    */
   public function Base_Render_Before($Sender) {
      if (is_object($Menu = GetValue('Menu', $Sender))) {
         $Menu->AddLink('Groups', T('Groups'), '/groups/', FALSE, array('class' => 'Groups'));
      }
   }
   
   /**
    *
    * @param DbaController $Sender 
    */
   public function DbaController_CountJobs_Handler($Sender) {
      $Counts = array(
          'Group' => array('CountMembers', 'DateLastComment')
      );
      
      foreach ($Counts as $Table => $Columns) {
         foreach ($Columns as $Column) {
            $Name = "Recalculate $Table.$Column";
            $Url = "/dba/counts.json?".http_build_query(array('table' => $Table, 'column' => $Column));
            
            $Sender->Data['Jobs'][$Name] = $Url;
         }
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
            
            Trace($Args, 'Group set');
         }
      }
   }
   
   protected function OverridePermissions($Sender) {
      $Dicussion = $Sender->DiscussionModel->GetID($Sender->ReflectArgs['DiscussionID']);
      $GroupID = GetValue('GroupID', $Dicussion);
      if (!$GroupID)
         return;
      
      $Model = new GroupModel();
      $Group = $Model->GetID($GroupID);
      if (!$Group)
         return;
      
      $Model->OverridePermissions($Group);
   }
   
   /**
    * 
    * @param DiscussionController $Sender
    * @return type
    */
   public function DiscussionController_Announce_Before($Sender) {
      $this->OverridePermissions($Sender);
   }
   
   public function DiscussionController_Close_Before($Sender) {
      $this->OverridePermissions($Sender);
   }
   
   public function DiscussionController_Delete_Before($Sender) {
      $this->OverridePermissions($Sender);
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
   
   public function Base_AfterDiscussionFilters_Handler($Sender) {
      echo '<li class="Groups">'.Anchor(Sprite('SpGroups').' '.T('Groups'), '/groups').'</li> ';
   }
   
   /**
    * @param PostController $Sender
    */
   public function PostController_Discussion_Before($Sender) {
      $GroupID = $Sender->Request->Get('groupid');
      
      if (!$GroupID)
         return;
      
      $Model = new GroupModel();
      $Group = $Model->GetID($GroupID);
      if (!$Group)
         return;
      
      $Sender->SetData('Group', $Group);
      
      $Model->OverridePermissions($Group);
   }
   
   public function PostController_EditDiscussion_Before($Sender) {
      $DiscussionID = GetValue('DiscussionID', $Sender->ReflectArgs);
      if ($DiscussionID) {
         $Discussion = $Sender->DiscussionModel->GetID($DiscussionID);
         $GroupID = GetValue('GroupID', $Discussion);
      }
      
      if (!$GroupID)
         return;
      
      $Model = new GroupModel();
      $Group = $Model->GetID($GroupID);
      if (!$Group)
         return;
      
      $Sender->SetData('Group', $Group);
      $Model->OverridePermissions($Group);
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
            $Sender->SetData('Group', $Group);
         }
      }
      
      $this->SetBreadcrumbs();
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