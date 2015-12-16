<?php

/**
 * @copyright 2010-2015 Vanilla Forums, Inc
 * @license Proprietary
 */

/**
 * Hosted Tours Plugin
 *
 * Provides walkthrough tours to newly registered customers of Vanilla to familiarize
 * them with the features available in Vanilla.
 *
 * Changes:
 *  1.0a    Development release
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package internal
 */

$PluginInfo['hostedtours'] = [
    'Name' => 'Hosted Tours',
    'Description' => 'Provides new user walkthrough tours for hosted customers',
    'Version' => '1.0a',
    'MobileFriendly' => false,
    'RequiredApplications' => [
        'Vanilla' => '2.2'
    ],
    'RequiredTheme' => false,
    'RequiredPlugins' => [
        'WalkThrough' => '0.2'
    ],
    'SettingsUrl' => false,
    'Author' => "Tim Gunter",
    'AuthorEmail' => 'tim@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/tim'
];

class HostedToursPlugin extends Gdn_Plugin {

    /**
     * List of pushable tours
     * @var array
     */
    protected static $tours = [
        'welcome' => [
            'name' => 'Welcome Tour',
            'options' => [
                'cssFile' => [
                    'design/welcome/welcome.css',
                    'https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css'
                ],
                'exitOnEsc' => true,
                'showStepNumbers' => false,
                'highlightClass' => 'tooltip-welcome',
                'tooltipPosition' => 'top',
                'showBullets' => false,
                'nextPageLabel' => 'Next &rarr;',
                'prevPageLabel' => '&larr; Back'
            ],
            'steps' => [
                [
                    'page' => '/',
                    'title' => '<i class="fa fa-heart-o"></i> Welcome!',
                    'tooltipClass' => 'tooltip-welcome welcome-intro',
                    'intro' => <<<TOOLTIP
<div class="intro-box">
    <b class="intro-heading">Thanks for signing up!</b>
    <p>Vanilla is a powerful community platform with <i>tons of features</i> to offer.</p>
    <p>We're very keen to make sure your community is a big success, so let's spend a few minutes getting familiar with some of those features, shall we?</p>
</div>
TOOLTIP
,
                ],
                [
                    'page' => '/',
                    'title' => '<i class="fa fa-home"></i> Community Homepage',
                    'tooltipClass' => 'tooltip-welcome welcome-homepage',
                    'intro' => <<<TOOLTIP
<div class="intro-frame">
    <div class="image-homepage"></div>
    <div class="intro-box">
        <b class="intro-heading">This is your homepage</b>
        Your members will land here when they visit your community. It can show recent discussions, a category list, or even your community's "Best Of" page. You can customize the layout in the dashboard later!
    </div>
</div>
TOOLTIP
,
                ],
                [
                    'page' => '/settings',
                    'title' => '<i class="fa fa-cogs"></i> Dashboard',
                    'tooltipClass' => 'tooltip-welcome welcome-dashboard',
                    'intro' => <<<TOOLTIP
<div class="intro-box">
    <b class="intro-heading">This is your dashboard</b>
    <p>Manage all the aspects of your community using the menu on the left. Themes, Plugins, Permissions, and other more advanced settings are all available within the dashboard.<p>
    <p>Getting back here is easy - just click the cog next to your username and choose "Dashboard".</p>
</div>
TOOLTIP
,
                ],
                [
                    'page' => '/settings/themes',
                    'element' => '.Box.Group.Appearance',
                    'position' => 'right',
                    'title' => '<i class="fa fa-picture-o"></i> Appearance',
                    'tooltipClass' => 'tooltip-welcome welcome-appearance',
                    'intro' => <<<TOOLTIP
<div class="intro-box">
    <b class="intro-heading">Make it yours!</b>
    <p>Customize the look and feel of your community in the <i>Appearance</i> section. You can choose a theme and customize it with your own CSS rules, upload banners and icons, and change the title of your site.</p>
    <p>Check out the <a href="https://blog.vanillaforums.com/help/vanilla-custom-themes/" target="_blank">Theming Guide!</a></p>
</div>
TOOLTIP
,
                ],
                [
                    'page' => '/vanilla/settings/managecategories',
                    'element' => '.Box.Group.Forum',
                    'position' => 'right',
                    'title' => '<i class="fa fa-th-list"></i> Categories',
                    'tooltipClass' => 'tooltip-welcome welcome-categories',
                    'intro' => <<<TOOLTIP
<div class="intro-box">
    <b class="intro-heading">Create some structure</b>
    <p><i>Categories</i> are high level discussion containers, and help to add some organization to your community. A car enthusiast community might have categories like "Car Pictures", "Maintenance Advice", "Cars For Sale", and “Reviews”.</p>
    <p>Too many categories can be a bad thing, we suggest starting with 5-10!</p>
    <p>Looking for specifics? Read our blog post about managing <a href="https://blog.vanillaforums.com/help/tips-creating-categories-vanilla-forums/" target="_blank">Categories</a>.</p>
</div>
TOOLTIP
,
                ],
                [
                    'page' => '/dashboard/role',
                    'element' => '.Box.Group.Users',
                    'position' => 'right',
                    'title' => '<i class="fa fa-lock"></i> Roles & Permissions',
                    'tooltipClass' => 'tooltip-welcome welcome-roles',
                    'intro' => <<<TOOLTIP
<div class="intro-box">
    <b class="intro-heading">Prepare for your members</b>
    <p>Vanilla handles access control (what people can see and do) using <i>Roles & Permissions</i>. Each user can have one or more Roles, each of which grant them some permissions. We've created some good starting Roles, but you can change these to suit your own needs.</p>
</div>
<div class="intro-more-box">
    <p>Would you like to know more? We've got a post about <a href="https://blog.vanillaforums.com/news/vanillas-roles-permissions-and-ranks/" target="_blank">Roles & Permissions</a>.</p>
</div>
TOOLTIP
,
                ],
                [
                    'page' => '/dashboard/user',
                    'element' => '.Box.Group.Users',
                    'position' => 'right',
                    'title' => '<i class="fa fa-users"></i> Users',
                    'tooltipClass' => 'tooltip-welcome welcome-users',
                    'intro' => <<<TOOLTIP
<div class="intro-box">
    <b class="intro-heading">Find your members!</b>
    <p>Once your community has launched and people have started to join, you can find and manage their accounts here in the <i>User List</i>.</p>
    <p>You can search by Username, Email, and even Role names.</p>
</div>
<div class="image-users"></div>
TOOLTIP
,
                ],
                [
                    'page' => '/',
                    'title' => '<i class="fa fa-trophy"></i> Congratulations',
                    'tooltipClass' => 'tooltip-welcome welcome-getstarted',
                    'intro' => <<<TOOLTIP
<div class="intro-box">
    <b class="intro-heading">Time to get started!</b>
    <p>Now that you have a basic idea of how your community works, it's a good time to post a discussion and start exploring Vanilla's features for yourself! If you want to take the tour again, just visit the dashboard and click "Take the Tour!" at the top right.</p>
    <p>If you run into problems, just contact our famously helpful support team - <a href="support@vanillaforums.com">support@vanillaforums.com</a></p>
    <p>Good luck!</p>
</div>
TOOLTIP
,
                ]
            ]
        ]
    ];

