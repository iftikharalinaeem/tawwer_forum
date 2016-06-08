<?php
/**
 * RoleTracker Plugin
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

$PluginInfo['RoleTracker'] = [
    'Name' => 'Role Tracker',
    'Description' => 'Tag and track posts made by users in selected roles.',
    'Version' => '1.0',
    'RequiredApplications' => ['Vanilla' => '2.2.111'],
    'RequiredPlugins' => ['Tagging' => '1.9.0'],
    'HasLocale' => false,
    'License' => 'GNU GPL2',
    'SettingsUrl' => '/settings/roletracker',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => 'Alexandre (DaazKu) Chouinard',
    'AuthorEmail' => 'alexandre.c@vanillaforums.com'
];

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
            ->column('TrackerTagID', 'tinyint(1)', true)
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
     * Add a link to the dashboard menu.
     *
     * @param object $sender Sending controller instance.
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = &$sender->EventArguments['SideMenu'];
        $menu->addLink('Add-ons', t('Role Tracker'), 'settings/roletracker', 'Garden.Settings.Manage');
    }

    /**
     * Create a method called "roletracker" on the SettingsController.
     *
     * @param SettingsController $sender Sending controller instance.
     */
    public function settingsController_roleTracker_create($sender) {
        // Prevent non-admins from accessing this page
        $sender->permission('Garden.Settings.Manage');

        $sender->title(sprintf(t('%s settings'), t('RoleTracker')));
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
        $sender->addType('Tracker', array(
            'key' => 'Tracker',
            'name' => 'Tracker',
            'plural' => 'Trackers',
            'addtag' => false,
            'default' => false
        ));
    }

    /**
     * Join the roles to every discussions and comments. Also add  CSS to the discussion
     *
     * @param DiscussionController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionController_render_before($sender, $args) {
        if (!val('Discussion', $sender)) {
            return;
        }

        // Join the users' role(s) to the discussion and comments
        $joinDiscussion = [$sender->Discussion];
        RoleModel::setUserRoles($joinDiscussion, 'InsertUserID');
        $comments = $sender->data('Comments');
        RoleModel::setUserRoles($comments->result(), 'InsertUserID');

        // And add the css class to the discussion
        if (is_array($sender->Discussion->Roles)) {
            if (count($sender->Discussion->Roles)) {
                $trackedRoles = RoleTrackerModel::instance()->getTrackedRoles();
                $cssTrackerRoles = [];
                foreach (val('Roles', $sender->Discussion, []) as $roleID => $roleName) {
                    if (array_key_exists($roleID, $trackedRoles)) {
                        $cssTrackerRoles[] = $this->formatRoleCss($roleName);
                    }
                }

                if (!empty($cssTrackerRoles)) {
                    $sender->Discussion->_CssClass = val('_CssClass', $sender->Discussion, '').' '.implode(' ', $cssTrackerRoles);
                }
            }
        }
    }

    /**
     * Add the tracked role CSS to every comments
     *
     * @param object $sender Sending controller instance.
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
     * Inject tracker roles' tag into authorInfo
     *
     * @param DiscussionController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionController_authorInfo_handler($sender, $args) {
        static $tags = null;

        $target = $args['Type'];

        $postRoles = val('Roles', $args[$target]);
        if (!$postRoles) {
            return;
        }
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

        if ($tags === null) {
            $tagModel = TagModel::instance();
            $tags = $tagModel->getWhere(['Type' => 'Tracker'])->resultArray();
            $tags = Gdn_DataSet::index($tags, 'TagID');
        }

        $postTags = '';
        foreach ($tagsID as $tagID) {
            if (!isset($tags[$tagID])) {
                continue;
            }
            $trackerTag = $tags[$tagID];
            $tagName = htmlspecialchars($trackerTag['FullName']);
            // Keep those spaces before and after the tag :D
            $postTags .= ' <span class="Tag tag-'.strtolower(t($trackerTag['Name'])).'-tracker">'.$tagName.'</span> ';
        }

        echo '<span class="MItem RoleTracker"><span class="Tags">'.$postTags.'</span></span> ';
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

        foreach ($discussionTags as $tagData) {
            if (in_array($tagData['TagID'], $tagIDs)) {
                $tagName = htmlspecialchars(t($tagData['FullName']));
                echo ' <span class="Tag tag-'.strtolower(t($tagData['Name'])).'-tracker">'.$tagName.'</span> ';

            }
        }

    }

}
