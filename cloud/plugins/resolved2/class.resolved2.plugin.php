<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPLv2
 */

/**
 * Class Resolved2Plugin
 */
class Resolved2Plugin extends Gdn_Plugin {

    const UNRESOLVED_CACHE = 'plugin.resolved2.unresolved_count.%s';
    const UNRESOLVED_CACHE_TTL = 3600;

    const UNRESOLVED_RECALC = 'plugin.resolved2.recalculate.%s';

    /**
     * @var DiscussionModel
     */
    private $discussionModel;

    /**
     * @var Gdn_Controller
     */
    private $controller;

    /**
     * @var Gdn_Request
     */
    private $request;

    /**
     * Resolved2Plugin constructor.
     *
     * @param Gdn_Controller $controller
     * @param DiscussionModel $discussionModel
     * @param Gdn_Request $request
     */
    public function __construct(
        Gdn_Controller $controller,
        DiscussionModel $discussionModel,
        Gdn_Request $request
    ) {
        $this->controller = $controller;
        $this->discussionModel = $discussionModel;
        $this->request = $request;
    }

    /**
     * Plugin setup method.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Plugin structure method.
     *
     * Add 'Resolved' columns to the Discussion table.
     */
    public function structure() {
        Gdn::structure()
            ->table('Discussion')
            ->column('Resolved', 'tinyint', '0')
            // Track the number of time a discussion was resolved.
            // If CountResolved is null it means that the discussion was created while the plugin
            // was not active and the resolution count won't be tracked.
            ->column('CountResolved', 'int', null)
            ->column('DateResolved', 'datetime', true)
            ->column('ResolvedUserID', 'int', true)
            ->set();

        // Disable incompatible plugin.
        if (Gdn::addonManager()->isEnabled('Resolved', \Vanilla\Addon::TYPE_ADDON)) {
            Gdn::pluginManager()->disablePlugin('Resolved');
        }

        \Gdn::config()->touch('Resolved2.DiscussionTitle.DisplayResolved', true);
    }

    /**
     * Generate the option for the option menu.
     *
     * @param $discussion
     * @param $format Either string or array.
     * @return string|array Generated option.
     */
    private function generateOptionMenuItem($discussion, $format) {
        $resolved = val('Resolved', $discussion);
        $discussionID = val('DiscussionID', $discussion);
        $toggledResolved = $resolved ? 0 : 1;

        $label = t($toggledResolved ? 'Resolve' : 'Unresolve');
        $url = "/discussion/resolve?discussionID={$discussionID}&resolve={$toggledResolved}";

        if ($format === 'string') {
            $option = anchor($label, $url, 'ResolveDiscussion Hijack');
        } else {
            $option = [
                'Label' => $label,
                'Url' => $url,
                'Class' => 'ResolveDiscussion Hijack'
            ];
        }

        return $option;
    }

    /**
     * BoxFilterDiscussion list item that contains link to '/discussions/unresolved'.
     *
     * @return string
     */
    private function generateDiscussionFilterItem() {
        $count = countString($this->getUnresolvedDiscussionCount());
        $unresolved = t('Unresolved').($count ? '<span class="Aside">'.$count.'</span>' : null);
        return '<li class="Unresolved">'.anchor(sprite('SpUnresolved').' '.$unresolved, '/discussions/unresolved').'</li>';
    }

    /**
     * Get resolved/unresolved markup.
     *
     * @param array|object $discussion
     * @return string
     */
    private function generateStateIndicator($discussion) {
        $name = val('Resolved', $discussion) ? 'resolved' : 'unresolved';

        $markup = '<span title="' . t(ucfirst($name)) . '" class="MItem MItem-Resolved">';
        $markup .= file_get_contents(PATH_PLUGINS."/resolved2/design/svgs/{$name}.svg");
        $markup .= '</span>';

        return $markup;
    }

    /**
     * Get a hash of category permissions
     *
     * @return string
     */
    private function getPermissionHash() {
        static $permissionHash;

        if (is_null($permissionHash)) {
            $permissions = DiscussionModel::categoryPermissions();
            if ($permissions === true) {
                $permissionHash = 'all';
            } else {
                $permissionHash = sha1(serialize(sort($permissions)));
            }
        }

        return $permissionHash;
    }

    /**
     * Return the number of unresolved discussions.
     *
     * @return int
     */
    private function getUnresolvedDiscussionCount() {
        $permissionHash = $this->getPermissionHash();
        $unresolvedKey = sprintf(self::UNRESOLVED_CACHE, $permissionHash);

        $unresolved = Gdn::cache()->get($unresolvedKey);
        if ($unresolved === Gdn_Cache::CACHEOP_FAILURE) {
            $unresolved = $this->updateUnresolvedDiscussionCache($unresolvedKey);
        }
        return $unresolved;
    }

