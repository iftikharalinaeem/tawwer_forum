<?php
/**
 * @copyright 2011-2015 Vanilla Forums, Inc.
 * @author Lincoln Russell <lincoln@vanillaforums.com>
 * @package Badges
 */

$PluginInfo['badges'] = array(
    'Name' => 'Badges',
    'Description' => "Give badges to your users to reward them for contributing to your community.",
    'Version' => '1.4.2',
    'RegisterPermissions' => array(
        'Reputation.Badges.View' => 1,
        'Reputation.Badges.Request',
        'Reputation.Badges.Give' => 'Garden.Settings.Manage',
        'Reputation.Badges.Manage' => 'Garden.Settings.Manage'
    ),
    'Author' => "Lincoln Russell",
    'AuthorEmail' => 'lincoln@vanillaforums.com',
    'AuthorUrl' => 'http://lincolnwebs.com',
    'License' => 'Proprietary'
);

/**
 * Places badges hooks into other applications.
 */
class BadgesHooks extends Gdn_Plugin {

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
     * @param SimpleApiPlugin $Sender
     */
    public function simpleApiPlugin_mapper_handler($Sender) {
        switch ($Sender->Mapper->Version) {
            case '1.0':
                $Sender->Mapper->addMap(array(
                    'badges/give'              => 'badge/giveuser',
                    'badges/revoke'            => 'badge/revoke',
                    'badges/user'              => 'badges/user',
                    'badges/list'              => 'badges/all',
                    'badges/get'               => 'badge',
                    'badges/add'               => 'badge/manage',
                    'badges/edit'              => 'badge/manage',
                    'badges/delete'            => 'badge/delete',
                ));
                break;
        }
    }

    /**
     * Add styling.
     *
     * @param $Sender
     * @param $Args
     */
    public function assetModel_styleCss_handler($Sender, $Args) {
        $Sender->addCssFile('badges.css', 'plugins/badges');
    }

    /**
     * Trigger Connect-type badges.
     *
     * @param $Sender
     * @param $Args
     */
    public function base_afterConnection_handler($Sender, $Args) {
        $BadgeModel = new BadgeModel();
        $UserBadgeModel = new UserBadgeModel();
        $Badges = $BadgeModel->getByType('Connect');

        foreach ($Badges as $Badge) {
            if ($Badge['Attributes']['Provider'] == $Args['Provider']) {
                $UserBadgeModel->give(valr('User.UserID', $Args), $Badge);
                break;
            }
        }
    }

    /**
     * Delete & log UserPoints when a user is deleted.
     *
     * @param $Sender
     * @param $Args
     */
    public function base_beforeDeleteUser_handler($Sender, $Args) {
        Gdn::userModel()->getDelete('UserPoints', array('UserID' => $Args['UserID']), $Args['Content']);
    }

    /**
     * Adds items to Dashboard menu.
     *
     * @since 1.0.0
     * @param object $Sender DashboardController.
     */
    public function base_getAppSettingsMenuItems_handler($Sender) {
        $Menu = &$Sender->EventArguments['SideMenu'];
        $Menu->addLink('Reputation', t('Badges'), '/badge/all', 'Garden.Settings.Manage', array('class' => 'nav-badges'));
        $Menu->addLink('Reputation', t('Badge Requests'), '/badge/requests', 'Reputation.Badges.Give', array('class' => 'nav-badge-requests'));
    }

    /**
     * Trigger NameDropper.
     *
     * @param $Sender
     * @param $Args
     */
    public function commentModel_beforeNotification_handler($Sender, $Args) {
        $this->nameDropper($Sender, $Args);
    }

    /**
     * Trigger NameDropper.
     *
     * @param $Sender
     * @param $Args
     */
    public function discussionModel_beforeNotification_handler($Sender, $Args) {
        $this->nameDropper($Sender, $Args);
    }

    /**
     * Calculate whether badges are awarded when a user visits.
     */
//    public function Gdn_Session_AfterGetSession_Handler($Sender, $Args) {
//        $this->FreshStart($Sender, $Args);
//        $this->Attendance($Sender, $Args);
//    }

