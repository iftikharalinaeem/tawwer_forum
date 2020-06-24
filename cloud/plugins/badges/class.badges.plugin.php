<?php
/**
 * @copyright 2011-2016 Vanilla Forums, Inc.
 * @author Lincoln Russell <lincoln@vanillaforums.com>
 * @package Badges
 */
use \Garden\Container\Reference;
use Vanilla\Menu\CounterModel;
use Vanilla\Badges\Menu\BadgesCounterProvider;

/**
 * Places badges hooks into other applications.
 */
class BadgesHooks extends Gdn_Plugin {
    /**
     * @param \Garden\Container\Container $container
     */
    public function container_init(\Garden\Container\Container $container) {
        $container->rule(CounterModel::class)
            ->addCall('addProvider', [new Reference(BadgesCounterProvider::class)]);
    }
    /**
     * Allow badge syncing from hub.
     *
     * @param $sender
     * @param $args
     */
    public function multisiteModel_syncNodes_handler($sender, $args) {
        $args['urls'][] = '/badges/syncnode.json';
    }

    /**
     * Add mapper methods
     *
     * @param SimpleApiPlugin $sender
     */
    public function simpleApiPlugin_mapper_handler($sender) {
        switch ($sender->Mapper->Version) {
            case '1.0':
                $sender->Mapper->addMap([
                    'badges/give'              => 'badge/giveuser',
                    'badges/revoke'            => 'badge/revoke',
                    'badges/user'              => 'badges/user',
                    'badges/list'              => 'badges/all',
                    'badges/get'               => 'badge',
                    'badges/add'               => 'badge/manage',
                    'badges/edit'              => 'badge/manage',
                    'badges/delete'            => 'badge/delete',
                ]);
                break;
        }
    }

    /**
     * Add styling.
     *
     * @param $sender
     * @param $args
     */
    public function assetModel_styleCss_handler($sender, $args) {
        $sender->addCssFile('badges.css', 'plugins/badges');
    }

    /**
     * Trigger Connect-type badges.
     *
     * @param $sender
     * @param $args
     */
    public function base_afterConnection_handler($sender, $args) {
        $badgeModel = new BadgeModel();
        $userBadgeModel = new UserBadgeModel();
        $badges = $badgeModel->getByType('Connect');

        foreach ($badges as $badge) {
            if ($badge['Attributes']['Provider'] == $args['Provider']) {
                $userBadgeModel->give(valr('User.UserID', $args), $badge);
                break;
            }
        }
    }

    /**
     * Delete & log UserPoints when a user is deleted.
     *
     * @param $sender
     * @param $args
     */
    public function base_beforeDeleteUser_handler($sender, $args) {
        Gdn::userModel()->getDelete('UserPoints', ['UserID' => $args['UserID']], $args['Content']);
    }

    /**
     * Adds items to Dashboard menu.
     *
     * @since 1.0.0
     * @param object $sender dashboardNavModule.
     */
    public function dashboardNavModule_init_handler($sender) {
        $sender->addLinkToSectionIf('Garden.Settings.Manage', 'Settings', t('Badges'), '/badge/all', 'users.badges');
        $sender->addLinkToSectionIf('Reputation.Badges.Give', 'Moderation', t('Badge Requests'), '/badge/requests', 'site.badges-give');
    }

    /**
     * Adds "Badge Requests" to MeModule menu.
     *
     * @param MeModule $sender
     * @param array $args
     */
    public function meModule_flyoutMenu_handler($sender, $args) {
        if (!val('Dropdown', $args, false) || !checkPermission('Reputation.Badges.Give')) {
            return;
        }
        if (!$sender->data('BadgeRequestCount', '')) {
            $ubm = new UserBadgeModel();
            $sender->setData('BadgeRequestCount', $ubm->getBadgeRequestCount());
        }

        /** @var DropdownModule $dropdown */
        $badgeModifiers['listItemCssClasses'] = ['BadgeRequests', 'link-badge-requests'];
        $badgeModifiers['badge'] = $sender->data('BadgeRequestCount', 0);
        $dropdown = $args['Dropdown'];
        $dropdown->addLink(t('Badge Requests'), '/badge/requests', 'moderation.badge-requests', '', [], $badgeModifiers);
    }

    /**
     * Adds count for badge requests to MeModule's dashboard notification count.
     *
     * @param MeModule $sender
     * @param array $args
     */
    public function meModule_beforeFlyoutMenu_handler($sender, $args) {
        if (checkPermission('Reputation.Badges.Give')) {
            if (!$sender->data('BadgeRequestCount', '')) {
                $ubm = new UserBadgeModel();
                $sender->setData('BadgeRequestCount', $ubm->getBadgeRequestCount());
            }
            $args['DashboardCount'] = $args['DashboardCount'] + $sender->data('BadgeRequestCount', 0);
        }
    }

