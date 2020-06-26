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
    public static function firstDate() {
        $minDate = '2000-01-01';
        $minTimestamp = strtotime($minDate);

        $firstUserDate = Gdn::sql()
            ->select('DateInserted', 'min')
            ->from('User')
            ->where('DateInserted >', '1976-01-01')
            ->get()->value('DateInserted');

        if (Gdn_Format::toTimestamp($firstUserDate) <= $minTimestamp) {
            return $minDate;
        }

        $firstDiscussionDate = Gdn::sql()
            ->select('DateInserted', 'min')
            ->from('Discussion')
            ->where('DateInserted >', '1976-01-01')
            ->get()->value('DateInserted');

        if (Gdn_Format::toTimestamp($firstDiscussionDate) <= $minTimestamp) {
            return $minDate;
        }

        $firstDate = Gdn_Format::toDateTime(min(Gdn_Format::toTimestamp($firstUserDate), Gdn_Format::toTimestamp($firstDiscussionDate)));

        return $firstDate;
    }

    /**
     * Gets a url suitable to ping the statistics server.
     *
     * @param type $path
     * @param type $params
     * @return string
     */
    public static function statsUrl($path, $params = []) {
        $analyticsServer = c('Garden.Analytics.Remote', '//analytics.vanillaforums.com');

        $path = '/'.trim($path, '/');

        $timestamp = time();
        $defaultParams = [
            'vid' => Gdn::installationID(),
            't' => $timestamp,
            's' => md5($timestamp.Gdn::installationSecret())];

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
    public function activityController_buzz_create($sender, $date = FALSE, $slot = 'w') {
        $buzzModel = new BuzzModel();
        $get = array_change_key_case($sender->Request->get());

        $sender->addCssFile('buzz.css', 'plugins/vfcom');
        $sender->Data = $buzzModel->get($slot, $date);

        $sender->setData('Title', t("What's the Buzz?"));
        $sender->render('Buzz', 'Activity', 'plugins/AdvancedStats');
    }

    public function utilityController_buzz_create($sender, $date = FALSE, $slot = 'w') {
        $this->activityController_buzz_create($sender, $date, $slot);
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
    public static function slotDateRange($slot = 'w', $date = FALSE) {
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
                $result = [Gdn_Format::toDateTime($timestamp), Gdn_Format::toDateTime(strtotime('+1 day', $timestamp))];
                break;
            case 'w':
                $sub = gmdate('N', $timestamp) - 1;
                $add = 7 - $sub;
                $result = [Gdn_Format::toDateTime(strtotime("-$sub days", $timestamp)), Gdn_Format::toDateTime(strtotime("+$add days", $timestamp))];
                break;
            case 'm':
                $sub = gmdate('j', $timestamp) - 1;
                $timestamp = strtotime("-$sub days", $timestamp);
                $result = [Gdn_Format::toDateTime($timestamp), Gdn_Format::toDateTime(strtotime("+1 month", $timestamp))];
                break;
            case 'y':
                $timestamp = strtotime(date('Y-01-01', $timestamp));
                $result = [Gdn_Format::toDate($timestamp), Gdn_Format::toDateTime(strtotime("+1 year", $timestamp))];
                break;
        }

        return $result;
    }

    protected static function rangeWhere($range, $fieldName = 'DateInserted') {
        return ["$fieldName >=" => $range[0], "$fieldName <" => $range[1]];
    }

    public function utilityController_basicStats_create($sender, $date = FALSE, $slot = 'w') {
        $slotRange = self::slotDateRange($slot, $date);

        $result = [
            'SlotType' => $slot,
            'DateFrom' => $slotRange[0],
            'DateTo' => $slotRange[1],
        ];

        $result['CountUsers'] = Gdn::sql()->getCount('User', self::rangeWhere($slotRange));
        $result['CountDiscussions'] = Gdn::sql()->getCount('Discussion', self::rangeWhere($slotRange));
        $result['CountComments'] = Gdn::sql()->getCount('Comment', self::rangeWhere($slotRange));

        $sender->setData('Stats', $result);
        $sender->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * @param DashboardNavModule $nav
     */
    public function dashboardNavModule_init_handler($nav) {
        if (c('Garden.Analytics.Advanced')) {
            // Add stats menu option.
            $nav->addLinkIf('Garden.Settings.Manage', 'Statistics', '/settings/statistics', 'forum-data.statistics');
        }
    }

    /**
     * @param Gdn_Controller $sender
     * @param type $args
     */
    public function base_render_before($sender, $args) {
//      if ($Sender->MasterView != 'admin') {
        $analyticsServer = c('Garden.Analytics.Remote', '//analytics.vanillaforums.com');

//         if ($AnalyticsServer == 'http://analytics.vanillaforums.com') {
//            $Url = "http://autostatic-cl1.vanilladev.com/analytics.vanillaforums.com/applications/vanillastats/js/track.min.js?v=$Version";
//         } else
//            $Url = $AnalyticsServer.'/applications/vanillastats/js/track'.(debug() ? '' : '.min').'.js?v='.$Version;

        $url = $analyticsServer.'/applications/vanillastatsapp/js/track'.(debug() ? '' : '.min').'.js?v='.$this->getPluginKey('Version');

        $sender->addJsFile($url, '', ['defer' => 'defer']);
        $sender->addDefinition('StatsUrl', self::statsUrl('{p}'));
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
    public function settingsController_statistics_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->title('Site Statistics');
        $sender->addSideMenu('settings/statistics');
        $sender->render('stats', '', 'plugins/AdvancedStats');
    }

    /**
     * @param UserModel $sender
     * @param array $args
     */
    public function userModel_beforeInsertUser_handler($sender, $args) {
        // Check for the tracker cookie and save that with the user.
        $trackerCookie = getValue('__vna', $_COOKIE);
        if ($trackerCookie) {
            $parts = explode('.', $trackerCookie);
            $dateFirstVisit = Gdn_Format::toDateTime($parts[0]);
            $signedIn = getValue(2, $parts);
            if (!$signedIn) {
                $args['InsertFields']['DateFirstVisit'] = $dateFirstVisit;
            }
        }
    }

    public function utilityController_ping_create($sender) {
        $sender->setData('VanillaID', Gdn::installationID());
        $sender->setData('DateFirstStats', self::firstDate());
        $sender->render();
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