    /**
     * Add options to profile menu.
     *
     * @since 1.0.0
     * @access public
     */
    public function base_beforeProfileOptions_handler($Sender, &$Args) {
        $SideMenu = val('SideMenu', $Args);

        // Add 'Give Badge' to profiles
        if (checkPermission('Reputation.Badges.Give')) {
            $Args['ProfileOptions'][] = array('Text' => t('Give Badge'), 'Url' => '/badge/giveuser/'.$Args['UserID'].'/', 'CssClass' => 'Popup');
        }
    }

    /**
     * Adds email notification options to profiles.
     *
     * @since 1.0.0
     * @access public
     * @param object $Sender ProfileController.
     */
    public function profileController_afterPreferencesDefined_handler($Sender) {
        $Sender->Preferences['Notifications']['Email.Badge'] = t('PreferenceBadgeEmail', 'Notify me when I earn a badge.');
        $Sender->Preferences['Notifications']['Popup.Badge'] = t('PreferenceBadgePopup', 'Notify me when I earn a badge.');

        if (Gdn::session()->checkPermission('Reputation.Badges.Give')) {
            $Sender->Preferences['Notifications']['Email.BadgeRequest'] = t('Notify me when a badge is requested.');
            $Sender->Preferences['Notifications']['Popup.BadgeRequest'] = t('Notify me when a badge is requested.');

            // Save to list of users for notifications
            if ($Sender->Form->authenticatedPostBack()) {
                $Set = array();
                $Prefixes = array('Email.BadgeRequest', 'Popup.BadgeRequest');
                foreach ($Prefixes as $Prefix) {
                    $Value = $Sender->Form->getFormValue($Prefix, null);
                    $Set[$Prefix] = ($Value) ? $Value : null;
                }
                UserModel::setMeta($Sender->User->UserID, $Set, 'Preferences.');
            }
        }
    }

    /**
     * Show user's badges in profile.
     *
     * @todo
     */
//    public function ProfileController_Badges_Create($Sender) {
//        $Sender->Permission('Reputation.Badges.View');
//
//        // User data
//        $UserReference = ArrayValue(0, $Sender->RequestArgs, '');
//        $Username = ArrayValue(1, $Sender->RequestArgs, '');
//
//        // Tell the ProfileController what tab to load
//        $Sender->GetUserInfo($UserReference, $Username);
//        $Sender->SetTabView('Badges', 'profile', 'Badge', 'Reputation');
//
//        // Get User's badges
//        $UserBadgeModel = new UserBadgeModel();
//        $Sender->BadgeData = $UserBadgeModel->GetBadges($Sender->User->UserID);
//        $Sender->SetData('Badges', $Sender->BadgeData, TRUE);
//
//        $Sender->Render();
//    }

    /**
     * Add 'Badges' tab & module to profiles.
     *
     * @since 1.0.0
     * @access public
     */
    public function ProfileController_Render_Before($Sender) {
        if (c('Badges.BadgesModule.Target', 'Panel') == 'Panel') {
            $Sender->addModule('BadgesModule');
        }
    }

    /**
     * Add 'Badges' tab & module to profiles.
     *
     * @access public
     */
    public function Base_AfterUserInfo_Handler($Sender, $Args) {
        if (c('Badges.BadgesModule.Target') == 'AfterUserInfo') {
            echo Gdn_Theme::module('BadgesModule');
        }
    }

    /**
     * Add 'Badges' tab & module to profiles.
     *
     * @access public
     */
    public function Base_BeforeUserInfo_Handler($Sender, $Args) {
        if (c('Badges.BadgesModule.Target') == 'BeforeUserInfo') {
            echo Gdn_Theme::module('BadgesModule');
        }
    }

    /**
     *
     *
     * @param UserModel $Sender
     */
    public function userModel_visit_handler($Sender, $Args) {
        $this->anniversaries($Sender, $Args);
    }

    /**
     * Calculate whether any badges should be given... because of badges given.
     */
    public function userBadgeModel_afterGive_handler($Sender, $Args) {
        $this->comboBreaker($Sender, $Args);
    }

    /**
     * Adds badge total to UserInfo module.
     *
     * @since 1.0.0
     * @access public
     *
     * @param object $Sender ProfileController.
     * @todo Put in a view.
     */
    public function userInfoModule_onBasicInfo_handler($Sender) {
        echo ' '.wrap(t('Badges'), 'dt', array('class' => 'Badges'));
        echo ' '.wrap(valr('User.CountBadges', $Sender, 0), 'dd', array('class' => 'Badges'));
    }

