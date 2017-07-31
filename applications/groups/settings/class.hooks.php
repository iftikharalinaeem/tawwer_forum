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
     * @param null $group
     */
    protected function setBreadcrumbs($group = null) {
        if (!$group)
            $group = Gdn::Controller()->Data('Group', null);

        if ($group) {
            $sender = Gdn::Controller();
            $sender->SetData('Breadcrumbs', []);
            $sender->AddBreadcrumb(T('Groups'), '/groups');
            $sender->AddBreadcrumb($group['Name'], GroupUrl($group));

            $sender->SetData('_CancelUrl', GroupUrl($group));
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

    public function AssetModel_StyleCss_Handler($sender, $args) {
        $sender->addCssFile('groups.css', 'groups');
    }

    /**
     * Add the "Groups" link to the main menu.
     */
    public function Base_Render_Before($sender) {
        if (is_object($menu = GetValue('Menu', $sender))) {
            $menu->AddLink('Groups', T('Groups'), '/groups/', false, ['class' => 'Groups']);
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
     * @param $sender
     * @param $args
     */
    public function Base_ConversationGInvite_Handler($sender, $args) {
        $groupID = $sender->Data('Conversation.RegardingID');
        if ($groupID) {
            echo Gdn_Theme::Module('GroupUserHeaderModule', ['GroupID' => $groupID]);
        }
    }

    /**
     *
     * @param DbaController $sender
     */
    public function DbaController_CountJobs_Handler($sender) {
        $counts = [
             'Group' => ['CountDiscussions', 'CountMembers', 'DateLastComment', 'LastDiscussionID']
        ];

        foreach ($counts as $table => $columns) {
            foreach ($columns as $column) {
                $name = "Recalculate $table.$column";
                $url = "/dba/counts.json?".http_build_query(['table' => $table, 'column' => $column]);

                $sender->Data['Jobs'][$name] = $url;
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
     * @param DiscussionController $sender
     * @param array $args
     * @throws Exception Throws an exception if the user doesn't have proper access to the group.
     */
    public function DiscussionController_Index_Render($sender, $args) {
        $groupID = $sender->Data('Discussion.GroupID');
        if (!$groupID)
            return;

        $viewPermission = GroupPermission('View', $groupID);
        if (!$viewPermission) {
            throw ForbiddenException('@'.GroupPermission('View.Reason', $groupID));
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

    public function DiscussionController_Comment_Render($sender, $args) {
        $this->DiscussionController_Index_Render($sender, $args);
    }

    public function DiscussionModel_BeforeSaveDiscussion_Handler($sender, $args) {
         $groupID = Gdn::Request()->Get('groupid');
         if ($groupID) {
            $model = new GroupModel();
            $group = $model->GetID($groupID);

            if ($group) {
                // TODO: Check permissions.
                $args['FormPostValues']['CategoryID'] = $group['CategoryID'];
                $args['FormPostValues']['GroupID'] = $groupID;

                Trace($args, 'Group set');
            }
        }
    }

    /**
     * Increment the discussion aggregates on the group.
     *
     * @param DiscussionModel $sender
     * @param array $args
     */
    public function DiscussionModel_AfterSaveDiscussion_Handler($sender, $args) {
        $groupID = Gdn::Request()->Get('groupid');
        if ($groupID && $args['Insert']) {
            $model = new GroupModel();
            $model->IncrementDiscussionCount($groupID, 1, val('DiscussionID', $args), valr('Fields.DateInserted', $args));
        }
    }

    /**
     * Set the most recent comment on the group.
     *
     * @param CommentModel $sender
     * @param array $args
     */
    public function CommentModel_AfterSaveComment_Handler($sender, $args) {
        if ($args['Insert']) {
            $commentID = $args['CommentID'];
            $discussionID = valr('FormPostValues.DiscussionID', $args);
            $groupID = Gdn::SQL()->GetWhere('Discussion', ['DiscussionID' => $discussionID])->Value('GroupID');
            if ($groupID) {
                $model = new GroupModel();
                $model->SetField($groupID, [
                    'DateLastComment' => valr('FormPostValues.DateInserted', $args),
                    'LastDiscussionID' => $discussionID,
                    'LastCommentID' => $commentID
                ]);
            }
        }
    }

    public function DiscussionModel_DeleteDiscussion_Handler($sender, $args) {
        $groupID = GetValueR('Discussion.GroupID', $args);
        if ($groupID) {
            $model = new GroupModel();
            $model->IncrementDiscussionCount($groupID, -1);
        }
    }

    /**
     * Delete discussion must redirect to Group instead of Category page.
     *
     * @param $sender
     * @param $args
     */
    public function DiscussionController_DiscussionOptions_Handler($sender, $args) {
        if ($groupID = val('GroupID', $args['Discussion'])) {
            if (GetValue('DeleteDiscussion', $args['DiscussionOptions'])) {
                // Get the group
                $model = new GroupModel();
                $group = $model->GetID($groupID);
                if (!$group)
                    return;

                // Override redirect with GroupUrl instead of CategoryUrl.
                $args['DiscussionOptions']['DeleteDiscussion'] = [
                    'Label' => T('Delete Discussion'),
                    'Url' => '/discussion/delete?discussionid='.$args['Discussion']->DiscussionID.'&target='.urlencode(GroupUrl($group)),
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

    protected function OverridePermissions($sender) {
        $discussionID = valr('ReflectArgs.DiscussionID', $sender);
        if (!$discussionID) {
            $commentID = valr('ReflectArgs.CommentID', $sender);
            $commentModel = new CommentModel();
            $comment = $commentModel->GetID($commentID, DATASET_TYPE_ARRAY);
            $discussionID = $comment['DiscussionID'];
        }
        $discussion = $sender->DiscussionModel->GetID($discussionID);

        $groupID = GetValue('GroupID', $discussion);
        if (!$groupID)
            return;
        $model = new GroupModel();
        $group = $model->GetID($groupID);
        if (!$group)
            return;
        $model->OverridePermissions($group);
    }

    /**
     *
     * @param DiscussionController $sender
     * @return type
     */
    public function DiscussionController_Announce_Before($sender) {
        $this->OverridePermissions($sender);
    }

    public function DiscussionController_Index_Before($sender) {
        $this->OverridePermissions($sender);
    }

    public function DiscussionController_Close_Before($sender) {
        $this->OverridePermissions($sender);
    }

    public function DiscussionController_Delete_Before($sender) {
        $this->OverridePermissions($sender);
    }

    public function DiscussionController_Comment_Before($sender) {
         $this->OverridePermissions($sender);
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
     * @param DiscussionController $sender
     */
    public function DiscussionController_Render_Before($sender) {
        $groupID = $sender->Data('Discussion.GroupID');
        if ($groupID) {
            // This is a group discussion. Modify the breadcrumbs.
            $model = new GroupModel();
            $group = $model->GetID($groupID);
            if ($group) {
                $sender->SetData('Breadcrumbs', []);
                $sender->AddBreadcrumb(T('Groups'), '/groups');
                $sender->AddBreadcrumb($group['Name'], GroupUrl($group));
            }

            Gdn_Theme::section('Group');
        }
    }

    public function Base_AfterDiscussionFilters_Handler($sender) {
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
     * @param PostController $sender
     */
    public function PostController_Discussion_Before($sender) {
        $groupID = $sender->Request->Get('groupid');

        if (!$groupID)
            return;

        $model = new GroupModel();
        $group = $model->GetID($groupID);
        if (!$group)
            return;

        $sender->SetData('Group', $group);

        $model->OverridePermissions($group);
    }

    /**
     *
     * @param PostController $sender
     * @return type
     */
    public function PostController_Comment_Before($sender) {
        $discussionID = $sender->Request->Get('discussionid');
        if (!$discussionID)
            return;
        $discussion = $sender->DiscussionModel->GetID($discussionID);
        $groupID = GetValue('GroupID', $discussion);
        if (!$groupID)
            return;

        $model = new GroupModel();
        $group = $model->GetID($groupID);
        if (!$group)
            return;

        $sender->SetData('Group', $group);

        $model->OverridePermissions($group);
    }

    public function PostController_EditDiscussion_Before($sender) {
        $discussionID = GetValue('DiscussionID', $sender->ReflectArgs);
        $groupID = false;
        if ($discussionID) {
            $discussion = $sender->DiscussionModel->GetID($discussionID);
            $groupID = GetValue('GroupID', $discussion);
        }

        if (!$groupID)
            return;

        $model = new GroupModel();
        $group = $model->GetID($groupID);
        if (!$group)
            return;

        $sender->SetData('Group', $group);
        $model->OverridePermissions($group);
    }

    /**
     *
     * @param PostController $sender
     */
    public function PostController_Render_Before($sender) {
        $group = $sender->Data('Group');

        if ($group) {
            // Hide the category drop-down.
            $sender->ShowCategorySelector = false;

            // Reduce the announce options.
            $options = [
                2 => '@'.T('Announce'),
                0 => '@'.T("Don't announce.")];
            $sender->SetData('_AnnounceOptions', $options);
        }

        $this->SetBreadcrumbs();
    }

    /**
     * Configure Groups/Events notification preferences
     *
     * @param type $sender
     */
    public function ProfileController_AfterPreferencesDefined_Handler($sender) {
        $sender->Preferences['Notifications']['Email.Groups'] = T('Notify me when there is group activity.');
        $sender->Preferences['Notifications']['Popup.Groups'] = T('Notify me when there is group activity.');

        $sender->Preferences['Notifications']['Email.Events'] = T('Notify me when there is event activity.');
        $sender->Preferences['Notifications']['Popup.Events'] = T('Notify me when there is event activity.');
    }

     /**
      * Hide Private content.
      *
      * @param SearchController $sender Sending controller.
      * @param array $args Sending arguments.
      */
     public function SearchController_Render_Before($sender, $args) {

          $groupCategoryIDs = GroupModel::getGroupCategoryIDs();

          $searchResults = $sender->Data('SearchResults', []);
          foreach ($searchResults as $resultKey => &$result) {
                $groupID = val('GroupID', $result, false);

                if (val('RecordType', $result) === 'Group') {
                    continue;
                } elseif ($groupID || in_array($result['CategoryID'], $groupCategoryIDs)) {

                     if (!$groupID && val('RecordType', $result, false) == 'Discussion') {

                          $discussionModel = new DiscussionModel();
                          $discussion = $discussionModel->GetID($result['PrimaryID']);
                          $groupID = $discussion->GroupID;

                     } elseif (!$groupID && val('RecordType', $result, false) == 'Comment') {

                          $commentModel = new CommentModel();
                          $comment = $commentModel->GetID($result['PrimaryID']);
                          $discussionModel = new DiscussionModel();
                          $discussion = $discussionModel->GetID($comment->DiscussionID);

                          $groupID = $discussion->GroupID;

                     }

                     $groupModel = new GroupModel();
                     $group = Gdn::Cache()->Get(sprintf('Group.%s', $groupID));
                     if ($group === Gdn_Cache::CACHEOP_FAILURE) {
                          $group = $groupModel->GetID($groupID);
                          Gdn::Cache()->Store(sprintf('Group.%s', $groupID), $group, [Gdn_Cache::FEATURE_EXPIRY => 15 * 60]);
                     }

                     if ($group['Privacy'] == 'Private' && !$groupModel->CheckPermission('View', $group['GroupID'])) {
                          unset($searchResults[$resultKey]);
                          $result['Title'] = '** Private **';
                          $result['Summary'] = '** Private **';
                     }

                }
                $sender->SetData('SearchResults', $searchResults);
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
