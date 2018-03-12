<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

use \Garden\Schema\Schema;
use \Vanilla\Web\Controller;

/**
 * Class GroupsHooks
 */
class GroupsHooks extends Gdn_Plugin {

    /** @var GroupModel */
    private $groupModel;

    /**
     * GroupsHooks constructor.
     *
     * @param GroupModel $groupModel
     */
    public function __construct(GroupModel $groupModel) {
        $this->groupModel = $groupModel;
    }

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
            $group = Gdn::controller()->data('Group', null);

        if ($group) {
            $sender = Gdn::controller();
            $sender->setData('Breadcrumbs', []);
            $sender->addBreadcrumb(t('Groups'), '/groups');
            $sender->addBreadcrumb($group['Name'], groupUrl($group));

            $sender->setData('_CancelUrl', groupUrl($group));
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

    public function assetModel_styleCss_handler($sender, $args) {
        $sender->addCssFile('groups.css', 'groups');
    }

    /**
     * Add the "Groups" link to the main menu.
     */
    public function base_render_before($sender) {
        if (is_object($menu = getValue('Menu', $sender))) {
            $menu->addLink('Groups', t('Groups'), '/groups/', false, ['class' => 'Groups']);
        }
    }

    /**
     * Interrupt category management to disallow deletion of the Social Groups category.
     *
     * @param $sender
     */
    public function settingsController_render_before($sender) {
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
    public function base_conversationGInvite_handler($sender, $args) {
        $groupID = $sender->data('Conversation.RegardingID');
        if ($groupID) {
            echo Gdn_Theme::module('GroupUserHeaderModule', ['GroupID' => $groupID]);
        }
    }

    /**
     *
     * @param DbaController $sender
     */
    public function dbaController_countJobs_handler($sender) {
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
    public function discussionController_index_render($sender, $args) {
        $groupID = $sender->data('Discussion.GroupID');
        if (!$groupID)
            return;

        $viewPermission = groupPermission('View', $groupID);
        if (!$viewPermission) {
            throw forbiddenException('@'.groupPermission('View.Reason', $groupID));
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
            $groupID = $sender->data('Discussion.GroupID');
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

    public function discussionController_comment_render($sender, $args) {
        $this->discussionController_index_render($sender, $args);
    }

    public function discussionModel_beforeSaveDiscussion_handler($sender, $args) {
         $groupID = Gdn::request()->get('groupid');
         if ($groupID) {
            $model = new GroupModel();
            $group = $model->getID($groupID);

            if ($group) {
                // TODO: Check permissions.
                $args['FormPostValues']['CategoryID'] = $group['CategoryID'];
                $args['FormPostValues']['GroupID'] = $groupID;

                trace($args, 'Group set');
            }
        }
    }

    /**
     * Increment the discussion aggregates on the group.
     *
     * @param DiscussionModel $sender
     * @param array $args
     */
    public function discussionModel_afterSaveDiscussion_handler($sender, $args) {
        $groupID = Gdn::request()->get('groupid');
        if ($groupID && $args['Insert']) {
            $model = new GroupModel();
            $model->incrementDiscussionCount($groupID, 1, val('DiscussionID', $args), valr('Fields.DateInserted', $args));
        }
    }

    /**
     * Set the most recent comment on the group.
     *
     * @param CommentModel $sender
     * @param array $args
     */
    public function commentModel_afterSaveComment_handler($sender, $args) {
        if ($args['Insert']) {
            $commentID = $args['CommentID'];
            $discussionID = valr('FormPostValues.DiscussionID', $args);
            $groupID = Gdn::sql()->getWhere('Discussion', ['DiscussionID' => $discussionID])->value('GroupID');
            if ($groupID) {
                $model = new GroupModel();
                $model->setField($groupID, [
                    'DateLastComment' => valr('FormPostValues.DateInserted', $args),
                    'LastDiscussionID' => $discussionID,
                    'LastCommentID' => $commentID
                ]);
            }
        }
    }

    public function discussionModel_deleteDiscussion_handler($sender, $args) {
        $groupID = getValueR('Discussion.GroupID', $args);
        if ($groupID) {
            $model = new GroupModel();
            $model->incrementDiscussionCount($groupID, -1);
        }
    }

    /**
     * Delete discussion must redirect to Group instead of Category page.
     *
     * @param $sender
     * @param $args
     */
    public function discussionController_discussionOptions_handler($sender, $args) {
        if ($groupID = val('GroupID', $args['Discussion'])) {
            if (getValue('DeleteDiscussion', $args['DiscussionOptions'])) {
                // Get the group
                $model = new GroupModel();
                $group = $model->getID($groupID);
                if (!$group)
                    return;

                // Override redirect with GroupUrl instead of CategoryUrl.
                $args['DiscussionOptions']['DeleteDiscussion'] = [
                    'Label' => t('Delete Discussion'),
                    'Url' => '/discussion/delete?discussionid='.$args['Discussion']->DiscussionID.'&target='.urlencode(groupUrl($group)),
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
        $group = $groupModel->getID($groupID);
        if (val('Privacy', $group) == 'Public') {
            return true;
        }
        if ($userID) {
            $userGroup = Gdn::sql()->getWhere('UserGroup', ['GroupID' => $groupID, 'UserID' => $userID])->firstRow(DATASET_TYPE_ARRAY);
        }
        if ($userGroup) {
            return true;
        }
        return false;
    }

    protected function overridePermissions($sender) {
        $reflectArgs = property_exists($sender, 'ReflectArgs') ? array_change_key_case($sender->ReflectArgs) : [];
        $discussionID = val('discussionid', $reflectArgs);
        if (!$discussionID) {
            $commentID = val('commentid', $reflectArgs);
            $commentModel = new CommentModel();
            $comment = $commentModel->getID($commentID, DATASET_TYPE_ARRAY);
            $discussionID = $comment['DiscussionID'];
        }
        $discussion = $sender->DiscussionModel->getID($discussionID);

        $groupID = val('GroupID', $discussion);
        if (!$groupID)
            return;
        $model = new GroupModel();
        $group = $model->getID($groupID);
        if (!$group)
            return;
        $model->overridePermissions($group);
    }

    /**
     *
     * @param DiscussionController $sender
     * @return type
     */
    public function discussionController_announce_before($sender) {
        $this->overridePermissions($sender);
    }

    public function discussionController_index_before($sender) {
        $this->overridePermissions($sender);
    }

    public function discussionController_close_before($sender) {
        $this->overridePermissions($sender);
    }

    public function discussionController_delete_before($sender) {
        $this->overridePermissions($sender);
    }

    public function discussionController_comment_before($sender) {
         $this->overridePermissions($sender);
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
    public function discussionController_render_before($sender) {
        $groupID = $sender->data('Discussion.GroupID');
        if ($groupID) {
            // This is a group discussion. Modify the breadcrumbs.
            $model = new GroupModel();
            $group = $model->getID($groupID);
            if ($group) {
                $sender->setData('Breadcrumbs', []);
                $sender->addBreadcrumb(t('Groups'), '/groups');
                $sender->addBreadcrumb($group['Name'], groupUrl($group));
            }

            Gdn_Theme::section('Group');
        }
    }

    public function base_afterDiscussionFilters_handler($sender) {
        echo '<li class="Groups">'.anchor(sprite('SpGroups').' '.t('Groups'), '/groups').'</li> ';
    }

    /**
     * Override permissions for editing comments.
     *
     * @param PostController $sender
     */
    public function postController_editComment_before($sender) {
        $commentID = val('commentid', array_change_key_case($sender->ReflectArgs));
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
    public function postController_discussion_before($sender) {
        $groupID = $sender->Request->get('groupid');

        if (!$groupID)
            return;

        $model = new GroupModel();
        $group = $model->getID($groupID);
        if (!$group)
            return;

        $sender->setData('Group', $group);

        $model->overridePermissions($group);
    }

    /**
     *
     * @param PostController $sender
     * @return type
     */
    public function postController_comment_before($sender) {
        $discussionID = $sender->Request->get('discussionid');
        if (!$discussionID)
            return;
        $discussion = $sender->DiscussionModel->getID($discussionID);
        $groupID = getValue('GroupID', $discussion);
        if (!$groupID)
            return;

        $model = new GroupModel();
        $group = $model->getID($groupID);
        if (!$group)
            return;

        $sender->setData('Group', $group);

        $model->overridePermissions($group);
    }

    public function postController_editDiscussion_before($sender) {
        $discussionID = val('discussionid', array_change_key_case($sender->ReflectArgs));
        $groupID = false;
        if ($discussionID) {
            $discussion = $sender->DiscussionModel->getID($discussionID);
            $groupID = val('GroupID', $discussion);
        }

        if (!$groupID)
            return;

        $model = new GroupModel();
        $group = $model->getID($groupID);
        if (!$group)
            return;

        $sender->setData('Group', $group);
        $model->overridePermissions($group);
    }

    /**
     *
     * @param PostController $sender
     */
    public function postController_render_before($sender) {
        $group = $sender->data('Group');

        if ($group) {
            // Hide the category drop-down.
            $sender->ShowCategorySelector = false;

            // Reduce the announce options.
            $options = [
                2 => '@'.t('Announce'),
                0 => '@'.t("Don't announce.")];
            $sender->setData('_AnnounceOptions', $options);
        }

        $this->setBreadcrumbs();
    }

    /**
     * Configure Groups/Events notification preferences
     *
     * @param type $sender
     */
    public function profileController_afterPreferencesDefined_handler($sender) {
        $sender->Preferences['Notifications']['Email.Events'] = t('Notify me when there is event activity.');
        $sender->Preferences['Notifications']['Popup.Events'] = t('Notify me when there is event activity.');
    }

     /**
      * Hide Private content.
      *
      * @param SearchController $sender Sending controller.
      * @param array $args Sending arguments.
      */
     public function searchController_render_before($sender, $args) {

          $groupCategoryIDs = GroupModel::getGroupCategoryIDs();

          $searchResults = $sender->data('SearchResults', []);
          foreach ($searchResults as $resultKey => &$result) {
                $groupID = val('GroupID', $result, false);

                if (val('RecordType', $result) === 'Group') {
                    continue;
                } elseif ($groupID || in_array($result['CategoryID'], $groupCategoryIDs)) {

                     if (!$groupID && val('RecordType', $result, false) == 'Discussion') {

                          $discussionModel = new DiscussionModel();
                          $discussion = $discussionModel->getID($result['PrimaryID']);
                          $groupID = $discussion->GroupID;

                     } elseif (!$groupID && val('RecordType', $result, false) == 'Comment') {

                          $commentModel = new CommentModel();
                          $comment = $commentModel->getID($result['PrimaryID']);
                          $discussionModel = new DiscussionModel();
                          $discussion = $discussionModel->getID($comment->DiscussionID);

                          $groupID = $discussion->GroupID;

                     }

                     $groupModel = new GroupModel();
                     $group = Gdn::cache()->get(sprintf('Group.%s', $groupID));
                     if ($group === Gdn_Cache::CACHEOP_FAILURE) {
                          $group = $groupModel->getID($groupID);
                          Gdn::cache()->store(sprintf('Group.%s', $groupID), $group, [Gdn_Cache::FEATURE_EXPIRY => 15 * 60]);
                     }

                     if ($group['Privacy'] == 'Private' && !$groupModel->checkPermission('View', $group['GroupID'])) {
                          unset($searchResults[$resultKey]);
                          $result['Title'] = '** Private **';
                          $result['Summary'] = '** Private **';
                     }

                }
                $sender->setData('SearchResults', $searchResults);
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

    /**
     * Add groupID to the discussionSchema
     *
     * @param Schema $schema
     */
    public function discussionSchema_init(Schema $schema) {
        $schema->merge(Schema::parse([
            'groupID:i|n?' => 'The group the discussion is in.',
        ]));
    }

    /**
     * Add groupID to the discussionSchema
     *
     * @param Schema $schema
     */
    public function discussionGetEditSchema_init(Schema $schema) {
        $schema->merge(Schema::parse([
            'groupID:i|n?' => 'The group the discussion is in.',
        ]));
    }

    /**
     * Add groupID to the discussionIndexSchema.
     *
     * @param Schema $schema
     */
    public function discussionIndexSchema_init(Schema $schema) {
        $schema->merge(Schema::parse([
            'groupID:i|n?' => 'The group the discussion is in.',
        ]));
    }

    /**
     * Add groupID to the discussionPostSchema.
     *
     * @param Schema $schema
     */
    public function discussionPostSchema_init(Schema $schema) {
        // Remove the required flag from categoryID and add a requireOneOf rule below.
        $schema['required'] = array_values(array_filter($schema['required'], function($value) {
            return $value !== 'categoryID';
        }));

        $groupModel = new GroupModel();
        $schema
            ->merge(Schema::parse([
                'groupID:i|n?' => 'The group the discussion is in.',
            ]))
            ->requireOneOf(['categoryID', 'groupID'])
            ->addFilter('', function($value, $field) use ($groupModel) {
                if (!empty($value['groupID'])) {
                    $group = $groupModel->getID($value['groupID']);

                    if (!$group) {
                        $field->addError('invalidGroup', [
                            'messageCode' => 'The group {groupID} does not exists.',
                            'groupID' => $value['groupID'],
                        ]);
                    } else {
                        if (!array_key_exists('categoryID', $value)) {
                            $value['categoryID'] = $groupModel::getGroupCategoryIDs()[0];
                        }
                    }
                }
                return $value;
            })
        ;
    }

    /**
     * Add groupID to the discussions index's filters' where array.
     *
     * @param array $where Where clause as array
     * @param DiscussionsAPIController $controller
     * @param Schema $inSchema
     * @param array $query
     * @return array Where clause as array
     */
    public function discussionsApiController_indexFilters(
        $where,
        DiscussionsAPIController $controller,
        Schema $inSchema,
        array $query
    ) {
        if (!isset($query['groupID'])) {
            return $where;
        }

        $where['groupID'] = $query['groupID'];
        return $where;
    }

    /**
     * Add group fields to search schema.
     *
     * @param Schema $schema
     */
    public function searchResultSchema_init(Schema $schema) {
        $recordTypes = $schema->getField('properties.recordType.enum');
        $recordTypes[] = 'group';
        $types = $schema->getField('properties.type.enum');
        $types[] = 'group';

        $schema->merge(Schema::parse([
            'recordType' => [
                'enum' => $recordTypes,
            ],
            'groupID:i|n?' => 'The id of the group or the id of the group containing the record.',
        ]));
        $schema->setField('properties.type.enum', $types);
    }

    /**
     * Hook into the pre normalization process of the searchAPIController to fill out missing information about group records.
     *
     * @param array $records
     * @param SearchApiController $searchApiController
     * @param array $options
     * @return array
     */
    public function searchApiController_preNormalizeOutputs($records, SearchApiController $searchApiController, $options) {
        $groupIDs = [];

        foreach ($records as $record) {
            if ($record['RecordType'] === 'Group') {
                $groupIDs[] = $record['PrimaryID'];
            }
        }

        $groups = [];
        if ($groupIDs) {
            $groups = $this->groupModel->getWhere(['GroupID' => $groupIDs])->resultArray();
            $groups = Gdn_DataSet::index($groups, 'GroupID');
        }

        if ($groups) {
            foreach ($records as &$record) {
                if ($record['RecordType'] === 'Group') {
                    $record['UpdateUserID'] = $groups[$record['PrimaryID']]['UpdateUserID'] ?? null;
                    $record['DateUpdated'] = $groups[$record['PrimaryID']]['DateUpdated'] ?? null;
                }
            }
        }

        return $records;
    }
}