    /**
     * Calculate whether badges should be awarded after user is saved.
     */
    public function userModel_afterSave_handler($Sender, $Args) {
        $this->photogenic($Sender, $Args);
    }

    /**
     * Calculate whether badges should be awarded after user is updated.
     */
    public function userModel_afterSetField_handler($Sender, $Args) {
        $UserID = $Args['UserID'];
        $Fields = $Args['Fields'];

        if (isset($Fields['CountComments']) || isset($Fields['CountPosts'])) {
            $User = Gdn::userModel()->getID($UserID, DATASET_TYPE_ARRAY);
            $Fields['CountPosts'] = $User['CountComments'] + $User['CountDiscussions'];
        }

        // Collect all of the count fields.
        $Counts = array();
        foreach ($Fields as $Name => $Value) {
            if (StringBeginsWith($Name, 'Count') || in_array($Name, array('Likes'))) {
                $Counts[$Name] = $Value;
            }
        }

        if (count($Counts) == 0) {
            return;
        }

        $BadgeModel = new BadgeModel();
        $UserBadgeModel = new UserBadgeModel();

        $UserCountBadges = $BadgeModel->getByType('UserCount');
        foreach ($UserCountBadges as $Badge) {
            if (GetValue('Attributes', $Badge)) {
                $Column = $Badge['Attributes']['Column'];
                if (isset($Counts[$Column]) && $Counts[$Column] >= $Badge['Threshold']) {
                    $UserBadgeModel->give($UserID, $Badge);
                }
            }
        }
    }

    /**
     * Run structure & default badges.
     */
    public function setup() {
        Gdn::applicationManager()->disableApplication('reputation');
        $this->structure();
    }

    /**
     * Include our separate structure file because hot damn there's a lot to do.
     */
    public function structure() {
        require_once(dirname(__FILE__).'/structure.php');
    }

    /**
     * Add leaderboards to activity page.
     *
     * @param ActivityController $Sender
     * @param array $Args
     */
    public function activityController_render_before($Sender, $Args) {
        if ($Sender->deliveryMethod() == DELIVERY_METHOD_XHTML) {
            $Sender->addModule('LeaderBoardModule');

            $Module = new LeaderBoardModule();
            $Module->SlotType = 'a';
            $Sender->addModule($Module);
        }
    }

    /**
     * Custom badge trigger: All 'Anniversary'-class badges (X years of membership).
     *
     * @param $Sender
     * @param $Args
     */
    public function anniversaries($Sender, $Args) {
        if (Gdn::session()->isValid()) {
            $UserBadgeModel = new UserBadgeModel();
            $FirstVisit = val('DateFirstVisit', Gdn::session()->User);
            if (!$FirstVisit) {
                return;
            }
            $FirstVisit = Gdn_Format::toTimestamp($FirstVisit);
            $Today = time();
            $UserID = val('UserID', Gdn::session()->User);

            // Give most recent anniversary badge they've earned
            for ($i = 11; $i >= 1; $i--) {
                $BadgeTime = strtotime("+$i years", $FirstVisit);
                if ($BadgeTime <= $Today) {
                    $Suffix = ($i > 10) ? '-old' : (($i > 1) ? '-'.$i : '');
                    $UserBadgeModel->give($UserID, 'anniversary'.$Suffix);
                }
            }
        }
    }

    /**
     * Badge type trigger: 'Attendance' (X consecutive day visits).
     *
     * @todo Finish this trigger.
     *
     * @param $Sender
     * @param $Args
     */
    public function attendance($Sender, $Args) {
        $BadgeModel = new BadgeModel();
        $UserCountBadges = $BadgeModel->getByType('Attendance');
        foreach ($UserCountBadges as $Badge) {
            // Skip completed
            if (val('DateCompleted', $Badge)) {
                continue;
            }

            // Log progress
            $Attributes = val('Attributes', $Badge);
            if (false) {
                //$UserBadgeModel->give($UserID, $Badge);
            }
        }
    }

