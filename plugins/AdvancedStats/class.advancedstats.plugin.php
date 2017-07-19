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
        $MinDate = '2000-01-01';
        $MinTimestamp = strtotime($MinDate);

        $FirstUserDate = Gdn::SQL()
            ->Select('DateInserted', 'min')
            ->From('User')
            ->Where('DateInserted >', '1976-01-01')
            ->Get()->Value('DateInserted');

        if (Gdn_Format::ToTimestamp($FirstUserDate) <= $MinTimestamp) {
            return $MinDate;
        }

        $FirstDiscussionDate = Gdn::SQL()
            ->Select('DateInserted', 'min')
            ->From('Discussion')
            ->Where('DateInserted >', '1976-01-01')
            ->Get()->Value('DateInserted');

        if (Gdn_Format::ToTimestamp($FirstDiscussionDate) <= $MinTimestamp) {
            return $MinDate;
        }

        $FirstDate = Gdn_Format::ToDateTime(min(Gdn_Format::ToTimestamp($FirstUserDate), Gdn_Format::ToTimestamp($FirstDiscussionDate)));

        return $FirstDate;
    }

    /**
     * Gets a url suitable to ping the statistics server.
     *
     * @param type $Path
     * @param type $Params
     * @return string
     */
    public static function StatsUrl($Path, $Params = []) {
        $AnalyticsServer = C('Garden.Analytics.Remote', '//analytics.vanillaforums.com');

        $Path = '/'.trim($Path, '/');

        $Timestamp = time();
        $DefaultParams = [
            'vid' => Gdn::InstallationID(),
            't' => $Timestamp,
            's' => md5($Timestamp.Gdn::InstallationSecret())];

        $Params = array_merge($DefaultParams, $Params);

        $Result = $AnalyticsServer.$Path.'?'.http_build_query($Params);
        return $Result;
    }

    /// Event Handlers ///


    /**
     *
     * @param ActivityController $Sender
     * @param array $Args
     */
    public function ActivityController_Buzz_Create($Sender, $Date = FALSE, $Slot = 'w') {
        $BuzzModel = new BuzzModel();
        $Get = array_change_key_case($Sender->Request->Get());

        $Sender->AddCssFile('buzz.css', 'plugins/vfcom');
        $Sender->Data = $BuzzModel->Get($Slot, $Date);

        $Sender->SetData('Title', T("What's the Buzz?"));
        $Sender->Render('Buzz', 'Activity', 'plugins/AdvancedStats');
    }

    public function UtilityController_Buzz_Create($Sender, $Date = FALSE, $Slot = 'w') {
        $this->ActivityController_Buzz_Create($Sender, $Date, $Slot);
    }

    /**
     * Gets the date range for a slot.
     *
     * @param string $Slot One of:
     *  - d: Day
     *  - w: Week
     *  - m: Month
     * @param string|int $Date The date or timestamp in the slot.
     * @return array The dates in the form array(From, To).
     */
    public static function SlotDateRange($Slot = 'w', $Date = FALSE) {
        if (!$Date) {
            $Timestamp = strtotime(gmdate('Y-m-d'));
        } elseif (is_numeric($Date)) {
            $Timestamp = strtotime(gmdate('Y-m-d', $Date));
        } else {
            $Timestamp = strtotime(gmdate('Y-m-d', strtotime($Date)));
        }

        $Result = NULL;
        switch ($Slot) {
            case 'd':
                $Result = [Gdn_Format::ToDateTime($Timestamp), Gdn_Format::ToDateTime(strtotime('+1 day', $Timestamp))];
                break;
            case 'w':
                $Sub = gmdate('N', $Timestamp) - 1;
                $Add = 7 - $Sub;
                $Result = [Gdn_Format::ToDateTime(strtotime("-$Sub days", $Timestamp)), Gdn_Format::ToDateTime(strtotime("+$Add days", $Timestamp))];
                break;
            case 'm':
                $Sub = gmdate('j', $Timestamp) - 1;
                $Timestamp = strtotime("-$Sub days", $Timestamp);
                $Result = [Gdn_Format::ToDateTime($Timestamp), Gdn_Format::ToDateTime(strtotime("+1 month", $Timestamp))];
                break;
            case 'y':
                $Timestamp = strtotime(date('Y-01-01', $Timestamp));
                $Result = [Gdn_Format::ToDate($Timestamp), Gdn_Format::ToDateTime(strtotime("+1 year", $Timestamp))];
                break;
        }

        return $Result;
    }

    protected static function RangeWhere($Range, $FieldName = 'DateInserted') {
        return ["$FieldName >=" => $Range[0], "$FieldName <" => $Range[1]];
    }

    public function UtilityController_BasicStats_Create($Sender, $Date = FALSE, $Slot = 'w') {
        $SlotRange = self::SlotDateRange($Slot, $Date);

        $Result = [
            'SlotType' => $Slot,
            'DateFrom' => $SlotRange[0],
            'DateTo' => $SlotRange[1],
        ];

        $Result['CountUsers'] = Gdn::SQL()->GetCount('User', self::RangeWhere($SlotRange));
        $Result['CountDiscussions'] = Gdn::SQL()->GetCount('Discussion', self::RangeWhere($SlotRange));
        $Result['CountComments'] = Gdn::SQL()->GetCount('Comment', self::RangeWhere($SlotRange));

        $Sender->SetData('Stats', $Result);
        $Sender->Render('Blank', 'Utility', 'Dashboard');
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
     * @param Gdn_Controller $Sender
     * @param type $Args
     */
    public function Base_Render_Before($Sender, $Args) {
//      if ($Sender->MasterView != 'admin') {
        $AnalyticsServer = C('Garden.Analytics.Remote', '//analytics.vanillaforums.com');

//         if ($AnalyticsServer == 'http://analytics.vanillaforums.com') {
//            $Url = "http://autostatic-cl1.vanilladev.com/analytics.vanillaforums.com/applications/vanillastats/js/track.min.js?v=$Version";
//         } else
//            $Url = $AnalyticsServer.'/applications/vanillastats/js/track'.(Debug() ? '' : '.min').'.js?v='.$Version;

        $Url = $AnalyticsServer.'/applications/vanillastatsapp/js/track'.(Debug() ? '' : '.min').'.js?v='.$this->getPluginKey('Version');

        $Sender->AddJsFile($Url, '', ['defer' => 'defer']);
        $Sender->AddDefinition('StatsUrl', self::StatsUrl('{p}'));
//      }

        $statURL = url('/dashboard/settings/statistics');
        if (Gdn_Theme::inSection('Dashboard')
            && checkPermission('Garden.Setting.Manage')
            && Gdn::request()->url() != $statURL) {

            $now = time();
            $expiration = 60 * 60 * 24;
            $lastShown = Gdn::session()->getCookie('-'.__CLASS__.'-notificationtime', 0);
            if ($now - $lastShown > $expiration) {
                $Sender->informMessage(
                    sprintf(t('<a href="%s">The Advanced Stats addon will be removed on June 1, 2017.</a>'), $statURL)
                );
                Gdn::session()->setCookie('-'.__CLASS__.'-notificationtime', $now, $expiration);
            }

        }

    }

    /**
     * Creates an analytics page to load remote analytics data.
     */
    public function SettingsController_Statistics_Create($Sender) {
        $Sender->Permission('Garden.Settings.Manage');
        $Sender->Title('Site Statistics');
        $Sender->AddSideMenu('settings/statistics');
        $Sender->Render('stats', '', 'plugins/AdvancedStats');
    }

    /**
     * @param UserModel $Sender
     * @param array $Args
     */
    public function UserModel_BeforeInsertUser_Handler($Sender, $Args) {
        // Check for the tracker cookie and save that with the user.
        $TrackerCookie = GetValue('__vna', $_COOKIE);
        if ($TrackerCookie) {
            $Parts = explode('.', $TrackerCookie);
            $DateFirstVisit = Gdn_Format::ToDateTime($Parts[0]);
            $SignedIn = GetValue(2, $Parts);
            if (!$SignedIn) {
                $Args['InsertFields']['DateFirstVisit'] = $DateFirstVisit;
            }
        }
    }

    public function UtilityController_Ping_Create($Sender) {
        $Sender->SetData('VanillaID', Gdn::InstallationID());
        $Sender->SetData('DateFirstStats', self::FirstDate());
        $Sender->Render();
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
