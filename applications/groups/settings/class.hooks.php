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

   public function Base_ConversationGInvite_Handler($Sender, $Args) {
      $GroupID = $Sender->Data('Conversation.RegardingID');
      if ($GroupID) {
         echo Gdn_Theme::Module('GroupUserHeaderModule', array('GroupID' => $GroupID));
      }
   }

   /**
    *
    * @param DbaController $Sender
    */
   public function DbaController_CountJobs_Handler($Sender) {
      $Counts = array(
          'Group' => array('CountDiscussions', 'CountMembers', 'DateLastComment')
      );

      foreach ($Counts as $Table => $Columns) {
         foreach ($Columns as $Column) {
            $Name = "Recalculate $Table.$Column";
            $Url = "/dba/counts.json?".http_build_query(array('table' => $Table, 'column' => $Column));

            $Sender->Data['Jobs'][$Name] = $Url;
         }
      }
   }

   /**
    * Make sure the user has permission to view the group
    * @param DiscussionController $Sender
    * @param array $Args
    * @throws Exception Throws an exception if the user doesn't have proper access to the group.
    */
   public function DiscussionController_Index_Render($Sender, $Args) {
      $GroupID = $Sender->Data('Discussion.GroupID');
      if (!$GroupID)
         return;

      $ViewPermission = GroupPermission('View', $GroupID);
      if (!$ViewPermission) {
         throw ForbiddenException('@'.GroupPermission('View.Reason', $GroupID));
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

            if (GetValue('Insert', $Args)) {
               $Model->IncrementDiscussionCount($GroupID, 1);
            }

            Trace($Args, 'Group set');
         }
      }
   }

   public function DiscussionModel_DeleteDiscussion_Handler($Sender, $Args) {
      $GroupID = GetValueR('Discussion.GroupID', $Args);
      if ($GroupID) {
         $Model = new GroupModel();
         $Model->IncrementDiscussionCount($GroupID, -1);
      }
   }

   /**
    * Delete discussion must redirect to Group instead of Category page.
    *
    * @param $Sender
    * @param $Args
    */
   public function DiscussionController_DiscussionOptions_Handler($Sender, $Args) {
      if ($GroupID = val('GroupID', $Args['Discussion'])) {
         if (GetValue('DeleteDiscussion', $Args['DiscussionOptions'])) {
            // Get the group
            $Model = new GroupModel();
            $Group = $Model->GetID($GroupID);
            if (!$Group)
               return;

            // Override redirect with GroupUrl instead of CategoryUrl.
            $Args['DiscussionOptions']['DeleteDiscussion'] = array(
               'Label' => T('Delete Discussion'),
               'Url' => '/discussion/delete?discussionid='.$Args['Discussion']->DiscussionID.'&target='.urlencode(GroupUrl($Group)),
               'Class' => 'Popup');
         }
      }
   }

   protected function OverridePermissions($Sender) {
      $Discussion = $Sender->DiscussionModel->GetID($Sender->ReflectArgs['DiscussionID']);
      $GroupID = GetValue('GroupID', $Discussion);
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

   public function DiscussionController_Index_Before($Sender) {
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

         Gdn_Theme::Section('Group');
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

   /**
    *
    * @param PostController $Sender
    * @return type
    */
   public function PostController_Comment_Before($Sender) {
      $DiscussionID = $Sender->Request->Get('discussionid');
      if (!$DiscussionID)
         return;
      $Discussion = $Sender->DiscussionModel->GetID($DiscussionID);
      $GroupID = GetValue('GroupID', $Discussion);
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
      $GroupID = FALSE;
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
      $Group = $Sender->Data('Group');

      if ($Group) {
         // Hide the category drop-down.
         $Sender->ShowCategorySelector = FALSE;

         // Reduce the announce options.
         $Options = array(
            2 => '@'.T('Announce'),
            0 => '@'.T("Don't announce."));
         $Sender->SetData('_AnnounceOptions', $Options);
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

    /**
     * Hide Private content.
     *
     * @param SearchController $Sender Sending controller.
     * @param array $Args Sending arguments.
     */
    public function SearchController_Render_Before($Sender, $Args) {

        Trace('SearchController_Render_Before');

        $GroupCategoryID = Gdn::Cache()->Get('GroupCategoryID');
        if ($GroupCategoryID === Gdn_Cache::CACHEOP_FAILURE) {
            $CategoryModel = new CategoryModel();
            $GroupCategory = $CategoryModel->GetWhere(array('AllowGroups' => 1))->FirstRow(DATASET_TYPE_ARRAY);
            $GroupCatID = val('CategoryID', $GroupCategory, false);

            Gdn::Cache()->Store('GroupCategoryID', $GroupCatID);
        }

        $SearchResults = $Sender->Data('SearchResults', array());
        foreach ($SearchResults as $ResultKey => &$Result) {
            $GroupID = val('GroupID', $Result, false);
            if ($GroupID || $Result['CategoryID'] == $GroupCategoryID) {

                if (!$GroupID && $Result['RecordType'] == 'Discussion') {

                    $DiscussionModel = new DiscussionModel();
                    $Discussion = $DiscussionModel->GetID($Result['PrimaryID']);
                    $GroupID = $Discussion->GroupID;

                } elseif (!$GroupID && $Result['RecordType'] == 'Comment') {

                    $CommentModel = new CommentModel();
                    $Comment = $CommentModel->GetID($Result['PrimaryID']);
                    $DiscussionModel = new DiscussionModel();
                    $Discussion = $DiscussionModel->GetID($Comment->DiscussionID);

                    $GroupID = $Discussion->GroupID;

                }

                $GroupModel = new GroupModel();
                $Group = Gdn::Cache()->Get(sprintf('Group.%s', $GroupID));
                if ($Group === Gdn_Cache::CACHEOP_FAILURE) {
                    $Group = $GroupModel->GetID($GroupID);
                    Gdn::Cache()->Store(sprintf('Group.%s', $GroupID), $Group, array(Gdn_Cache::FEATURE_EXPIRY => 15 * 60));
                }

                if ($Group['Privacy'] == 'Private' && !$GroupModel->CheckPermission('View', $Group['GroupID'])) {
                    unset($SearchResults[$ResultKey]);
                    $Result['Title'] = '** Private **';
                    $Result['Summary'] = '** Private **';
                }

            }
            $Sender->SetData('SearchResults', $SearchResults);
        }

    }
}