    /**
     * Custom badge trigger: 'Combo Breaker' (5 badges in 1 day).
     *
     * @param $Sender
     * @param $Args
     */
    public function comboBreaker($Sender, $Args) {
        // Get badge given
        $UserBadge = val('UserBadge', $Args);

        // Register timeout event
        $UserBadgeModel = new UserBadgeModel();
        $EventCount = $UserBadgeModel->addTimeoutEvent(val('UserID', $UserBadge), 'combo', val('DateCompleted', $UserBadge));
    }

    /**
     * Badge type trigger: 'DiscussionContent' (pattern matching in Body of Discussions & Comments).
     *
     * @param $Sender
     * @param $Args
     */
    public function discussionContent($Sender, $Args) {
        $BadgeModel = new BadgeModel();
        $UserBadgeModel = new UserBadgeModel();
        $Body = val('Body', val('FormPostValues', $Args));

        $UserCountBadges = $BadgeModel->getByType('DiscussionContent');
        foreach ($UserCountBadges as $Badge) {
            if ($Attributes = val('Attributes', $Badge)) {
                $Pattern = val('Pattern', $Attributes);
                if (preg_match($Pattern, $Body)) {
                    $UserBadgeModel->give($UserID, $Badge);
                }
            }
        }
    }

    /**
     * Custom badge trigger: 'Fresh Start' (visited Jan 1).
     *
     * @param $Sender
     * @param $Args
     */
    public function FreshStart($Sender, $Args) {
        if (date('j') === '1' && date('n') === '1') {
            $User = val('User', $Args);
            $UserBadgeModel = new UserBadgeModel();
            $UserBadgeModel->give($User->UserID, 'fresh-start');
        }
    }

    /**
     * Custom badge trigger: 'Lightning Reflexes' (comment within 1 min of discussion start).
     *
     * @param $Sender
     * @param $Args
     */
    public function lightningReflexes($Sender, $Args) {
        // Get Comment & its Discussion
        $Comment = $Sender->SQL->getWhere('Comment', array('CommentID' => val('CommentID', $Args)))->firstRow();
        $Discussion = $Sender->SQL->getWhere('Discussion', array('DiscussionID' => val('DiscussionID', $Comment)))->firstRow();

        // Did less than 60 seconds elapse between Discussion & Comment insertion?
        $ElapsedSeconds = strtotime(val('DateInserted', $Comment)) - strtotime(val('DateInserted', $Discussion));
        if ($ElapsedSeconds < 60) {
            $UserBadgeModel = new UserBadgeModel();
            $UserBadgeModel->give($Comment->InsertUserID, 'lightning');
        }
    }

    /**
     * Custom badge trigger: 'Name Dropper' (mentioned another user).
     *
     * @param $Sender
     * @param $Args
     */
    public function nameDropper($Sender, $Args) {
        $Mentions = val('MentionedUsers', $Args, array());
        if (count($Mentions)) {
            $Comment = val('Comment', $Args, val('Discussion', $Args));
            $UserBadgeModel = new UserBadgeModel();
            $UserBadgeModel->Give(val('InsertUserID', $Comment), 'name-dropper');
        }
    }

    /**
     * Custom badge trigger: 'Photogenic' (uploaded a profile photo).
     *
     * @param $Sender
     * @param $Args
     */
    public function photogenic($Sender, $Args) {
        $UserID = $Args['UserID'];
        $User = $Args['Fields'];

        $UserBadgeModel = new UserBadgeModel();

        if (val('Photo', $User)) {
            $UserBadgeModel->give($UserID, 'photogenic');
        }
    }

    /**
     * Custom badge trigger: 'Welcome Committee' (comment in user's first discussion).
     *
     * @param $Sender
     * @param $Args
     */
    public function welcomeCommittee($Sender, $Args) {
        // Get current discussion
        $DiscussionModel = new DiscussionModel();
        $DiscussionID = GetValue('DiscussionID', val('FormPostValues', $Args));
        $Discussion = $DiscussionModel->getID($DiscussionID);

        // Is it discussion starter's first?
        $FirstDiscussion = $DiscussionModel->getWhere(array('InsertUserID' => val('InsertUserID', $Discussion)), 'DateInserted', 'asc', 1);
        if ($DiscussionID == GetValue('DiscussionID', $FirstDiscussion)) {
            $UserBadgeModel = new UserBadgeModel();
            $UserBadgeModel->give(Gdn::session()->UserID, 'welcome');
        }
    }
}
