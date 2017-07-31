<?php
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class AdvancedStatsPlugin extends Gdn_Plugin {
    /// Methods ///

    /**
     * The first date that stats can be considered.
     *
     * @return datetime
     */
    public static function FirstDate() {
        $minDate = '2000-01-01';
        $minTimestamp = strtotime($minDate);

        $firstUserDate = Gdn::SQL()
            ->Select('DateInserted', 'min')
            ->From('User')
            ->Where('DateInserted >', '1976-01-01')
            ->Get()->Value('DateInserted');

        if (Gdn_Format::ToTimestamp($firstUserDate) <= $minTimestamp) {
            return $minDate;
        }

        $firstDiscussionDate = Gdn::SQL()
            ->Select('DateInserted', 'min')
            ->From('Discussion')
            ->Where('DateInserted >', '1976-01-01')
            ->Get()->Value('DateInserted');

        if (Gdn_Format::ToTimestamp($firstDiscussionDate) <= $minTimestamp) {
            return $minDate;
        }

        $firstDate = Gdn_Format::ToDateTime(min(Gdn_Format::ToTimestamp($firstUserDate), Gdn_Format::ToTimestamp($firstDiscussionDate)));

        return $firstDate;
    }

    /**
     * Gets a url suitable to ping the statistics server.
     *
     * @param type $path
     * @param type $params
     * @return string
     */
    public static function StatsUrl($path, $params = []) {
        $analyticsServer = C('Garden.Analytics.Remote', '//analytics.vanillaforums.com');

        $path = '/'.trim($path, '/');

        $timestamp = time();
        $defaultParams = [
            'vid' => Gdn::InstallationID(),
            't' => $timestamp,
            's' => md5($timestamp.Gdn::InstallationSecret())];

        $params = array_merge($defaultParams, $params);

        $result = $analyticsServer.$path.'?'.http_build_query($params);
        return $result;
    }

    /// Event Handlers ///


    /**
     *
     * @param ActivityController $sender
     * @param array $Args
     */
    public function ActivityController_Buzz_Create($sender, $date = FALSE, $slot = 'w') {
        $buzzModel = new BuzzModel();
        $get = array_change_key_case($sender->Request->Get());

        $sender->AddCssFile('buzz.css', 'plugins/vfcom');
        $sender->Data = $buzzModel->Get($slot, $date);

        $sender->SetData('Title', T("What's the Buzz?"));
        $sender->Render('Buzz', 'Activity', 'plugins/AdvancedStats');
    }

    public function UtilityController_Buzz_Create($sender, $date = FALSE, $slot = 'w') {
        $this->ActivityController_Buzz_Create($sender, $date, $slot);
    }

    /**
     * Gets the date range for a slot.
     *
     * @param string $slot One of:
     *  - d: Day
     *  - w: Week
     *  - m: Month
     * @param string|int $date The date or timestamp in the slot.
     * @return array The dates in the form array(From, To).
     */
    public static function SlotDateRange($slot = 'w', $date = FALSE) {
        if (!$date) {
            $timestamp = strtotime(gmdate('Y-m-d'));
        } elseif (is_numeric($date)) {
            $timestamp = strtotime(gmdate('Y-m-d', $date));
        } else {
            $timestamp = strtotime(gmdate('Y-m-d', strtotime($date)));
        }

        $result = NULL;
        switch ($slot) {
            case 'd':
                $result = [Gdn_Format::ToDateTime($timestamp), Gdn_Format::ToDateTime(strtotime('+1 day', $timestamp))];
                break;
            case 'w':
                $sub = gmdate('N', $timestamp) - 1;
                $add = 7 - $sub;
                $result = [Gdn_Format::ToDateTime(strtotime("-$sub days", $timestamp)), Gdn_Format::ToDateTime(strtotime("+$add days", $timestamp))];
                break;
            case 'm':
                $sub = gmdate('j', $timestamp) - 1;
                $timestamp = strtotime("-$sub days", $timestamp);
                $result = [Gdn_Format::ToDateTime($timestamp), Gdn_Format::ToDateTime(strtotime("+1 month", $timestamp))];
                break;
            case 'y':
                $timestamp = strtotime(date('Y-01-01', $timestamp));
                $result = [Gdn_Format::ToDate($timestamp), Gdn_Format::ToDateTime(strtotime("+1 year", $timestamp))];
                break;
        }

        return $result;
    }

    protected static function RangeWhere($range, $fieldName = 'DateInserted') {
        return ["$fieldName >=" => $range[0], "$fieldName <" => $range[1]];
    }

    public function UtilityController_BasicStats_Create($sender, $date = FALSE, $slot = 'w') {
        $slotRange = self::SlotDateRange($slot, $date);

        $result = [
            'SlotType' => $slot,
            'DateFrom' => $slotRange[0],
            'DateTo' => $slotRange[1],
        ];

        $result['CountUsers'] = Gdn::SQL()->GetCount('User', self::RangeWhere($slotRange));
        $result['CountDiscussions'] = Gdn::SQL()->GetCount('Discussion', self::RangeWhere($slotRange));
        $result['CountComments'] = Gdn::SQL()->GetCount('Comment', self::RangeWhere($slotRange));

        $sender->SetData('Stats', $result);
        $sender->Render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * @param DashboardNavModule $nav
     */
    public function dashboardNavModule_init_handler($nav) {
        if (C('Garden.Analytics.Advanced')) {
            // Add stats menu option.
            $nav->addLinkIf('Garden.Settings.Manage', 'Statistics', '/settings/statistics', 'forum-data.statistics');
        }
    }

    /**
     * @param Gdn_Controller $sender
     * @param type $args
     */
    public function Base_Render_Before($sender, $args) {
//      if ($Sender->MasterView != 'admin') {
        $analyticsServer = C('Garden.Analytics.Remote', '//analytics.vanillaforums.com');

//         if ($AnalyticsServer == 'http://analytics.vanillaforums.com') {
//            $Url = "http://autostatic-cl1.vanilladev.com/analytics.vanillaforums.com/applications/vanillastats/js/track.min.js?v=$Version";
//         } else
//            $Url = $AnalyticsServer.'/applications/vanillastats/js/track'.(Debug() ? '' : '.min').'.js?v='.$Version;

        $url = $analyticsServer.'/applications/vanillastatsapp/js/track'.(Debug() ? '' : '.min').'.js?v='.$this->getPluginKey('Version');

        $sender->AddJsFile($url, '', ['defer' => 'defer']);
        $sender->AddDefinition('StatsUrl', self::StatsUrl('{p}'));
//      }

        $statURL = url('/dashboard/settings/statistics');
        if (Gdn_Theme::inSection('Dashboard')
            && checkPermission('Garden.Setting.Manage')
            && Gdn::request()->url() != $statURL) {

            $now = time();
            $expiration = 60 * 60 * 24;
            $lastShown = Gdn::session()->getCookie('-'.__CLASS__.'-notificationtime', 0);
            if ($now - $lastShown > $expiration) {
                $sender->informMessage(
                    sprintf(t('<a href="%s">The Advanced Stats addon will be removed on June 1, 2017.</a>'), $statURL)
                );
                Gdn::session()->setCookie('-'.__CLASS__.'-notificationtime', $now, $expiration);
            }

        }

    }

    /**
     * Creates an analytics page to load remote analytics data.
     */
    public function SettingsController_Statistics_Create($sender) {
        $sender->Permission('Garden.Settings.Manage');
        $sender->Title('Site Statistics');
        $sender->AddSideMenu('settings/statistics');
        $sender->Render('stats', '', 'plugins/AdvancedStats');
    }

    /**
     * @param UserModel $sender
     * @param array $args
     */
    public function UserModel_BeforeInsertUser_Handler($sender, $args) {
        // Check for the tracker cookie and save that with the user.
        $trackerCookie = GetValue('__vna', $_COOKIE);
        if ($trackerCookie) {
            $parts = explode('.', $trackerCookie);
            $dateFirstVisit = Gdn_Format::ToDateTime($parts[0]);
            $signedIn = GetValue(2, $parts);
            if (!$signedIn) {
                $args['InsertFields']['DateFirstVisit'] = $dateFirstVisit;
            }
        }
    }

    public function UtilityController_Ping_Create($sender) {
        $sender->SetData('VanillaID', Gdn::InstallationID());
        $sender->SetData('DateFirstStats', self::FirstDate());
        $sender->Render();
    }

    public function setup() {
        // Disallow enabling.
        throw new Gdn_UserException('Deprecated. Use VanillaAnalytics instead.');
    }

    /**
     * {@inheritdoc}
     */
    public function structure() {
        Gdn::pluginManager()->disablePlugin('AdvancedStats');
        saveToConfig('EnabledPlugins.AdvancedStats', null, ['RemoveEmpty' => true]);
    }
}
