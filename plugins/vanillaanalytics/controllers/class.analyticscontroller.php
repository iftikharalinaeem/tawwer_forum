<?php

class AnalyticsController extends DashboardController {

    /**
     * Restrict access to that controller
     */
    public function initialize() {
        $this->permission('Garden.Settings.Manage');

        parent::initialize();
    }

    /**
     * Save a widget to the current user's personal dashboard.
     *
     * @param string $widgetID
     */
    public function bookmarkWidget($widgetID) {
        $dashboardModel = new AnalyticsDashboard();
        $widgetModel = new AnalyticsWidget();
        $widget = $widgetModel->getID($widgetID);
        $userID = Gdn::session()->UserID;

        if ($widget) {
            if ($widget->isBookmarked()) {
                $dashboardModel->removeWidget(
                    $widgetID,
                    AnalyticsDashboard::DASHBOARD_PERSONAL,
                    $userID
                );
                $bookmarked = false;

                $this->jsonTarget(
                    "#analytics_widget_{$widgetID}",
                    'removeAnalyticsWidget',
                    'Callback'
                );
                $this->informMessage(t('Unpinned widget'));
            } else {
                $dashboardModel->addWidget(
                    $widgetID,
                    AnalyticsDashboard::DASHBOARD_PERSONAL,
                    $userID
                );
                $bookmarked = true;

                $this->informMessage(t('Pinned widget'));
            }

            $html = anchor(
                dashboardSymbol('pin'),
                "/analytics/bookmarkwidget/{$widgetID}",
                'Hijack bookmark'.($bookmarked ? ' bookmarked' : '')
//                array('title' => $widget->getTitle())
            );
            $this->jsonTarget('!element', $html, 'ReplaceWith');
        } else {
            $this->informMessage(t('Invalid widget ID'));
        }

        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        $this->deliveryTYpe(DELIVERY_TYPE_MESSAGE);
        $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Handle requests for the analytics index in Vanilla's dashboard.
     *
     */
    public function index() {
        redirect('/analytics/dashboard/traffic');
    }

    /**
     * Generate data for leaderboards.
     */
    public function leaderboard($widget) {
        if (empty($widget)) {
            throw new Gdn_UserException('Leaderboard widget required.');
        }

        // Verify the slug is a valid leaderboard widget.
        $defaultWidgets = AnalyticsTracker::getInstance()->getDefaultWidgets();
        $widget = val($widget, $defaultWidgets);
        if (!$widget || $widget->getType() !== 'leaderboard') {
            throw new Gdn_UserException('Invalid leaderboard widget.');
        }
        $leaderboard = new AnalyticsLeaderboard();

        $data = $widget->getData();
        $size = val('size', $data);
        if ($size) {
            $leaderboard->setSize($size);
        }

        // Verify we have a query to run.
        $this->title($widget->getTitle());
        $query = val('query', $widget->getData());
        if (!$query) {
            throw new Gdn_UserException('No query available.');
        }
        $leaderboard->setQuery($query);
        $leaderboard->setPreviousQuery(clone $query);
        $data = $leaderboard->lookupData(
            strtotime(Gdn::request()->get('Start')),
            strtotime(Gdn::request()->get('End'))
        );

        $this->setData('Leaderboard', $data);
        $this->setData('Labels', val('labels', val('chart', $widget->getData())));
        $this->render('leaderboard');
    }

    /**
     * Handle requests for an analytics dashboard.
     *
     * @param string $dashboardID
     */
    public function dashboard($dashboardID) {
        if (empty($dashboardID)) {
            redirect('analytics');
        }
        $this->addCssFile('vendors.min.css', 'plugins/vanillaanalytics');

        $this->addJsFile('vendors/d3.min.js', 'plugins/vanillaanalytics');
        $this->addJsFile('vendors/c3.min.js', 'plugins/vanillaanalytics');
        $this->addJsFile('dashboard.min.js', 'plugins/vanillaanalytics');
        $this->addJsFile('analyticsdashboard.min.js', 'plugins/vanillaanalytics');
        $this->addJsFile('analyticswidget.min.js', 'plugins/vanillaanalytics');
        $this->addJsFile('analyticstoolbar.min.js', 'plugins/vanillaanalytics');
        $this->addJsFile('vendors/jquery-ui.min.js', 'plugins/vanillaanalytics');


        // Translations
        $this->addDefinition('Unpin from your dashboard', t('Unpin from your dashboard'));
        $this->addDefinition('Pin to your dashboard', t('Pin to your dashboard'));

        $dashboardModel = new AnalyticsDashboard();
        $dashboard = $dashboardModel->getID($dashboardID);

        if ($dashboard) {
            Gdn_Theme::section('Analytics');
        } else {
            redirect('settings');
        }

        $this->setData('Title', $dashboard->getTitle());
        $this->setData('AnalyticsDashboard', $dashboard);
        $this->setData('IsPersonal', $dashboard->isPersonal());
        $this->setData('HasWidgets', $dashboard->hasWidgets());
        $this->addDefinition('analyticsDashboard', $dashboard);

        // Site ID is beneficial for differentiating sites on hub/node setups that may be reporting to one collection.
        if (class_exists('\Infrastructure')) {
            $siteID = \Infrastructure::site('siteid');
        } else {
            $siteID = c('Vanilla.VanillaForums.SiteID');
        }
        $this->addDefinition('siteID', $siteID);

        $this->render('dashboard');
    }

    /**
     * Sort the widgets in a custom dashboard.
     *
     * @param $dashboardID $dashboardID
     * @throws
     */
    public function dashboardSort($dashboardID) {
        if (!Gdn::request()->isPostBack()) {
            throw new Gdn_UserException('POST required.', 403);
        }

        $transientKey = Gdn::request()->getValueFrom(Gdn_Request::INPUT_POST, 'TransientKey', false);

        // If this isn't a postback then return false if there isn't a transient key.
        if (!$transientKey) {
            throw new Gdn_UserException('No CSRF token provided.', 403);
        }

        if (!Gdn::session()->validateTransientKey($transientKey)) {
            throw new Gdn_UserException('The CSRF token is invalid.', 403);
        }

        $success = true;
        $widgets = Gdn::request()->getValueFrom(Gdn_Request::INPUT_POST, 'Widgets', []);

        foreach ($widgets as $widgetID => $position) {
            try {
                Gdn::sql()->update(
                    'AnalyticsDashboardWidget',
                    ['Sort' => $position],
                    [
                        'DashboardID' => $dashboardID,
                        'WidgetID' => $widgetID
                    ]
                )->put();
            } catch (Exception $e) {
                $success = false;
            }
        }

        $this->setData('DashboardID', $dashboardID);
        $this->setData('Widgets', $widgets);
        $this->setData('Success', $success);

        $this->deliveryType(DELIVERY_TYPE_DATA);
        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        $this->render();
    }

    /**
     * Prints out HTML for a select input for direct child categories (one level down) of the passed parent category ID.
     *
     * Expects the following POST params:
     * ParentCategoryID: The category ID whose children to fetch.
     * TransientKey: The session's transient key.
     */
    public function getCategoryDropdown() {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            return;
        }

        $parentCategoryID = Gdn::request()->post('ParentCategoryID');
        $parentDepth = Gdn::request()->post('ParentDepth');

        if (!$parentCategoryID) {
            throw new Exception(t('Missing parameter: ParentCategoryID'), 403);
        }

        $categories = CategoryModel::getChildren($parentCategoryID);
        $categoryData = [];

        foreach($categories as $category) {
            $categoryData[val('CategoryID', $category)] = val('Name', $category);
        }

        if (empty($categoryData)) {
            return;
        }

        $attr = [
            'IncludeNull' => t('All')
        ];

        $form = new Gdn_Form('', 'bootstrap');
        include_once $this->fetchViewLocation('analyticsmodules_helper_functions', 'modules', 'plugins/vanillaanalytics');
        echo getCategoryFilterHTML($form, $categoryData, $attr, t('Subcategory'), $parentDepth + 1);
    }
}
