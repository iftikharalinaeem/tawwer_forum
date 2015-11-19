<?php
/**
 * Places badges hooks into other applications.
 *
 * @package Badges
 */
$PluginInfo['badges'] = array(
    'Name' => 'Badges',
    'Description' => "Give badges to your users to reward them for contributing to your community.",
    'Version' => '1.4',
    'Author' => "Lincoln Russell",
    'AuthorEmail' => 'lincoln@vanillaforums.com',
    'AuthorUrl' => 'http://lincolnwebs.com',
    'License' => 'GNU GPL2'
);

/**
 * Class BadgesHooks
 */
class BadgesHooks extends Gdn_Plugin {

    public function MultisiteModel_SyncNodes_Handler($sender, $args) {
        $args['urls'][] = '/badges/syncnode.json';
    }

    /**
     * Add mapper methods
     *
     * @param SimpleApiPlugin $Sender
     */
    public function SimpleApiPlugin_Mapper_Handler($Sender) {
    switch ($Sender->Mapper->Version) {
        case '1.0':
            $Sender->Mapper->AddMap(array(
                'badges/give'              => 'badge/giveuser',
                'badges/revoke'            => 'badge/revoke',
                'badges/user'              => 'badges/user',
                'badges/list'              => 'badges/all',
                'badges/get'                => 'badge',
                'badges/add'                => 'badge/manage',
                'badges/edit'              => 'badge/manage',
                'badges/delete'            => 'badge/delete',
            ));
            break;
        }
    }

    public function AssetModel_StyleCss_Handler($Sender, $Args) {
        $Sender->AddCssFile('badges.css', 'plugin/badges');
    }

    public function Base_AfterConnection_Handler($Sender, $Args) {
        $BadgeModel = new BadgeModel();
        $UserBadgeModel = new UserBadgeModel();

        $Badges = $BadgeModel->GetByType('Connect');

        foreach ($Badges as $Badge) {
            if ($Badge['Attributes']['Provider'] == $Args['Provider']) {
            $UserBadgeModel->Give(GetValueR('User.UserID', $Args), $Badge);
            break;
            }
        }
    }

    /**
     * Delete & log UserPoints when a user is deleted.
     */
    public function Base_BeforeDeleteUser_Handler($Sender, $Args) {
        Gdn::UserModel()->GetDelete('UserPoints', array('UserID' => $Args['UserID']), $Args['Content']);
    }

    /**
     * Adds items to Dashboard menu.
     *
     * @since 1.0.0
     * @param object $Sender DashboardController.
     */
    public function Base_GetAppSettingsMenuItems_Handler($Sender) {
        $Menu = &$Sender->EventArguments['SideMenu'];
        $Menu->AddLink('Reputation', T('Badges'), '/badge/all', 'Garden.Settings.Manage', array('class' => 'nav-badges'));
        $Menu->AddLink('Reputation', T('Badge Requests'), '/badge/requests', 'Reputation.Badges.Give', array('class' => 'nav-badge-requests'));
    }

//    public function CommentModel_AfterSaveComment_Handler($Sender, $Args) {
//        $this->DiscussionContent($Sender, $Args);
//        $this->CommentMarathon($Sender, $Args);
//        $this->LightningReflexes($Sender, $Args);
//        $this->WelcomeCommittee($Sender, $Args);
//    }

    public function CommentModel_BeforeNotification_Handler($Sender, $Args) {
        $this->NameDropper($Sender, $Args);
    }

//    public function DiscussionModel_AfterSaveDiscussion_Handler($Sender, $Args) {
//        $this->DiscussionContent($Sender, $Args);
//    }

    public function DiscussionModel_BeforeNotification_Handler($Sender, $Args) {
        $this->NameDropper($Sender, $Args);
    }

    /**
     * Calculate whether badges are awarded when a user visits.
     */
//    public function Gdn_Session_AfterGetSession_Handler($Sender, $Args) {
//        $this->FreshStart($Sender, $Args);
//        $this->Anniversaries($Sender, $Args);
//        $this->Attendance($Sender, $Args);
//        $this->EarlyMorningTreat($Sender, $Args);
//    }