    /**
     * Trigger NameDropper.
     *
     * @param $sender
     * @param $args
     */
    public function commentModel_beforeNotification_handler($sender, $args) {
        $this->nameDropper($sender, $args);
    }

    /**
     * Trigger NameDropper.
     *
     * @param $sender
     * @param $args
     */
    public function discussionModel_beforeNotification_handler($sender, $args) {
        $this->nameDropper($sender, $args);
    }

    /**
     * Calculate whether badges are awarded when a user visits.
     */
//    public function gdn_Session_AfterGetSession_Handler($Sender, $Args) {
//        $this->freshStart($Sender, $Args);
//        $this->attendance($Sender, $Args);
//    }

    /**
     * Add options to profile menu.
     *
     * @since 1.0.0
     * @access public
     */
    public function base_beforeProfileOptions_handler($sender, $args) {
        // Add 'Give Badge' to profiles
        if (checkPermission('Reputation.Badges.Give')) {
            $args['ProfileOptions'][] = ['Text' => t('Give Badge'), 'Url' => '/badge/giveuser/'.$args['UserID'].'/', 'CssClass' => 'Popup'];
        }
    }

    /**
     * Adds email notification options to profiles.
     *
     * @since 1.0.0
     * @access public
     * @param object $sender ProfileController.
     */
    public function profileController_afterPreferencesDefined_handler($sender) {
        $sender->Preferences['Notifications']['Email.Badge'] = t('PreferenceBadgeEmail', 'Notify me when I earn a badge.');
        $sender->Preferences['Notifications']['Popup.Badge'] = t('PreferenceBadgePopup', 'Notify me when I earn a badge.');

        if (Gdn::session()->checkPermission('Reputation.Badges.Give')) {
            $sender->Preferences['Notifications']['Email.BadgeRequest'] = t('Notify me when a badge is requested.');
            $sender->Preferences['Notifications']['Popup.BadgeRequest'] = t('Notify me when a badge is requested.');

            // Save to list of users for notifications
            if ($sender->Form->authenticatedPostBack()) {
                $set = [];
                $prefixes = ['Email.BadgeRequest', 'Popup.BadgeRequest'];
                foreach ($prefixes as $prefix) {
                    $value = $sender->Form->getFormValue($prefix, null);
                    $set[$prefix] = ($value) ? $value : null;
                }
                UserModel::setMeta($sender->User->UserID, $set, 'Preferences.');
            }
        }
    }

    /**
     * Show user's badges in profile.
     */
//    public function profileController_Badges_Create($Sender) {
//        $Sender->permission('Reputation.Badges.View');
//
//        // User data
//        $UserReference = arrayValue(0, $Sender->RequestArgs, '');
//        $Username = arrayValue(1, $Sender->RequestArgs, '');
//
//        // Tell the ProfileController what tab to load
//        $Sender->getUserInfo($UserReference, $Username);
//        $Sender->setTabView('Badges', 'profile', 'Badge', 'Reputation');
//
//        // Get User's badges
//        $UserBadgeModel = new userBadgeModel();
//        $Sender->BadgeData = $UserBadgeModel->getBadges($Sender->User->UserID);
//        $Sender->setData('Badges', $Sender->BadgeData, TRUE);
//
//        $Sender->render();
//    }

    /**
     * Add 'Badges' tab & module to profiles.
     *
     * @since 1.0.0
     * @access public
     */
    public function profileController_render_before($sender) {
        if (c('Badges.BadgesModule.Target', 'Panel') == 'Panel') {
            $sender->addModule('BadgesModule');
        }
    }

    /**
     * Add 'Badges' tab & module to profiles.
     *
     * @access public
     */
    public function base_afterUserInfo_handler($sender, $args) {
        if (c('Badges.BadgesModule.Target') == 'AfterUserInfo') {
            echo Gdn_Theme::module('BadgesModule');
        }
    }

    /**
     * Add 'Badges' tab & module to profiles.
     *
     * @access public
     */
    public function base_beforeUserInfo_handler($sender, $args) {
        if (c('Badges.BadgesModule.Target') == 'BeforeUserInfo') {
            echo Gdn_Theme::module('BadgesModule');
        }
    }

    /**
     * General configuration page for Badges.
     */
    public function settingsController_badges_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');

        if ($sender->Form->authenticatedPostback()) {
            $excludePermission = $sender->Form->getFormValue('ExcludePermission', '');

            if ($excludePermission === 'None') {
                removeFromConfig('Badges.ExcludePermission');
            } else {
                saveToConfig('Badges.ExcludePermission', $excludePermission);
            }
        } else {
            $sender->Form->setValue('ExcludePermission', c('Badges.ExcludePermission', 'None'));
        }