    /**
     * Update count of unresolved discussions.
     *
     * @param string $unresolvedKey precaclculated permissions-based key
     * @return int
     */
    private function updateUnresolvedDiscussionCache(string $unresolvedKey = null) {

        if (is_null($unresolvedKey)) {
            $permissionHash = $this->getPermissionHash();
            $unresolvedKey = sprintf(self::UNRESOLVED_CACHE, $permissionHash);
        }

        $unresolved = $this->discussionModel->getCount([
            'Resolved' => 0,
        ]);

        Gdn::cache()->store($unresolvedKey, $unresolved, [
            Gdn_Cache::FEATURE_EXPIRY => self::UNRESOLVED_CACHE_TTL
        ]);

        return $unresolved;
    }

    /**
     * Get the discussion name for the resolved state.
     * Prepend [RESOLVED] to the discussion's name if resolved.
     *
     * @param array|object $discussion The discussion.
     * @return string
     */
    private function getUpdatedDiscussionName($discussion) {
        if (c('Resolved2.DiscussionTitle.DisplayResolved')) {
            $newName = $this->generateStateIndicator($discussion).val('Name', $discussion);
        } else {
            $newName = val('Name', $discussion, '');
        }
        return $newName;
    }

    /**
     * Update the UI.
     *
     * @param $discussion
     */
    private function setJSONTarget($discussion) {
        // Discussion list.
        $this->controller->jsonTarget(
            "#Discussion_{$discussion['DiscussionID']} .MItem-Resolved",
            $this->generateStateIndicator($discussion),
            'ReplaceWith'
        );

        if (c('Resolved2.DiscussionTitle.DisplayResolved')) {
            // Update the discussion title.
            $this->controller->jsonTarget('.Discussion #Item_0 h1', $this->getUpdatedDiscussionName($discussion));

            // Highlight the discussion title.
            $this->controller->jsonTarget('.Discussion #Item_0', null, 'Highlight');
        }

        // Update the option menu.
        $this->controller->jsonTarget(
            '.Discussion #Item_0 .OptionsMenu .ResolveDiscussion',
            $this->generateOptionMenuItem($discussion, 'string'),
            'ReplaceWith'
        );

        // Update the unresolved discussion count
        $this->controller->jsonTarget(
            '.BoxDiscussionFilter .Unresolved',
            $this->generateDiscussionFilterItem(),
            'ReplaceWith'
        );
    }

    /**
     * Set a discussion's resolved state.
     *
     * @param array $discussion
     * @param bool $resolved
     * @return array The resolved discussion.
     */
    private function setResolved($discussion, $resolved) {
        $resolutionFields = [
            'Resolved' => $resolved ? 1 : 0,
            'DateResolved' => $resolved ? Gdn_Format::toDateTime() : null,
            'ResolvedUserID' => $resolved ? Gdn::session()->UserID : null
        ];

        // Only set CountResolved if the field is not empty.
        $currentCountResolved = val('CountResolved', $discussion, null);
        if (!empty($currentCountResolved) || in_array($currentCountResolved, [0, '0'], true)) {
            $countResolved = $currentCountResolved + $resolutionFields['Resolved'];
            $resolutionFields['CountResolved'] = $countResolved;
        }

        $discussion = array_merge($discussion, $resolutionFields);

        return $discussion;
    }


    /**
     * Save a discussion's resolved field.
     *
     * @param array $discussion
     */
    private function saveDiscussionResolvedFields($discussion) {
        $resolutionFields = [
            'Resolved' => val('Resolved', $discussion, 0),
            'CountResolved' => val('CountResolved', $discussion, null),
            'DateResolved' => val('DateResolved', $discussion, null),
            'ResolvedUserID' => val('ResolvedUserID', $discussion, null),
        ];

        $this->discussionModel->setField($discussion['DiscussionID'], $resolutionFields);

        $permissionHash = $this->getPermissionHash();
        $recalcKey = sprintf(self::UNRESOLVED_RECALC, $permissionHash);
        Gdn::cache()->store($recalcKey, $permissionHash, [
            Gdn_Cache::FEATURE_EXPIRY => 60
        ]);

        // Force a trackEvent since we are calling update instead of DiscussionModel->save()
        if (class_exists('AnalyticsTracker')) {
            $type = 'discussion_edit';
            $collection = 'post_modify';

            $data = AnalyticsData::getDiscussion(val('DiscussionID', $discussion));

            AnalyticsTracker::getInstance()->trackEvent($collection, $type, $data);
        }

    }