    /**
     * Add options to profile menu.
     *
     * @since 1.0.0
     * @access public
     */
    public function Base_BeforeProfileOptions_Handler($Sender, &$Args) {
        $SideMenu = GetValue('SideMenu', $Args);

        // Add 'Give Badge' to profiles
        if (CheckPermission('Reputation.Badges.Give')) {
            $Args['ProfileOptions'][] = array('Text' => T('Give Badge'), 'Url' => '/badge/giveuser/'.$Args['UserID'].'/', 'CssClass' => 'Popup');
        }
    }

    /**
     * Adds email notification options to profiles.
     *
     * @since 1.0.0
     * @access public
     * @param object $Sender ProfileController.
     */
    public function ProfileController_AfterPreferencesDefined_Handler($Sender) {
        $Sender->Preferences['Notifications']['Email.Badge'] = T('PreferenceBadgeEmail', 'Notify me when I earn a badge.');
        $Sender->Preferences['Notifications']['Popup.Badge'] = T('PreferenceBadgePopup', 'Notify me when I earn a badge.');

        if (Gdn::Session()->CheckPermission('Reputation.Badges.Give')) {
            $Sender->Preferences['Notifications']['Email.BadgeRequest'] = T('Notify me when a badge is requested.');
            $Sender->Preferences['Notifications']['Popup.BadgeRequest'] = T('Notify me when a badge is requested.');

            // Save to list of users for notifications
            if ($Sender->Form->IsPostBack()) {
                $Set = array();
                $Prefixes = array('Email.BadgeRequest', 'Popup.BadgeRequest');
                foreach ($Prefixes as $Prefix) {
                    $Value = $Sender->Form->GetFormValue($Prefix, null);
                    $Set[$Prefix] = ($Value) ? $Value : null;
                }
                UserModel::SetMeta($Sender->User->UserID, $Set, 'Preferences.');
            }
        }
    }

    /**
     * Show user's badges in profile.
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
        if (C('Badges.BadgesModule.Target', 'Panel') == 'Panel') {
            $Sender->AddModule('BadgesModule');
        }
    }

    /**
     * Add 'Badges' tab & module to profiles.
     *
     * @access public
     */
    public function Base_AfterUserInfo_Handler($Sender, $Args) {
        if (C('Badges.BadgesModule.Target') == 'AfterUserInfo') {
            echo Gdn_Theme::Module('BadgesModule');
        }
    }

    /**
     * Add 'Badges' tab & module to profiles.
     *
     * @access public
     */
    public function Base_BeforeUserInfo_Handler($Sender, $Args) {
        if (C('Badges.BadgesModule.Target') == 'BeforeUserInfo') {
            echo Gdn_Theme::Module('BadgesModule');
        }
    }

    /**
     *
     * @param UserModel $Sender
     */
    public function UserModel_Visit_Handler($Sender, $Args) {
        $this->Anniversaries($Sender, $Args);
    }