    /**
     * Push tours
     *
     * @param Gdn_Dispatcher $sender
     */
    public function gdn_dispatcher_appStartup_handler($sender) {
        if (!Gdn::session()->isValid()) {
            return;
        }

        if (Gdn::session()->User->Admin !== 1) {
            return false;
        }

        // Send tour

        $tourKey = 'welcome';
        $tour = HostedToursPlugin::$tours[$tourKey];

        $userID = Gdn::session()->UserID;
        $tourName = val('name', $tour);
        if ($this->walkthrough()->shouldUserSeeTour($userID, $tourName)) {
            $tour = $this->prepareTour($tour);
            $this->walkthrough()->loadTour($tour);
        }
    }

    /**
     * Show 're-take the tour' button
     *
     * @param type $sender
     */
    public function base_beforeUserOptionsMenu_handler($sender) {
        if (!Gdn::session()->isValid()) {
            return;
        }

        if (Gdn::session()->User->Admin !== 1) {
            return false;
        }

        // Show tour button

        $tourKey = 'welcome';
        $tour = HostedToursPlugin::$tours[$tourKey];

        $userID = Gdn::session()->UserID;
        $tourName = val('name', $tour);
        if (!$this->walkthrough()->shouldUserSeeTour($userID, $tourName)) {
            echo anchor(t('Take the Tour!'), url("/plugin/hostedtours/reset/{$tourKey}"), 'reset-tour');
        }
    }