    /**
     * Set resolved metric on discussions.
     *
     * @param AnalyticsTracker $sender Event's source.
     * @param array $args Event's arguments.
     */
    public function analyticsTracker_beforeTrackEvent_handler($sender, $args) {
        if (!in_array($args['Collection'], ['post', 'post_modify'])) {
            return;
        }

        if (in_array($args['Event'], ['discussion_add', 'discussion_edit'])) {
            $discussion = $this->discussionModel->getID($args['Data']['discussionID'], DATASET_TYPE_ARRAY);

            $dateResolved = $discussion['DateResolved'] ? AnalyticsData::getDateTime($discussion['DateResolved']) : null;
            if ($dateResolved) {
                $timeResolved = $dateResolved['timestamp'] - $args['Data']['dateInserted']['timestamp'];
            } else {
                $timeResolved = null;
            }

            $resolvedMetric = [
                'resolved' => $discussion['Resolved'],
                'countResolved' => $discussion['CountResolved'],
                'dateResolved' => $dateResolved,
                'resolvedUser' => $discussion['ResolvedUserID'] ? AnalyticsData::getUser($discussion['ResolvedUserID']) : null,
                'time' => $timeResolved,
            ];

            $args['Data']['resolvedMetric'] = $resolvedMetric;
        }
    }

    /**
     * Hook into the Tick event for every real page load
     *
     * Look for a reason to update the unresolved count.
     *
     * @param Gdn_Statistics $sender
     */
    public function gdn_statistics_analyticsTick_handler($sender) {
        $permissionHash = $this->getPermissionHash();
        $recalcKey = sprintf(self::UNRESOLVED_RECALC, $permissionHash);

        // Check is recalc flag is set
        $recalcFlag = Gdn::cache()->get($recalcKey);
        if ($recalcFlag) {
            Gdn::cache()->remove($recalcKey);

            $unresolvedKey = sprintf(self::UNRESOLVED_CACHE, $permissionHash);
            $this->updateUnresolvedDiscussionCache($unresolvedKey);
        }
    }

    /**
     * Add 'Unresolved' discussions filter to menu.
     */
    public function base_afterDiscussionFilters_handler() {
        if (checkPermission('Garden.Staff.Allow')) {
            echo $this->generateDiscussionFilterItem();
        }
    }

    /**
     * Add additional stylesheet globally.
     *
     * @param AssetModel $sender Event's source.
     */
    public function assetModel_styleCss_handler($sender) {
        $sender->addCssFile('resolved2.css', 'plugins/resolved2');
    }

    /**
     * Add resolved/unresolved icon
     *
     * @param Gdn_Controller $sender Event's source.
     * @param array $args Event's arguments.
     */
    public function base_beforeDiscussionMeta_handler($sender, $args) {
        if (!checkPermission('Garden.Staff.Allow')) {
            return;
        }

        echo $this->generateStateIndicator($args['Discussion']);
    }

    /**
     * Allow staff to Resolve via discussion options.
     *
     * @param Gdn_Controller $sender Sending controller instance.
     * @param array $args Event's arguments.
     */
    public function base_discussionOptions_handler($sender, $args) {
        if (!checkPermission('Garden.Staff.Allow')) {
            return;
        }

        $discussion = $args['Discussion'];

        // Deal with inconsistencies in how options are passed
        $options = val('Options', $this->controller);
        if ($options) {
            $options .= wrap($this->generateOptionMenuItem($discussion, 'string'), 'li', ['role' => 'presentation', 'class' => 'no-icon']);
            setValue('Options', $this->controller, $options);
        } else {
            $args['DiscussionOptions']['ResolveDiscussion'] = $this->generateOptionMenuItem($discussion, 'array');
        }
    }

    /**
     * Set discussion's resolved state when a new comment is made.
     *
     * @param CommentModel $sender Sending model instance.
     * @param array $args Event's arguments.
     */
    public function commentModel_afterSaveComment_handler($sender, $args) {
        $discussionID = valr('FormPostValues.DiscussionID', $args);
        $discussion = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);