        $sender->title(sprintf(t('%s settings'), t('Badges')));
        $sender->setData('PluginDescription', $this->getPluginKey('Description'));
        $sender->addSideMenu('dashboard/settings/plugins');
        $sender->render($sender->fetchViewLocation('settings', '', 'plugins/badges'));
    }

    /**
     *
     *
     * @param UserModel $sender
     */
    public function userModel_visit_handler($sender, $args) {
        $this->anniversaries($sender, $args);
    }

    /**
     * Calculate whether any badges should be given... because of badges given.
     */
    public function userBadgeModel_afterGive_handler($sender, $args) {
        $this->comboBreaker($sender, $args);
    }

    /**
     * Adds badge total to UserInfo module.
     *
     * @since 1.0.0
     * @access public
     *
     * @param object $sender ProfileController.
     * @todo Put in a view.
     */
    public function userInfoModule_onBasicInfo_handler($sender) {
        echo ' '.wrap(t('Badges'), 'dt', ['class' => 'Badges']);
        echo ' '.wrap(valr('User.CountBadges', $sender, 0), 'dd', ['class' => 'Badges']);
    }

    /**
     * Calculate whether badges should be awarded after user is saved.
     */
    public function userModel_afterSave_handler($sender, $args) {
        $this->photogenic($sender, $args);
    }

    /**
     * Calculate whether badges should be awarded after user is updated.
     */
    public function userModel_afterSetField_handler($sender, $args) {
        $userID = $args['UserID'];
        $fields = $args['Fields'];

        if (isset($fields['CountComments']) || isset($fields['CountPosts'])) {
            $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
            $fields['CountPosts'] = $user['CountComments'] + $user['CountDiscussions'];
        }

        // Collect all of the count fields.
        $counts = [];
        foreach ($fields as $name => $value) {
            if (stringBeginsWith($name, 'Count') || in_array($name, ['Likes'])) {
                $counts[$name] = $value;
            }
        }

        if (count($counts) == 0) {
            return;
        }

        $badgeModel = new BadgeModel();
        $userBadgeModel = new UserBadgeModel();

        $userCountBadges = $badgeModel->getByType('UserCount');
        foreach ($userCountBadges as $badge) {
            if (val('Attributes', $badge)) {
                $column = $badge['Attributes']['Column'];
                if (isset($counts[$column]) && $counts[$column] >= $badge['Threshold']) {
                    $userBadgeModel->give($userID, $badge);
                }
            }
        }
    }

    /**
     * Run structure & default badges.
     */
    public function setup() {
        // If Reputation is enabled, disable it.
        if (Gdn::addonManager()->isEnabled('reputation', \Vanilla\Addon::TYPE_ADDON)) {
            Gdn::applicationManager()->disableApplication('reputation');
        }

        $this->structure();
    }

    /**
     * Include our separate structure file because hot damn there's a lot to do.
     */
    public function structure() {
        require(dirname(__FILE__).'/structure.php');
    }

    /**
     * Add leaderboards to activity page.
     *
     * @param ActivityController $sender
     * @param array $args
     */
    public function activityController_render_before($sender, $args) {
        if ($sender->deliveryMethod() == DELIVERY_METHOD_XHTML) {
            $sender->addModule('LeaderBoardModule');

            $module = new LeaderBoardModule();
            $module->SlotType = 'a';
            $sender->addModule($module);
        }
    }

    /**
     * Custom badge trigger: All 'Anniversary'-class badges (X years of membership).
     *
     * @param $sender
     * @param $args
     */
    public function anniversaries($sender, $args) {
        if (Gdn::session()->isValid()) {
            $userBadgeModel = new UserBadgeModel();
            $firstVisit = val('DateFirstVisit', Gdn::session()->User);
            if (!$firstVisit) {
                return;
            }
            $firstVisit = Gdn_Format::toTimestamp($firstVisit);
            $today = time();
            $userID = val('UserID', Gdn::session()->User);

            // Give most recent anniversary badge they've earned
            for ($i = 11; $i >= 1; $i--) {
                $badgeTime = strtotime("+$i years", $firstVisit);
                if ($badgeTime <= $today) {
                    $suffix = ($i > 10) ? '-old' : (($i > 1) ? '-'.$i : '');
                    $userBadgeModel->give($userID, 'anniversary'.$suffix);
                }
            }
        }
    }

    /**
     * Badge type trigger: 'Attendance' (X consecutive day visits).
     *
     * @todo Finish this trigger.
     *
     * @param $sender
     * @param $args
     */
    public function attendance($sender, $args) {
        $badgeModel = new BadgeModel();
        $userCountBadges = $badgeModel->getByType('Attendance');
        foreach ($userCountBadges as $badge) {
            // Skip completed
            if (val('DateCompleted', $badge)) {
                continue;
            }

            // Log progress
            $attributes = val('Attributes', $badge);
            if (false) {
                //$UserBadgeModel->give($UserID, $Badge);
            }
        }
    }

    /**
     * Custom badge trigger: 'Combo Breaker' (5 badges in 1 day).
     *
     * @param $sender
     * @param $args
     */
    public function comboBreaker($sender, $args) {
        // Get badge given
        $userBadge = val('UserBadge', $args);

        // Register timeout event
        $userBadgeModel = new UserBadgeModel();
        $userBadgeModel->addTimeoutEvent(val('UserID', $userBadge), 'combo', val('DateCompleted', $userBadge));
    }

    /**
     * Badge type trigger: 'DiscussionContent' (pattern matching in Body of Discussions & Comments).
     *
     * @param $sender
     * @param $args
     */
    public function discussionContent($sender, $args) {
        $badgeModel = new BadgeModel();
        $userBadgeModel = new UserBadgeModel();
        $body = val('Body', val('FormPostValues', $args));

        $userCountBadges = $badgeModel->getByType('DiscussionContent');
        foreach ($userCountBadges as $badge) {
            if ($attributes = val('Attributes', $badge)) {
                $pattern = val('Pattern', $attributes);
                if (preg_match($pattern, $body)) {
                    $userBadgeModel->give($userID, $badge);
                }
            }
        }
    }

    /**
     * Custom badge trigger: 'Fresh Start' (visited Jan 1).
     *
     * @param $sender
     * @param $args
     */
    public function freshStart($sender, $args) {
        if (date('j') === '1' && date('n') === '1') {
            $user = val('User', $args);
            $userBadgeModel = new UserBadgeModel();
            $userBadgeModel->give($user->UserID, 'fresh-start');
        }
    }

    /**
     * Custom badge trigger: 'Lightning Reflexes' (comment within 1 min of discussion start).
     *
     * @param $sender
     * @param $args
     */
    public function lightningReflexes($sender, $args) {
        // Get Comment & its Discussion
        $comment = $sender->SQL->getWhere('Comment', ['CommentID' => val('CommentID', $args)])->firstRow();
        $discussion = $sender->SQL->getWhere('Discussion', ['DiscussionID' => val('DiscussionID', $comment)])->firstRow();

        // Did less than 60 seconds elapse between Discussion & Comment insertion?
        $elapsedSeconds = strtotime(val('DateInserted', $comment)) - strtotime(val('DateInserted', $discussion));
        if ($elapsedSeconds < 60) {
            $userBadgeModel = new UserBadgeModel();
            $userBadgeModel->give($comment->InsertUserID, 'lightning');
        }
    }

    /**
     * Custom badge trigger: 'Name Dropper' (mentioned another user).
     *
     * @param $sender
     * @param $args
     */
    public function nameDropper($sender, $args) {
        $mentions = val('MentionedUsers', $args, []);
        if (count($mentions)) {
            $comment = val('Comment', $args, val('Discussion', $args));
            $userBadgeModel = new UserBadgeModel();
            $userBadgeModel->give(val('InsertUserID', $comment), 'name-dropper');
        }
    }

    /**
     * Custom badge trigger: 'Photogenic' (uploaded a profile photo).
     *
     * @param $sender
     * @param $args
     */
    public function photogenic($sender, $args) {
        $userID = $args['UserID'];
        $user = $args['Fields'];

        $userBadgeModel = new UserBadgeModel();

        if (val('Photo', $user)) {
            $userBadgeModel->give($userID, 'photogenic');
        }
    }

    /**
     * Custom badge trigger: 'Welcome Committee' (comment in user's first discussion).
     *
     * @param $sender
     * @param $args
     */
    public function welcomeCommittee($sender, $args) {
        // Get current discussion
        $discussionModel = new DiscussionModel();
        $discussionID = val('DiscussionID', val('FormPostValues', $args));
        $discussion = $discussionModel->getID($discussionID);

        // Is it discussion starter's first?
        $firstDiscussion = $discussionModel->getWhere(['InsertUserID' => val('InsertUserID', $discussion)], 'DateInserted', 'asc', 1);
        if ($discussionID == val('DiscussionID', $firstDiscussion)) {
            $userBadgeModel = new UserBadgeModel();
            $userBadgeModel->give(Gdn::session()->UserID, 'welcome');
        }
    }
}
