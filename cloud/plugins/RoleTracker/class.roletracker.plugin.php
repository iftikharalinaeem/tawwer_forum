<?php
/**
 * RoleTracker Plugin
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

use Vanilla\Utility\HtmlUtils;

/**
 * Class RoleTrackerPlugin
 */
class RoleTrackerPlugin extends Gdn_Plugin {
    /**
     * Setup is called when the plugin is enabled. It prepares the config and db.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Update the DB structure. Called on /utility/update and when the plugin is enabled
     */
    public function structure() {
        Gdn::structure()->table('Role')
            ->column('IsTracked', 'tinyint(1)', 0)
            ->column('TrackerTagID', 'int(11)', true)
            ->set(false, false);
    }

    #######################################
    ## Plugin's functions
    #######################################

    /**
     * Generate a valid css class from a role's name.
     *
     * @param string $roleName role name
     *
     * @return string CSS class
     */
    private function formatRoleCss($roleName) {
        return str_replace(' ', '_', Gdn_Format::alphaNumeric(strtolower($roleName))).'-tracker';
    }

    /**
     * Add user role tracker's tag(s) to discussion.
     *
     * @param int $discussionID Identifier of the discussion
     * @param int $userID User identifier
     */
    private function addUserTagsToDiscussion($discussionID, $userID) {
        $userTrackedRoles = RoleTrackerModel::instance()->getUserTrackedRoles($userID);
        if (!$userTrackedRoles) {
            return;
        }

        $tagModel = TagModel::instance();
        $tagIDs = array_column($userTrackedRoles, 'TrackerTagID');

        $tagModel->addDiscussion($discussionID, $tagIDs);
    }

    #######################################
    ## Plugin's hooks
    #######################################

    /**
     * Create a method called "roletracker" on the SettingsController.
     *
     * @param SettingsController $sender Sending controller instance.
     */
    public function settingsController_roleTracker_create($sender) {
        // Prevent non-admins from accessing this page
        $sender->permission('Garden.Settings.Manage');

        $sender->title(sprintf(t('%s Settings'), t('RoleTracker')));
        $sender->addSideMenu('settings/roletracker');

        $sender->setData('PluginDescription', $this->getPluginKey('Description'));

        $sender->Form = new Gdn_Form();

        $roleTrackerModel = RoleTrackerModel::instance();
        $formData = $roleTrackerModel->getFormData(false);
        $sender->Form->setModel($roleTrackerModel, $formData);

        $sender->setData('Roles', $roleTrackerModel->getPublicRoles());

        // If we are not seeing the form for the first time
        if ($sender->Form->authenticatedPostBack() !== false) {
            if ($sender->Form->save() !== false) {
                $sender->informMessage(t('Your changes have been saved.'));
            }
        }

        $sender->render($sender->fetchViewLocation('settings', '', 'plugins/RoleTracker'));
    }

    /**
     * Add Types to TagModel
     *
     * @param TagModel $sender Sending controller instance
     */
    public function tagModel_types_handler($sender) {
        $sender->addType('Tracker', [
            'key' => 'Tracker',
            'name' => 'Tracker',
            'plural' => 'Trackers',
            'addtag' => false,
            'default' => false
        ]);
    }