        if ($discussion['Resolved'] XOR checkPermission('Garden.Staff.Allow')) {
            $resolved = checkPermission('Garden.Staff.Allow');
            $discussion = $this->setResolved($discussion, $resolved);
            $this->saveDiscussionResolvedFields($discussion);

            if ($resolved) {
                $this->setJSONTarget($discussion);
            }
        }
    }

    /**
     * Show [RESOLVED] in discussion title when viewing single.
     */
    public function discussionController_beforeDiscussionOptions_handler() {
        if (!c('Resolved2.DiscussionTitle.DisplayResolved')) {
            return;
        }

        $discussion = $this->controller->data('Discussion');

        if (checkPermission('Garden.Staff.Allow')) {
            $newName = $this->getUpdatedDiscussionName($discussion);
            setValue('Name', $discussion, $newName);
            $this->controller->setData('Discussion', $discussion);
        }
    }

    /**
     * Handle discussion option menu Resolve action.
     *
     * @throws Exception Throws an exception when the discussion is not found, or the request is not a POST
     */
    public function discussionController_resolve_create() {
        $this->controller->permission('Garden.Staff.Allow');

        $discussionID = $this->request->get('discussionID');
        $resolved = $this->request->get('resolve') ? 1 : 0;

        // Make sure we are posting back.
        if (!$this->request->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }

        $discussion = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);

        if (!$discussion) {
            throw notFoundException('Discussion');
        }

        // Resolve the discussion.
        $discussion = $this->setResolved($discussion, $resolved);
        $this->saveDiscussionResolvedFields($discussion);
        $this->controller->sendOptions((object)$discussion);
        $this->setJSONTarget($discussion);

        $this->controller->render('blank', 'utility', 'dashboard');
    }

    /**
     * Create the /discussions/unresolved endpoint.
     *
     * @param CommentModel $sender Sending model instance.
     * @param array $args Event's arguments.
     */
    public function discussionsController_unresolved_create($sender, $args) {
        $this->controller->permission('Garden.Staff.Allow');

        Gdn_Theme::section('DiscussionList');
        Gdn_Theme::section('ResolvedList');

        $page = val(0, $args, 0);

        // Determine offset from $page
        list($page, $limit) = offsetLimit($page, c('Vanilla.Discussions.PerPage', 30));

        // Validate $page
        if (!ctype_digit((string)$page) || $page < 0) {
            $page = 0;
        }

        $discussions = $this->discussionModel->getWhere(['Announce' => 'all', 'Resolved' => 0], '', '', $limit, $page);
        $this->controller->DiscussionData = $discussions;
        $this->controller->setData('Discussions', $discussions);
        $countDiscussions = $this->getUnresolvedDiscussionCount();
        $this->controller->setData('CountDiscussions', $countDiscussions);
        $this->controller->Category = false;

        $this->controller->setJson('Loading', $page.' to '.$limit);

        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $this->controller->EventArguments['PagerType'] = 'Pager';
        $this->controller->fireEvent('BeforeBuildBookmarkedPager');
        $this->controller->Pager = $pagerFactory->getPager($this->controller->EventArguments['PagerType'], $this->controller);
        $this->controller->Pager->ClientID = 'Pager';
        $this->controller->Pager->configure(
            $page, $limit, $countDiscussions, 'discussions/unresolved/%1$s'
        );

        if (!$this->controller->data('_PagerUrl')) {
            $this->controller->setData('_PagerUrl', 'discussions/unresolved/{Page}');
        }
        $this->controller->setData('_Page', $page);
        $this->controller->setData('_Limit', $limit);
        $this->controller->fireEvent('AfterBuildBookmarkedPager');

        // Deliver JSON data if necessary
        if ($sender->deliveryType() != DELIVERY_TYPE_ALL) {
            $this->controller->setJson('LessRow', $this->controller->Pager->toString('less'));
            $this->controller->setJson('MoreRow', $this->controller->Pager->toString('more'));
            $this->controller->View = 'discussions';
        }

        // Add modules
        $this->controller->addModule('DiscussionFilterModule');
        $this->controller->addModule('NewDiscussionModule');
        $this->controller->addModule('CategoriesModule');

        // Render default view
        $this->controller->setData('Title', t('Unresolved Discussions'));
        $this->controller->setData('Breadcrumbs', [['Name' => t('Unresolved'), 'Url' => '/discussions/unresolved']]);
        $this->controller->render('index');
    }

    /**
     * Initialize CountResolved and add resolved fields if needed.
     *
     * @param DiscussionModel $sender Sending model instance.
     * @param array $args Event's arguments.
     */
    public function discussionModel_beforeSaveDiscussion_handler($sender, $args) {
        if ($args['Insert']) {
            $resolved = checkPermission('Garden.Staff.Allow');
            $args['FormPostValues']['CountResolved'] = 0;
            // Make sure that we don't screw with the discussionModel->save from setResolved.
            $args['FormPostValues'] = $this->setResolved($args['FormPostValues'], $resolved, false);
        }
    }

    /**
     * Add /settings/resolved2 endpoint.
     */
    public function settingsController_resolved2_create() {
        $this->controller->permission('Garden.Settings.Manage');

        $conf = new ConfigurationModule($this->controller);
        $conf->initialize([
            'Resolved2.DiscussionTitle.DisplayResolved' => [
                'Control' => 'Toggle',
                'LabelCode' => t('Display resolution state in a discussion'),
                'Description' => sprintf(t('Prefix the discussion title with a resolved state indicator.')),
            ],
        ]);

        $this->controller->setData('Title', sprintf(t('%s Settings'), t('Resolved Discussion')));
        $sender->ConfigurationModule = $conf;
        $conf->renderAll();
    }


}