    /**
     * Calculate whether any badges should be given... because of badges given.
     */
    public function UserBadgeModel_AfterGive_Handler($Sender, $Args) {
        $this->ComboBreaker($Sender, $Args);
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
    public function UserInfoModule_OnBasicInfo_Handler($Sender) {
        echo ' '.Wrap(T('Badges'), 'dt', array('class' => 'Badges'));
        echo ' '.Wrap(GetValueR('User.CountBadges', $Sender, 0), 'dd', array('class' => 'Badges'));
    }

    /**
     * Calculate whether badges should be awarded after user is saved.
     */
    public function UserModel_AfterSave_Handler($Sender, $Args) {
        $this->Photogenic($Sender, $Args);
//        $Fields = $Args['Fields'];
//        if (isset($Fields['DateLastActive'])) {
//            $this->Anniversaries($Sender, $Args);
//        }
    }

    /**
     * Calculate whether badges should be awarded after user is updated.
     */
    public function UserModel_AfterSetField_Handler($Sender, $Args) {
        $UserID = $Args['UserID'];
        $Fields = $Args['Fields'];

        if (isset($Fields['CountComments']) || isset($Fields['CountPosts'])) {
            $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
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

        $UserCountBadges = $BadgeModel->GetByType('UserCount');
        foreach ($UserCountBadges as $Badge) {
            if (GetValue('Attributes', $Badge)) {
                $Column = $Badge['Attributes']['Column'];
                if (isset($Counts[$Column]) && $Counts[$Column] >= $Badge['Threshold']) {
                    $UserBadgeModel->Give($UserID, $Badge);
                }
            }
        }
    }

    /**
     * Dole out points for likes.
     *
     * @param VanillaLabsPlugin $Sender
     * @param array $Args
     */
    public function VanillaLabsPlugin_IncrementUser_Handler($Sender, $Args) {
        $Column = $Args['Column'];
        $Inc = $Args['Inc'];
        if ($Column != 'Likes' || $Inc == 0) {
            return;
        }

        $UserID = $Args['UserID'];

        $Points = $Inc > 0 ? 2 : -2;
        UserBadgeModel::GivePoints($UserID, $Points, 'Reactions');
    }

    /**
     * Run structure & default badges.
     */
    public function Setup() {
        Gdn::applicationManager()->disableApplication('reputation');
        require_once(dirname(__FILE__).'/structure.php');
    }

    /**
     *
     * @param ActivityController $Sender
     * @param type $Args
     */
    public function ActivityController_Render_Before($Sender, $Args) {
        if ($Sender->DeliveryMethod() == DELIVERY_METHOD_XHTML) {
            $Sender->AddModule('LeaderBoardModule');

            $Module = new LeaderBoardModule();
            $Module->SlotType = 'a';
            $Sender->AddModule($Module);
        }
    }

    /**
     * Custom badge trigger: All 'Anniversary'-class badges (X years of membership).
     */
    public function Anniversaries($Sender, $Args) {
        if (Gdn::Session()->IsValid()) {
            $UserBadgeModel = new UserBadgeModel();
            $FirstVisit = GetValue('DateFirstVisit', Gdn::Session()->User);
//            decho($FirstVisit, 'DateFirstVisit');
            if (!$FirstVisit) {
                return;
            }
            $FirstVisit = Gdn_Format::ToTimestamp($FirstVisit);
            $Today = time();
            $UserID = GetValue('UserID', Gdn::Session()->User);

            // Give most recent anniversary badge they've earned
            for ($i = 11; $i >= 1; $i--) {
                $BadgeTime = strtotime("+$i years", $FirstVisit);
//                decho(Gdn_Format::ToDateTime($BadgeTime), 'BadgeTime');
//                decho(Gdn_Format::ToDateTime($Today), 'Today');
                if ($BadgeTime <= $Today) {
                    $Suffix = ($i > 10) ? '-old' : (($i > 1) ? '-'.$i : '');
//                    decho($Suffix, 'anniversary');
                    $UserBadgeModel->Give($UserID, 'anniversary'.$Suffix);
                }
            }
        }
    }

    /**
     * Badge type trigger: 'Attendance' (X consecutive day visits).
     *
     * @todo Finish this trigger.
     */
    public function Attendance($Sender, $Args) {
        $BadgeModel = new BadgeModel();
        $UserCountBadges = $BadgeModel->GetByType('Attendance');
        foreach ($UserCountBadges as $Badge) {
            // Skip completed
            if (GetValue('DateCompleted', $Badge)) {
                continue;
            }

            // Log progress
            $Attributes = GetValue('Attributes', $Badge);
            if (false) {
                $UserBadgeModel->Give($UserID, $Badge);
            }
        }
    }

//    public function Base_AfterUserInfo_Handler($Sender, $Args) {
//        // Fetch the view helper functions.
//        include_once Gdn::Controller()->FetchViewLocation('reputation_functions', '', 'Reputation');
//
//        echo '<h2>'.T('Points').'</h2>';
//        WriteProfilePoints();
//    }

    /**
     * Custom badge trigger: 'Combo Breaker' (5 badges in 1 day).
     */
    public function ComboBreaker($Sender, $Args) {
        // Get badge given
        $UserBadge = GetValue('UserBadge', $Args);

        // Register timeout event
        $UserBadgeModel = new UserBadgeModel();
        $EventCount = $UserBadgeModel->AddTimeoutEvent(GetValue('UserID', $UserBadge), 'combo', GetValue('DateCompleted', $UserBadge));
    }

    /**
     * Custom badge trigger: 'Comment Marathon' (42 comments 1 day).
     */
//    public function CommentMarathon($Sender, $Args) {
//        // Get comment
//        $CommentModel = new CommentModel();
//        $Comment = $CommentModel->GetID(GetValue('CommentID', $Args));
//
//        // Register timeout event
//        $UserBadgeModel = new UserBadgeModel();
//        $EventCount = $UserBadgeModel->AddTimeoutEvent(GetValue('InsertUserID', $Comment), 'marathon', GetValue('DateInserted', $Comment));
//    }

    /**
     * Badge type trigger: 'DiscussionContent' (pattern matching in Body of Discussions & Comments).
     */
    public function DiscussionContent($Sender, $Args) {
        $BadgeModel = new BadgeModel();
        $UserBadgeModel = new UserBadgeModel();
        $Body = GetValue('Body', GetValue('FormPostValues', $Args));

        $UserCountBadges = $BadgeModel->GetByType('DiscussionContent');
        foreach ($UserCountBadges as $Badge) {
            if ($Attributes = GetValue('Attributes', $Badge)) {
                $Pattern = GetValue('Pattern', $Attributes);
                if (preg_match($Pattern, $Body)) {
                    $UserBadgeModel->Give($UserID, $Badge);
                }
            }
        }
    }

    /**
     * Custom badge trigger: 'Morning Treat' (visited 4am-6am local).
     */
    public function MorningTreat($Sender, $Args) {
        if (Gdn::Session()->IsValid()) {
            $Offset = GetValue('HourOffset', Gdn::Session()->User);
            $Hour = date('G') + $Offset;
            if ($Hour == 4 || $Hour == 5) {
                $UserBadgeModel = new UserBadgeModel();
                $UserBadgeModel->Give(GetValue('UserID', Gdn::Session()), 'morning');
            }
        }
    }

    /**
     * Custom badge trigger: 'Fresh Start' (visited Jan 1).
     */
    public function FreshStart($Sender, $Args) {
        if (date('j') === '1' && date('n') === '1') {
            $User = GetValue('User', $Args);
            $UserBadgeModel = new UserBadgeModel();
            $UserBadgeModel->Give($User->UserID, 'fresh-start');
        }
    }

    /**
     * Custom badge trigger: 'Lightning Reflexes' (comment within 1 min of discussion start).
     */
    public function LightningReflexes($Sender, $Args) {
        // Get Comment & its Discussion
        $Comment = $Sender->SQL->GetWhere('Comment', array('CommentID' => GetValue('CommentID', $Args)))->FirstRow();
        $Discussion = $Sender->SQL->GetWhere('Discussion', array('DiscussionID' => GetValue('DiscussionID', $Comment)))->FirstRow();

        // Did less than 60 seconds elapse between Discussion & Comment insertion?
        $ElapsedSeconds = strtotime(GetValue('DateInserted', $Comment)) - strtotime(GetValue('DateInserted', $Discussion));
        if ($ElapsedSeconds < 60) {
            $UserBadgeModel = new UserBadgeModel();
            $UserBadgeModel->Give($Comment->InsertUserID, 'lightning');
        }
    }

    /**
     * Custom badge trigger: 'Name Dropper' (mentioned another user).
     */
    public function NameDropper($Sender, $Args) {
        $Mentions = GetValue('MentionedUsers', $Args, array());
        if (count($Mentions)) {
            $Comment = GetValue('Comment', $Args, GetValue('Discussion', $Args));
            $UserBadgeModel = new UserBadgeModel();
            $UserBadgeModel->Give(GetValue('InsertUserID', $Comment), 'name-dropper');
        }
    }

    /**
     * Custom badge trigger: 'Photogenic' (uploaded a profile photo).
     */
    public function Photogenic($Sender, $Args) {
        $UserID = $Args['UserID'];
        $User = $Args['Fields'];

        $UserBadgeModel = new UserBadgeModel();

        if (GetValue('Photo', $User)) {
            $UserBadgeModel->Give($UserID, 'photogenic');
        }
    }

    /**
     * Custom badge trigger: 'Welcome Committee' (comment in user's first discussion).
     */
    public function WelcomeCommittee($Sender, $Args) {
        // Get current discussion
        $DiscussionModel = new DiscussionModel();
        $DiscussionID = GetValue('DiscussionID', GetValue('FormPostValues', $Args));
        $Discussion = $DiscussionModel->GetID($DiscussionID);

        // Is it discussion starter's first?
        $FirstDiscussion = $DiscussionModel->GetWhere(array('InsertUserID' =>GetValue('InsertUserID', $Discussion)), 'DateInserted', 'asc', 1);
        if ($DiscussionID == GetValue('DiscussionID', $FirstDiscussion)) {
            $UserBadgeModel = new UserBadgeModel();
            $UserBadgeModel->Give(Gdn::Session()->UserID, 'welcome');
        }
    }
}
