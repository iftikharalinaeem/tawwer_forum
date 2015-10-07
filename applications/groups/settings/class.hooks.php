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

   /**
    * Add a discussion excerpt in each discussion list item.
    */
   public function groupController_afterDiscussionTitle_handler($sender) {
      $discussion = val('Discussion', $sender->EventArguments);
      if (is_object($discussion) && val('Announce', $discussion)) {
         echo '<div class="Excerpt">'.sliceString(Gdn_Format::plainText($discussion->Body, $discussion->Format), c('Vanilla.DiscussionExcerpt.Length', 100)).'</div>';
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
          'Group' => array('CountDiscussions', 'CountMembers', 'DateLastComment', 'LastDiscussionID')
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
    * Remove category permissions restrictions temporarily when getting bookmarks.
    *
    * @param DiscussionsController $sender
    * @param $args
    */
   public function discussionsController_bookmarked_before($sender, $args) {
      $categoryModel = new CategoryModel();
      $categories = $categoryModel->getWhereCache(['AllowGroups' => 1]);
      $this->categoryPermissions = [];
      foreach ($categories as $id => $cat) {
         $this->categoryPermissions[$id] = $cat['PermsDiscussionsView'];
         CategoryModel::setLocalField($id, 'PermsDiscussionsView', true);
      }
   }

   /**
    * Restore category permissions that have been changed.
    *
    * Permissions are changed in discussionsController_bookmarked_before in order to allow bookmarks
    * to appear that are in groups within categories, in this method they are restored.
    *
    * @param $sender
    * @param $args
    */
   public function discussionsController_bookmarked_render($sender, $args) {

      /* Ensure that there are discussions */

      if (!$sender->data('Discussions') && !($sender->data('Discussions') instanceof Gdn_DataSet)) {
         trigger_error("No discussions found in the data array.", E_USER_NOTICE);
         return;
      }

      $discussions = $sender->data('Discussions');
      $discussionResult = (array)$discussions->result();

      $groupModel = new GroupModel();

      // Create a discussions array by filtering out groups for which the $User does not have permissions to view.
      $discussionsDenied = array_filter(
          $discussionResult,
          function ($discussion) use ($groupModel) {
             $groupID = val('GroupID', $discussion);
             if (empty($groupID)) {
                return true;
             }
             $viewPermission = $groupModel->checkPermission('View', $groupID);
             return (bool)$viewPermission;
          }
      );

      // Re-import $disussions into dataset.
      $discussions->importDataset($discussionsDenied);

      // Re-instate category permissions temporarily modified in discussionsController_bookmarked_before.
      $categoryModel = new CategoryModel();
      $categories = $categoryModel->getWhereCache(['AllowGroups' => 1]);

      foreach ($categories as $id => $cat) {
         CategoryModel::setLocalField($id, 'PermsDiscussionsView', $this->categoryPermissions[$id]);
      }

   }



   /**
    * Remove category permissions restrictions temporarily when getting bookmarks.
    *
    * @param DiscussionsController $sender
    * @param $args
    */
   public function discussionsController_bookmarkedPopin_before($sender, $args) {
      $categoryModel = new CategoryModel();
      $categories = $categoryModel->getWhereCache(['AllowGroups' => 1]);
      $this->categoryPermissions = [];
      foreach ($categories as $id => $cat) {
         $this->categoryPermissions[$id] = $cat['PermsDiscussionsView'];
         CategoryModel::setLocalField($id, 'PermsDiscussionsView', true);
      }
   }

   /**
    * Restore category permissions that have been changed in discussionsController_bookmarked_before in order
    * to allow bookmarks to appear that are in groups within categories.
    *
    * @param $sender
    * @param $args
    */
   public function discussionsController_bookmarkedPopin_render($sender, $args, $discussions) {

      // Ensure that there are discussions.

      if (!$sender->data('Discussions') && !($sender->data('Discussions') instanceof Gdn_DataSet)) {
         trigger_error("No discussions found in the data array.", E_USER_NOTICE);
         return;
      }

      $discussionResult = $sender->data('Discussions');
      $groupModel = new GroupModel();

      // Create a discussions Array by filtering out groups forwhich the $User does not have permissions to view.
      $discussions = array_filter(
          $discussionResult,
          function ($discussion) use ($groupModel) {
             $groupID = val('GroupID', $discussion);
             if (empty($groupID)) {
                return true;
             }
             $viewPermission = $groupModel->checkPermission('View', $groupID);
             return (bool)$viewPermission;
          }
      );

      $sender->setData('Discussions', $discussions);

      // Re-instate category permissions temporarily modified in discussionsController_bookmarked_before.
      $categoryModel = new CategoryModel();
      $categories = $categoryModel->getWhereCache(['AllowGroups' => 1]);

      foreach ($categories as $id => $cat) {
         CategoryModel::setLocalField($id, 'PermsDiscussionsView', $this->categoryPermissions[$id]);
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

   /**
    * Renders the group header on the discussion/announcement pages.
    *
    * @param $sender
    * @param $args
    * @return bool
    */
   public function base_beforeRenderAsset_handler($sender, $args) {
      if (val('AssetName', $args) == 'Content' && is_a($sender, 'DiscussionController')) {
         $groupId = $sender->Data('Discussion.GroupID');
         if (!$groupId) {
            return false;
         }
         $model = new GroupModel();
         $group = $model->getID($groupId);

         $params = array('group' => $group,
            'showButtons' => true,
            'showOptions' => true,
         );

         echo Gdn_Theme::module('GroupHeaderModule', $params);
      }
   }

   public function DiscussionController_Comment_Render($Sender, $Args) {
      $this->DiscussionController_Index_Render($Sender, $Args);
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

   /**
    * Increment the discussion aggregates on the group.
    *
    * @param DiscussionModel $Sender
    * @param array $Args
    */
   public function DiscussionModel_AfterSaveDiscussion_Handler($Sender, $Args) {
      $GroupID = Gdn::Request()->Get('groupid');
      if ($GroupID && $Args['Insert']) {
         $Model = new GroupModel();
         $Model->IncrementDiscussionCount($GroupID, 1, val('DiscussionID', $Args), valr('Fields.DateInserted', $Args));
      }
   }

   /**
    * Set the most recent comment on the group.
    *
    * @param CommentModel $Sender
    * @param array $Args
    */
   public function CommentModel_AfterSaveComment_Handler($Sender, $Args) {
      if ($Args['Insert']) {
         $CommentID = $Args['CommentID'];
         $DiscussionID = valr('FormPostValues.DiscussionID', $Args);
         $GroupID = Gdn::SQL()->GetWhere('Discussion', array('DiscussionID' => $DiscussionID))->Value('GroupID');
         if ($GroupID) {
            $Model = new GroupModel();
            $Model->SetField($GroupID, array(
               'DateLastComment' => valr('FormPostValues.DateInserted', $Args),
               'LastDiscussionID' => $DiscussionID,
               'LastCommentID' => $CommentID
            ));
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
      $DiscussionID = valr('ReflectArgs.DiscussionID', $Sender);
      if (!$DiscussionID) {
         $CommentID = valr('ReflectArgs.CommentID', $Sender);
         $CommentModel = new CommentModel();
         $Comment = $CommentModel->GetID($CommentID, DATASET_TYPE_ARRAY);
         $DiscussionID = $Comment['DiscussionID'];
      }
      $Discussion = $Sender->DiscussionModel->GetID($DiscussionID);

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

   public function DiscussionController_Comment_Before($Sender) {
       $this->OverridePermissions($Sender);
   }

   /**
    * Add groups link to mobile navigation.
    *
    * @param $sender
    */
   public function SiteNavModule_default_handler($sender) {
      $sender->addLink('main.groups', array('text' => t('Groups'), 'url' => '/groups', 'sort' => 1, 'icon' => icon('group')));
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
      $Sender->Preferences['Notifications']['Email.Groups'] = T('Notify me when there is group activity.');
      $Sender->Preferences['Notifications']['Popup.Groups'] = T('Notify me when there is group activity.');

      $Sender->Preferences['Notifications']['Email.Events'] = T('Notify me when there is event activity.');
      $Sender->Preferences['Notifications']['Popup.Events'] = T('Notify me when there is event activity.');
   }

    /**
     * Hide Private content.
     *
     * @param SearchController $Sender Sending controller.
     * @param array $Args Sending arguments.
     */
    public function SearchController_Render_Before($Sender, $Args) {

        $GroupCategoryIDs = Gdn::Cache()->Get('GroupCategoryIDs');
        if ($GroupCategoryIDs === Gdn_Cache::CACHEOP_FAILURE) {
            $CategoryModel = new CategoryModel();
            $GroupCategories = $CategoryModel->GetWhere(array('AllowGroups' => 1))->ResultArray();
            $GroupCategoryIDs = array();
            foreach ($GroupCategories as $GroupCategory) {
                $GroupCategoryIDs[] = $GroupCategory['CategoryID'];
            }

            Gdn::Cache()->Store('GroupCategoryIDs', $GroupCategoryIDs);
        }

        $SearchResults = $Sender->Data('SearchResults', array());
        foreach ($SearchResults as $ResultKey => &$Result) {
            $GroupID = val('GroupID', $Result, false);

            if (val('RecordType', $Result) === 'Group') {
               continue;
            } elseif ($GroupID || in_array($Result['CategoryID'], $GroupCategoryIDs)) {

                if (!$GroupID && val('RecordType', $Result, false) == 'Discussion') {

                    $DiscussionModel = new DiscussionModel();
                    $Discussion = $DiscussionModel->GetID($Result['PrimaryID']);
                    $GroupID = $Discussion->GroupID;

                } elseif (!$GroupID && val('RecordType', $Result, false) == 'Comment') {

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
