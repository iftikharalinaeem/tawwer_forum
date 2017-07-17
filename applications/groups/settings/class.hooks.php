<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Class GroupsHooks
 */
class GroupsHooks extends Gdn_Plugin {

    /**
     * Setup routine for when the application is enabled.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Run structure & default badges.
     */
    public function structure() {
        include(dirname(__FILE__).'/structure.php');
    }

    /**
     *
     *
     * @param null $Group
     */
    protected function setBreadcrumbs($Group = null) {
        if (!$Group)
            $Group = Gdn::Controller()->Data('Group', null);

        if ($Group) {
            $Sender = Gdn::Controller();
            $Sender->SetData('Breadcrumbs', []);
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
        $Sender->addCssFile('groups.css', 'groups');
    }

    /**
     * Add the "Groups" link to the main menu.
     */
    public function Base_Render_Before($Sender) {
        if (is_object($Menu = GetValue('Menu', $Sender))) {
            $Menu->AddLink('Groups', T('Groups'), '/groups/', false, ['class' => 'Groups']);
        }
    }

    /**
     * Interrupt category management to disallow deletion of the Social Groups category.
     *
     * @param $sender
     */
    public function SettingsController_Render_Before($sender) {
        if ($sender->RequestMethod == 'managecategories') {
            $categorydata = $sender->data('CategoryData');
            foreach ($categorydata->result() as $category) {
                if (val('AllowGroups', $category)) {
                     setValue('CanDelete', $category, 0);
                }
            }
        }
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function Base_ConversationGInvite_Handler($Sender, $Args) {
        $GroupID = $Sender->Data('Conversation.RegardingID');
        if ($GroupID) {
            echo Gdn_Theme::Module('GroupUserHeaderModule', ['GroupID' => $GroupID]);
        }
    }

    /**
     *
     * @param DbaController $Sender
     */
    public function DbaController_CountJobs_Handler($Sender) {
        $Counts = [
             'Group' => ['CountDiscussions', 'CountMembers', 'DateLastComment', 'LastDiscussionID']
        ];

        foreach ($Counts as $Table => $Columns) {
            foreach ($Columns as $Column) {
                $Name = "Recalculate $Table.$Column";
                $Url = "/dba/counts.json?".http_build_query(['table' => $Table, 'column' => $Column]);

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

        if (!$sender->data('Discussions') || !($sender->data('Discussions') instanceof Gdn_DataSet)) {
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
    public function discussionsController_bookmarkedPopin_render($sender, $args) {

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
            $groupID = $sender->Data('Discussion.GroupID');
            if (!$groupID) {
                return false;
            }
            $model = new GroupModel();
            $group = $model->getID($groupID);

            $params = ['group' => $group,
                'showButtons' => true,
                'showOptions' => true,
            ];

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
            $GroupID = Gdn::SQL()->GetWhere('Discussion', ['DiscussionID' => $DiscussionID])->Value('GroupID');
            if ($GroupID) {
                $Model = new GroupModel();
                $Model->SetField($GroupID, [
                    'DateLastComment' => valr('FormPostValues.DateInserted', $Args),
                    'LastDiscussionID' => $DiscussionID,
                    'LastCommentID' => $CommentID
                ]);
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
                $Args['DiscussionOptions']['DeleteDiscussion'] = [
                    'Label' => T('Delete Discussion'),
                    'Url' => '/discussion/delete?discussionid='.$Args['Discussion']->DiscussionID.'&target='.urlencode(GroupUrl($Group)),
                    'Class' => 'Popup'];
            }
        }
    }

    /**
     * Usually discussion view permissions are based on the Category View permissions.
     * In groups, discussion view permission is based on the group. This overrides the
     * calculation of discussion view permissions.
     *
     * @param $sender
     * @param $args
     */
    public function discussionModel_checkPermission_handler($sender, $args) {
         if (val('Permission', $args) === 'Vanilla.Discussions.View') {
              $discussion = val('Discussion', $args);
              $categoryID = val('CategoryID', $discussion);
              $userID = val('UserID', $args);
              if (in_array($categoryID, GroupModel::getGroupCategoryIDs()) && ($groupID = val('GroupID', $discussion, false))) {
                    $args['HasPermission'] = $this->canViewGroupContent($userID, $groupID);
              }
         }
    }

    /**
     * Checks whether a given user is able to view the content of a group.
     *
     * @param integer $userID The ID of the user to test.
     * @param integer $groupID The group ID.
     * @return bool Whether the user can view the group content.
     */
    protected function canViewGroupContent($userID, $groupID) {
        $groupModel = new GroupModel();
        $group = $groupModel->GetID($groupID);
        if (val('Privacy', $group) == 'Public') {
            return true;
        }
        if ($userID) {
            $userGroup = Gdn::SQL()->GetWhere('UserGroup', ['GroupID' => $groupID, 'UserID' => $userID])->FirstRow(DATASET_TYPE_ARRAY);
        }
        if ($userGroup) {
            return true;
        }
        return false;
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
     * @param SiteNavModule $sender
     */
    public function siteNavModule_init_handler($sender) {
        $sender->addLink(t('Groups'), '/groups', 'main.groups', '', [], ['icon' => 'group']);
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
                $Sender->SetData('Breadcrumbs', []);
                $Sender->AddBreadcrumb(T('Groups'), '/groups');
                $Sender->AddBreadcrumb($Group['Name'], GroupUrl($Group));
            }

            Gdn_Theme::section('Group');
        }
    }

    public function Base_AfterDiscussionFilters_Handler($Sender) {
        echo '<li class="Groups">'.Anchor(Sprite('SpGroups').' '.T('Groups'), '/groups').'</li> ';
    }

    /**
     * Override permissions for editing comments.
     *
     * @param PostController $sender
     */
    public function postController_editComment_before($sender) {
        $commentID = val('CommentID', $sender->ReflectArgs);
        if (!$commentID) {
            return;
        }

        // Get the groupID of this comment.
        $comment = $sender->CommentModel->getID($commentID);
        $discussion = $sender->DiscussionModel->getID(val('DiscussionID', $comment));
        $groupID = val('GroupID', $discussion);

        if (!$groupID) {
            return;
        }

        $model = new GroupModel();
        $group = $model->getID($groupID);
        if (!$group) {
            return;
        }

        $sender->setData('Group', $group);

        $model->overridePermissions($group);
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
        $GroupID = false;
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
            $Sender->ShowCategorySelector = false;

            // Reduce the announce options.
            $Options = [
                2 => '@'.T('Announce'),
                0 => '@'.T("Don't announce.")];
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

          $GroupCategoryIDs = GroupModel::getGroupCategoryIDs();

          $SearchResults = $Sender->Data('SearchResults', []);
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
                          Gdn::Cache()->Store(sprintf('Group.%s', $GroupID), $Group, [Gdn_Cache::FEATURE_EXPIRY => 15 * 60]);
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

    /**
     * Add groups categories to the searchable categories
     *
     * @param object $sender Sending instance
     * @param array $args Event's arguments
     */
    public function search_allowedCategories_handler($sender, $args) {
        $args['CategoriesID'] = array_merge($args['CategoriesID'], GroupModel::getGroupCategoryIDs());
    }

    /**
     * Add backwards compatibility for making undeletable categories.
     *
     * @param VanillaSettingsController $sender
     * @param array $args
     */
    public function vanillaSettingsController_render_before($sender, $args) {
        $categories = $sender->data('Categories');
        if (is_array($categories)) {
            $this->setCategoryCanDelete($categories);
            $sender->setData('Categories', $categories);
        }
    }

    /**
     * Format groups and events logs
     *
     * @param LogModel $sender
     * @param array $args
     */
    public function logModel_formatContent_handler($sender, $args) {
        $log = $args['Log'];
        if (!in_array($log['RecordType'], ['Group', 'Event'])) {
            return;
        }

        $data = $log['Data'];
        switch ($log['RecordType']) {
            case 'Group':
                $args['Result'] =
                    '<b>'.$sender->formatKey('Name', $data).'</b><br />'.
                    $sender->formatKey('Description', $data);
                break;
            case 'Event':
                $args['Result'] =
                    '<b>'.$sender->formatKey('Name', $data).'</b><br />'.
                    $sender->formatKey('Body', $data).'<br />'.
                    t('Location').': '.$sender->formatKey('Location', $data);
                break;
        }
    }

    /**
     * Disable deletion for Social Groups created before the CanDelete flag existed.
     *
     * @param array $categories
     */
    protected function setCategoryCanDelete(array &$categories) {
        foreach ($categories as &$category) {
            if (val('AllowGroups', $category)) {
                setValue('CanDelete', $category, 0);
            }

            // Descend into tree.
            if (!empty($category['Children'])) {
                $this->setCategoryCanDelete($category['Children']);
            }
        }
    }

    /**
     * Clear the GroupID field form a discussion that is moved.
     *
     * @param DiscussionModel $sender
     * @param array $args
     */
    public function discussionModel_afterSetField_handler($sender, $args) {
        $newCategoryID = valr('SetField.CategoryID', $args, false);
        // Make sure we are moving a discussion into a non group category!
        if ($newCategoryID && !in_array($newCategoryID, GroupModel::getGroupCategoryIDs())) {
            $sender->setField($args['DiscussionID'], 'GroupID', null);
        }
    }


    /**
     * Create a settings page for Groups.
     *
     * @param SettingsController $sender
     * @param array $args
     */
    public function settingsController_groups_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');
        $cf = new ConfigurationModule($sender);
        $cf->initialize([
            'Groups.Leaders.CanModerate' => [
                'LabelCode' => 'Allow Leaders of a Group to edit or delete comments and discussions in the groups they lead.',
                'Control' => 'toggle'
            ],
            'Groups.Members.CanAddEvents' => [
                'LabelCode' => 'Allow members to add events. Leaders will always be able to add events.',
                'Control' => 'toggle',
                'Default' => true
            ]
        ]);
        $sender->setData('Title', t('Group Settings'));
        $cf->renderAll();
    }
}