    /**
     * Join the roles to every discussions and comments. Also add CSS hook to the tracked discussions.
     *
     * @param Gdn_Controller $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function base_render_before($sender, $args) {
        $sender->addJsFile('roletracker.js', 'plugins/RoleTracker');

        $multiple = true;

        $discussions = $sender->data('Discussions');
        if (!$discussions) {
            $discussion = $sender->data('Discussion');
            if ($discussion) {
                $discussions = [$discussion];
                $multiple = false;
            }
        }

        if (!$discussions) {
            return;
        }

        $wrapGdnDataSet = false;
        if ($discussions instanceof Gdn_DataSet) {
            $wrapGdnDataSet = true;
            $discussions = $discussions->result();
        }

        // Join the users' role(s) to the discussion and comments
        RoleModel::setUserRoles($discussions, 'InsertUserID');
        $comments = $sender->data('Comments');
        if ($comments) {
            RoleModel::setUserRoles($comments->result(), 'InsertUserID');
        }

        foreach ($discussions as &$discussion) {
            // And add the css class to the discussion
            $roles = val('Roles', $discussion, []);
            $tags = val('Tags', $discussion);
            if (!$tags) {
                $tags = val('Tracker', val('XTags', $discussion));
            }

            if (is_array($roles) && count($roles) && is_array($tags) && count($tags)) {
                $tags = Gdn_DataSet::index($tags, 'TagID');
                $trackedRoles = RoleTrackerModel::instance()->getTrackedRoles();

                $cssTrackerRoles = [];
                foreach ($roles as $roleID => $roleName) {
                    if (array_key_exists($roleID, $trackedRoles) && isset($tags[$trackedRoles[$roleID]['TrackerTagID']])) {
                        $cssTrackerRoles[] = $this->formatRoleCss($roleName);
                    }
                }

                if (!empty($cssTrackerRoles)) {
                    $discussion->_CssClass = val('_CssClass', $discussion, '').' tracked '.implode(' ', $cssTrackerRoles);
                }
            }
        }

        if ($multiple) {
            if ($wrapGdnDataSet) {
                $discussions = new Gdn_DataSet($discussions);
            }

            $sender->setData('Discussions', $discussions, true);
        } else {
            $sender->setData('Discussion', $discussions[0], true);
        }
    }

    /**
     * Add CSS hook to the tracked discussions
     *
     * @param DiscussionsController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function base_beforeDiscussionName_handler($sender, $args) {
        $discussion = val('Discussion', $args);
        if (!$discussion) {
            return;
        }

        // Used by $this->base_discussionMeta_handler
        $sender->EventArguments['DiscussionIsTracked'] = false;

        // Determine if the discussion is tagged with the TrackerTagID of a role.
        $tags = val('Tags', $discussion, []);
        if (is_array($tags) && count($tags)) {
            $tags = Gdn_DataSet::index($tags, 'TagID');
            $trackedRoles = RoleTrackerModel::instance()->getTrackedRoles();

            $cssTrackerRoles = [];
            foreach ($trackedRoles as $roleID => $roleData) {
                if (isset($tags[$roleData['TrackerTagID']])) {
                    $cssTrackerRoles[] = $this->formatRoleCss($roleData['Name']);
                }
            }

            if (!empty($cssTrackerRoles)) {
                $args['CssClass'] = val('CssClass', $args, '').' tracked '.implode(' ', $cssTrackerRoles);
                $sender->EventArguments['DiscussionIsTracked'] = true;
            }
        }
    }

    /**
     * Add the tracked role CSS to every comments
     *
     * @param Gdn_Controller $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function base_beforeCommentDisplay_handler($sender, $args) {
        $trackedRoles = RoleTrackerModel::instance()->getTrackedRoles();

        $cssClass = val('CssClass', $args, null);

        $cssTrackerRoles = [];
        foreach (val('Roles', $args['Comment'], []) as $roleID => $roleName) {
            if (array_key_exists($roleID, $trackedRoles)) {
                $cssTrackerRoles[] = $this->formatRoleCss($roleName);
            }
        }

        if (!empty($cssTrackerRoles)) {
            $args['CssClass'] = trim($cssClass.' '.implode(' ', $cssTrackerRoles));
        }
    }

    /**
     * Add "untracking" options on discussions.
     *
     * @param Gdn_Controller $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function base_discussionOptions_handler($sender, $args) {
        $trackedRoles = RoleTrackerModel::instance()->getTrackedRoles();
        if (!$trackedRoles) {
            return;
        }

        $discussion = $args['Discussion'];
        if (!Gdn::session()->checkPermission('Vanilla.Discussions.Edit', true, 'Category', val('PermissionCategoryID', $discussion))) {
            return;
        }

        $discussionTags = val('Tags', $discussion);
        if ($discussionTags) {
            $discussionTags = Gdn_DataSet::index($discussionTags, 'TagID');
        } else {
            $tagModel = new TagModel();
            $discussionTags = $tagModel->getDiscussionTags(val('DiscussionID', $discussion), TagModel::IX_TAGID);
        }
        if (!$discussionTags) {
            return;
        }

        $trackedRolesByTag = Gdn_DataSet::index($trackedRoles, 'TrackerTagID');
        $discussionsTrackedTagIDs = array_intersect(array_keys($trackedRolesByTag), array_keys($discussionTags));
        if (!$discussionsTrackedTagIDs) {
            return;
        }

        $url = '/roletracker/untrack/'.val('DiscussionID', $discussion);
        $label = t('Role Tracker').'...';
        if (isset($args['DiscussionOptions'])) {
            $args['DiscussionOptions']['RoleTracker'] = ['Label' => $label, 'Url' => $url, 'Class' => 'Popup'];
        } elseif (isset($sender->Options)) {
            $sender->Options .= '<li>'.anchor($label, $url, 'Popup RoleTrackerOptions') . '</li>';
        }
    }

    /**
     * Inject tracker roles' tag into authorInfo
     *
     * @param DiscussionController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionController_authorInfo_handler($sender, $args) {
        $postType = $args['Type'];
        $post = $args[$postType];

        if (!val('Roles', $post)) {
            return;
        }

        $discussion = val('Discussion', $args);
        $comment = val('Comment', $args);

        writePostTrackedTags($discussion, $comment);
    }

    /**
     * Add user role tracker's tag(s) to discussion after discussion save.
     *
     * @param PostController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function postController_afterDiscussionSave_handler($sender, $args) {
        $discussion = $args['Discussion'];
        $this->addUserTagsToDiscussion(val('DiscussionID', $discussion), val('InsertUserID', $discussion));
    }

    /**
     * Prevent the tagging plugin from linking an user generated tag that
     * would have the same name than a RoleTracker tag.
     *
     * @param TaggingPlugin $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function taggingPlugin_saveDiscussion_handler($sender, $args) {
        $trackedRoles = RoleTrackerModel::instance()->getTrackedRoles();
        if (!$trackedRoles) {
            return;
        }

        $tagIDs = array_column($trackedRoles, 'TrackerTagID');
        $tags = TagModel::instance()->getWhere(['TagID' => $tagIDs])->resultArray();
        $tagNames = array_column($tags, 'Name');
        array_walk($tagNames, 'strtolower');

        foreach ($args['Tags'] as $index => $tag) {
            if (in_array(strtolower($tag), $tagNames)) {
                unset($args['Tags'][$index]);
            }
        }
    }

    /**
     * Add user role tracker's tag(s) to discussion after comment save.
     *
     * @param PostController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function postController_afterCommentSave_handler($sender, $args) {
        $this->addUserTagsToDiscussion(val('DiscussionID', $args['Discussion']), val('InsertUserID', $args['Comment']));
    }

    /**
     * Add user role tracker's tag(s) in discussions list.
     *
     * @param object $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function base_afterDiscussionLabels_handler($sender, $args) {
        static $tagIDs = null;

        $discussion = val('Discussion', $args, false);
        $discussionTags = val('Tags', $discussion, false);
        if (!$discussion || !$discussionTags) {
            return;
        }

        $trackedRoles = RoleTrackerModel::instance()->getTrackedRoles();
        if (!$trackedRoles) {
            return;
        }

        if ($tagIDs === null) {
            $tagIDs = array_column($trackedRoles, 'TrackerTagID');
        }

        $linksTitle = t('Jump to first tracked post.');
        foreach ($discussionTags as $tagData) {
            if (in_array($tagData['TagID'], $tagIDs)) {
                $tagFullName = htmlspecialchars(t($tagData['FullName']));
                $tagName = htmlspecialchars(strtolower(t($tagData['Name'])));
                $accessibleLabel= HtmlUtils::accessibleLabel(
                    '%s for discussion: "%s"',
                    [
                        sprintf('Tagged with "%s"', $tagName),
                        is_array($discussion) ? $discussion["Name"] : $discussion->Name
                    ]
                );
                echo ' <a href="'.url('/roletracker/jump/'.val('DiscussionID', $discussion)).'"
                    nofollow aria-label="'. $accessibleLabel . '"
                    class="Tag tag-tracker tag-'.$tagName.'-tracker"
                    title="'.$linksTitle.'">'.$tagFullName.'</a> ';
            }
        }
    }
}

if (!function_exists('writePostTrackedTags')) {
    function writePostTrackedTags($discussion, $comment = false) {
        static $tags = null;

        $discussionID = val('DiscussionID', $discussion);

        if ($comment) {
            $from = val('DateInserted', $comment);
            $postRoles = val('Roles', $comment);
        } else {
            $from = val('DateInserted', $discussion);
            $postRoles = val('Roles', $discussion);
        }
        $from = rawurlencode($from);

        $trackedRoles = RoleTrackerModel::instance()->getTrackedRoles();
        $tagsID = [];

        foreach ($postRoles as $roleID => $roleName) {
            if (array_key_exists($roleID, $trackedRoles)) {
                $tagsID[] = $trackedRoles[$roleID]['TrackerTagID'];
            }
        }

        if (!$tagsID) {
            return;
        }

        // In case we fiddled with the tags and assigned multiple roles to 1 tag :D
        $tagsID = array_unique($tagsID);

        if ($tags === null) {
            $tagModel = TagModel::instance();
            $tags = $tagModel->getWhere(['Type' => 'Tracker'])->resultArray();
            $tags = Gdn_DataSet::index($tags, 'TagID');
        }

        $classes = [];
        $names = [];
        foreach ($tagsID as $tagID) {
            if (!isset($tags[$tagID])) {
                continue;
            }
            $trackerTag = $tags[$tagID];
            $tagFullName = htmlspecialchars(t($trackerTag['FullName']));
            $tagName = htmlspecialchars(strtolower(t($trackerTag['Name'])));
            // Keep those spaces before and after the tag :D
            $classes[] = 'tag-'.$tagName.'-tracker';
            $names[] = $tagFullName;
        }

        echo '<span class="MItem RoleTracker">'
            .'<a href="'.url("/roletracker/jump/$discussionID/$from").'" nofollow class="JumpTo Next Tag tag-tracker '.implode(' ', $classes).'" title="'.t('[Title] Next', 'Next tracked post').'">'
                .implode(c('RoleTrackerTagSeparator', '&#8729;'), $names).' '.c('RoleTrackerTagIcon', '&rsaquo;')
            .'</a>'
         .'</span> ';
    }
}