    /**
     * Hook for tour skip
     *
     * @param WalkthroughController $sender
     */
    public function walkthroughController_skipped_handler($sender) {
        $tourName = val('TourName', $sender->EventArguments);
        $tour = self::getTourByName($tourName);
        if (!$tour) {
            return;
        }
        $tourKey = val('key', $tour);

        $this->completion($tourKey, true);
    }

    /**
     * Hook for tour completion
     *
     * @param WalkthroughController $sender
     */
    public function walkthroughController_completed_handler($sender) {
        $tourName = val('TourName', $sender->EventArguments);
        $tour = self::getTourByName($tourName);
        if (!$tour) {
            return;
        }
        $tourKey = val('key', $tour);

        $this->completion($tourKey, false);
    }

    /**
     * Note tour completion
     *
     * @param string $tourKey
     * @param boolean $skipped
     */
    protected function completion($tourKey, $skipped = false) {
        $userName = val('Name', Gdn::session()->User);
        $userEmail = val('Email', Gdn::session()->User);
        $siteName = Infrastructure::site('name');

        $tour = HostedToursPlugin::getTour($tourKey);
        $tourName = val('name', $tour);

        $completionType = $skipped ? 'skipped' : 'completed';
        $completionColor = $skipped ? HipNotify::COLOR_YELLOW : HipNotify::COLOR_GREEN;
        Infrastructure::notify(Infrastructure::ROOM_NOTIFICATIONS, 0)
                ->color($completionColor)
                ->message(sprintf('<b>%s</b> (%s) %s <b>%s</b> on %s', $userName, $userEmail, $completionType, $tourName, $siteName))
                ->send();
    }

    /**
     * Get a tour array
     *
     * @param string $tourKey
     * @return array|false
     */
    public static function getTour($tourKey) {
        return valr($tourKey, self::$tours, false);
    }

    /**
     * Get a tour array by name
     *
     * @param string $tourName
     * @return array|false
     */
    public static function getTourByName($tourName) {
        foreach (self::$tours as $tourKey => $tour) {
            $thisTourName = $tour['name'];
            if ($thisTourName == $tourName) {
                $tour['key'] = $tourKey;
                return $tour;
            }
        }
        return false;
    }

    /**
     * Pre parse a tour to prepare it for WalkThrough
     *
     * @param array $tour
     * @return array
     */
    protected function prepareTour($tour) {

        // Prepare CSS file

        $cssFile = valr('options.cssFile', $tour, false);
        if (!is_array($cssFile)) {
            $cssFile = [$cssFile];
        }
        foreach ($cssFile as &$includeFile) {
            if ($includeFile !== false && !isUrl($includeFile)) {
                $includeFile = asset($this->getWebResource($includeFile), true);
            }
        }
        setvalr('options.cssFile', $tour, $cssFile);

        // Prepare completion steps

        $tour['options'] = array_merge($tour['options'], [
            'onComplete' => url('/plugin/hostedtours/completion/welcome'),
            'onSkip' => url('/plugin/hostedtours/completion/welcome/skipped')
        ]);

        return $tour;
    }

    /**
     * Get Walkthrough instance
     *
     * @staticvar WalkthroughPlugin $instance
     * @return WalkthroughPlugin
     */
    public function walkthrough() {
        static $instance = null;
        if (!($instance instanceof WalkThroughPlugin)) {
            $instance = WalkThroughPlugin::instance();
        }
        return $instance;
    }

}