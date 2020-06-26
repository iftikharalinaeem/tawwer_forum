<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license Proprietary
 */

class MultisitesController extends DashboardController {
    /// Properties ///

    protected $Uses = ['Form'];

    /**
     * @var Gdn_Form;
     */
    protected $form;

    /**
     * @var MultisiteModel
     */
    protected $siteModel;

    /**
     * @var array The current site we are looking at.
     */
    protected $site;

    /// Methods ///

    protected function getSite($siteID) {
        $this->site = $this->siteModel->getID($siteID);
    }

    public function index($page = '', $sort = '') {
        switch ($this->Request->requestMethod()) {
            case 'GET':
                if ($this->site) {
                    return $this->get();
                }
                break;
            case 'POST':
                if ($this->site) {
                    return $this->patch();
                } else {
                    return $this->post();
                }
                break;
            case 'DELETE':
                return $this->delete();
                break;
        }

        if ($this->site) {
            $this->permissionNoLog('Garden.Settings.Manage');
        } else {
            $this->permission('Garden.Settings.Manage');
        }
        $pageSize = 20;
        list($offset, $limit) = offsetLimit($page, $pageSize);
        $this->form = new Gdn_Form('', 'bootstrap');
        $this->form->Method = 'get';

        if (!in_array(strtolower($sort), ['url', 'dateinserted'])) {
            $sort = 'url';
        }

        if ($search = $this->Request->get('search')) {
            $sites = $this->siteModel->search($search, $sort, 'asc', $limit + 1, $offset)->resultArray();

            // Select 1 more than page size so we can know whether or not to display the next link.
            $this->setData('_CurrentRecords', count($sites));
            if (count($sites) > $limit) {
                $sites = array_slice($sites, 0, $pageSize);
            }

            $this->setData('Sites', $sites);
        } else {
            $where = [];
            $this->setData('Sites', $this->siteModel->getWhere($where, $sort, 'asc', $limit, $offset)->resultArray());
            $this->setData('RecordCount', $this->siteModel->getCount($where));
        }

        $this->setData('_Limit', $pageSize);

        $this->title('Sites');
        $this->addSideMenu();
        $this->render();
    }

    public function add() {
        $this->permissionNoLog('Garden.Settings.Manage');
        $this->title(t('Add Site'));
        $this->render();
    }

    /**
     * The callback for when a site has been built.
     * @throws Gdn_UserException Thrown when the site was not found.
     */
    public function buildcallback() {
        $this->permissionNoLog('Garden.Settings.Manage');

        if (Gdn::request()->requestMethod() !== 'POST') {
            throw new Gdn_UserException("This resource only accepts POST.", 405);
        }

        if (!$this->site) {
            throw notFoundException('Site');
        }
        $id = $this->site['MultisiteID'];
        $data = array_change_key_case(Gdn::request()->post());

        if (val('result', $data) === 'success') {
            MultisiteModel::instance()->status($id, 'active');
            MultisiteModel::instance()->setField($id, ['SiteID' => valr('site.SiteID', $data)]);
            trace("Status of site $id set to active.");
            MultisiteModel::instance()->syncNode($this->site);
        } else {
            MultisiteModel::instance()->status($id, 'error', val('status', $data));
            MultisiteModel::instance()->saveAttribute($id, 'callback', $data);
            trace("Status of site $id set to error.");
        }

        $this->render('API');
    }

    protected function get() {
        if (!$this->site) {
            throw notFoundException('Site');
        }

        $this->setData('Site', $this->site);
        $this->render('api');
    }

    public function nodeConfig($from) {
        $this->permissionNoLog('Garden.Settings.Manage');
        if (!$from) {
            throw notFoundException('Site');
        }
        $this->site = $this->siteModel->getWhere(['slug' => $from])->firstRow(DATASET_TYPE_ARRAY);
        if (!$this->site) {
            throw notFoundException('Site');
        }

        // See if we even should sync.
        $this->setData('Sync', val('Sync', $this->site));
        if (!$this->data('Sync')) {
            $this->render('api');
            return;
        }
        $this->setData('Multisite', $this->site);

        // Get the base config.
        $config = c('NodeConfig', []);

        // Parse out the node config into a version that is
        $configSettings = val('Config', $config, []);
        $realConfigSettings = [];
        foreach ($configSettings as $key => $value) {
            $realConfigSettings[str_replace('-', '.', $key)] = $value;
        }
        $config['Config'] = $realConfigSettings;

        $this->setData($config);

        // Get the roles.
        $roles = $this->siteModel->getSyncRoles($this->site);
        $this->setData('Roles', $roles);

        // Get the categories.
        $categories = $this->siteModel->getSyncCategories($this->site);
        $this->setData('Categories', $categories);

        $this->setData('OtherCategories', $this->siteModel->getDontSyncCategories($this->site));

        // Get the authentication providers.
        $providers = Gdn_AuthenticationProviderModel::getWhereStatic(['SyncToNodes' => 1]);
        $this->setData('Authenticators', $providers);

        saveToConfig('Api.Clean', FALSE, FALSE);
        $this->render('api');
    }

    public function notifySync() {
        if (!$this->site) {
            throw notFoundException('Site');
        }
    }

    /**
     * Handled updating sites?
     *
     * @throws type
     */
    protected function patch() {
        $this->permissionNoLog('Garden.Settings.Manage');

        if (!$this->site) {
            throw notFoundException('Site');
        }

        $post = $this->Request->post();

        $allowed = ['DateLastSync', 'Status', 'Locale', 'Name'];
        $post = arrayTranslate($post, $allowed);
        if (val('Status', $post) === 'active' && $this->site['Status'] !== 'active') {
            $post['DateStatus'] = Gdn_Format::toDateTime();
        } else {
            unset($post['Status']);
        }

        if (isset($post['DateLastSync'])) {
            $this->siteModel->setField($this->site['MultisiteID'], $post);
            $this->setData('DateLastSync', true);
        }

        $this->render('api');
    }

    /**
     * Handled adding sites?
     *
     * @throws Gdn_UserException
     */
    protected function post() {
        $this->permissionNoLog('Garden.Settings.Manage');

        if ($this->site) {
            throw new Gdn_UserException('Site invalid when creating a site.');
        }

        $siteID = $this->siteModel->insert($this->Request->post());
        if ($siteID) {
            $site = $this->siteModel->getID($siteID);
            $this->setData('Site', $site);
        } else {
            throw new Gdn_UserException($this->siteModel->Validation->resultsText());
        }

        if ($this->deliveryType() === DELIVERY_TYPE_VIEW) {
            $this->jsonTarget('', '', 'Refresh');
        } elseif ($this->deliveryType() === DELIVERY_TYPE_ALL) {
            redirectTo('/multisites');
        }

        $this->render('api');
    }

    public function delete() {
        $this->permission('Garden.Settings.Manage');

        if (!$this->site) {
            throw notFoundException('Site');
        }

        $this->Form = new Gdn_Form();

        if ($this->Form->authenticatedPostBack()) {
            if (!$this->site['SiteID']) {
                // There is no site associated with this role so just delete the row.
                $this->siteModel->delete(['MultisiteID' => $this->site['MultisiteID']]);
                $this->jsonTarget("#Multisite_{$this->site['MultisiteID']}", '', 'SlideUp');
            } elseif (!$this->siteModel->queueDelete($this->site['MultisiteID'])) {
                $this->Form->setValidationResults($this->siteModel->validationResults());
            } else {
                $this->jsonTarget("#Multisite_{$this->site['MultisiteID']} td.js-status", t('deleting'), 'Html');
            }
        }

        $this->render('blank', 'utility', 'dashboard');
    }

    /**
     * The callback for when a site has been built.
     * @throws Gdn_UserException Thrown when the site was not found.
     */
    public function deletecallback() {
        $this->permissionNoLog('Garden.Settings.Manage');

        if (Gdn::request()->requestMethod() !== 'POST') {
            throw new Gdn_UserException("This resource only accepts POST.", 405);
        }

        if (!$this->site) {
            throw notFoundException('Site');
        }
        $id = $this->site['MultisiteID'];
        $data = array_change_key_case(Gdn::request()->post());

        if (val('result', $data) === 'success') {
            MultisiteModel::instance()->delete(['MultisiteID' => $id]);
        } else {
            MultisiteModel::instance()->status($id, 'error', val('status', $data));
            MultisiteModel::instance()->saveAttribute($id, 'callback', $data);
            trace("Status of site $id set to error.");
        }

        $this->render('API');
    }

    /**
     * Synchronize a node or nodes with the hub.
     */
    public function syncNode() {
        $this->permissionNoLog('Garden.Settings.Manage');

        if (Gdn::request()->requestMethod() !== 'POST') {
            throw new Gdn_UserException("This resource only accepts POST.", 405);
        }

        if ($this->site) {
            $result = MultisiteModel::instance()->syncNode($this->site);
            $this->setData('Result', $result);
        } else {
            $result = MultisiteModel::instance()->syncNodes();
            $this->setData('Result', $result);

            if ($this->deliveryType() !== DELIVERY_TYPE_DATA) {
                $this->informMessage(t('The sites are now synchronizing.'));
            }
        }
        $this->render('api');
    }

    /**
     * Synchronize the categories from a node into the NodeCategory table.
     */
    public function syncNodeCategories() {
        $this->permissionNoLog('Garden.Settings.Manage');

        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Gdn_UserException("This resource only accepts POST.", 405);
        }

        // Lookup the site.
        $post = $this->Request->post();
        $site = $this->siteModel->getSiteFromKey(val('MultisiteID', $post), val('SiteID', $post), val('Slug', $post));
        if (!$site) {
            throw notFoundException('Site');
        }

        $categories = val('Categories', $post);
        if (!is_array($categories)) {
            throw new Gdn_UserException('Categories are required');
        }

        $result = $this->siteModel->syncNodeCategories($site['MultisiteID'], $categories, val('Delete', $post, true));
        $this->Data = $result;


        $this->render('api');
    }

    /**
     * Synchronize the subcommunities from a node into the NodeSubcommunity table.
     */
    public function syncNodeSubcommunities() {
        $this->permissionNoLog('Garden.Settings.Manage');

        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Gdn_UserException("This resource only accepts POST.", 405);
        }

        // Lookup the site.
        $post = $this->Request->post();
        $site = $this->siteModel->getSiteFromKey(val('MultisiteID', $post), val('SiteID', $post), val('Slug', $post));
        if (!$site) {
            throw notFoundException('Site');
        }

        $subcommunities = val('Subcommunities', $post);
        if (!is_array($subcommunities)) {
            throw new Gdn_UserException('Subcommunities is required.');
        }

        $result = $this->siteModel->syncNodeSubcommunities($site['MultisiteID'], $subcommunities, val('Delete', $post, true));
        $this->Data = $result;

        $this->render('api');
    }

    /**
     * Include JS, CSS, and modules used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        parent::initialize();

        $this->siteModel = MultisiteModel::instance();

        // Check for a site.
        $args = Gdn::dispatcher()->controllerArguments();
        if (isset($args[0]) && is_numeric($args[0])) {
            $id = array_shift($args);
            $this->getSite($id);

            // See if there is a method next.
            $method = array_shift($args);
            if ($method) {
                if (stringEndsWith($method, '.json', TRUE)) {
                    $method = stringEndsWith($method, '.json', TRUE, TRUE);
                    $this->deliveryType(DELIVERY_TYPE_DATA);
                    $this->deliveryMethod(DELIVERY_METHOD_JSON);
                }
                if (method_exists($this, $method)) {
                    Gdn::dispatcher()->EventArguments['ControllerMethod'] = $method;
                    Gdn::dispatcher()->ControllerMethod = $method;
                } else {
                    array_unshift($args, $method);
                }
            }

            Gdn::dispatcher()->controllerArguments($args);
        }
    }

    /**
     * Proxy postback notifications to nodes.
     *
     * @throws Gdn_UserException
     */
    public function cleanspeakProxy() {
        $this->permissionNoLog('Garden.Settings.Manage');

        $post = Gdn::request()->post();
        if (!$post) {
            return;
        }

        Logger::event('cleanspeak_proxy', Logger::INFO, 'Cleanspeak proxy postback.', ['post' => $post]);

        switch ($post['type']) {
            case 'contentApproval':
                $errors = $this->cleanspeakContentApproval($post);
                break;
            case 'contentDelete':
                $errors = $this->cleanspeakContentDelete($post);
                break;
//            case 'userAction':
//                $errors = $this->cleanspeakUserAction($post);
//                break;
            default:
                $context = ['type' => $post['type']];
                Logger::event('cleanspeak_proxy', Logger::INFO, 'Cleanspeak proxy does not support type {type}.', $context);
                return;

        }
        if (is_array($errors) && !empty($errors)) {
            $this->setData('Errors', $errors);
            $errorMessage = implode(PHP_EOL, $errors);
            $context['Errors'] = $errors;
            Logger::event('cleanspeak_error', Logger::ERROR, 'Error(s) approving content: ' . $errorMessage, $context);

        } else {
            $this->setData('Success', true);
        }

        $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Get SiteID from a UUID.
     *
     * @param string $uuid Unique User Identification.
     * @return int mixed SiteID.
     * @throws Gdn_UserException
     */
    protected function getSiteIDFromUUID($uuid) {
        $ints = self::getIntsFromUUID($uuid);
        $siteID = $ints[0];
        if ($siteID == 0) {
            throw new Gdn_UserException('Invalid UUID: ' . $uuid);
        }
        return $siteID;
    }

    /**
     * Handle ContentDelete post back notification.
     *
     * @param array $post Post data.
     * @return array Errors. Empty if none.
     * @throws Gdn_UserException
     */
    protected function cleanspeakContentDelete($post) {
        $siteID = $this->getSiteIDFromUUID($post['id']);
        $errors = [];

        $multiSiteModel = new MultisiteModel();
        $site = $multiSiteModel->getWhere(['SiteID' => $siteID])->firstRow(DATASET_TYPE_ARRAY);
        if (!$site) {
            Logger::event('cleanspeak_error', Logger::ERROR, "Site not found. UUID: {$post['id']} SiteID: $siteID");
            return [];
        }

        try {
            $response = $this->siteModel->nodeApi($site['Slug'], 'mod/cleanspeakpostback.json', 'POST', $post);
        } catch (Gdn_UserException $e) {
            Logger::log(Logger::ERROR, 'Error communicating with node.', [$e->getMessage()]);
        }

        if (getValue('Errors', $response)) {
            $errors[$siteID] = $response['Errors'];
        }

        return $errors;

    }

    /**
     * Handle ContentApproval post back notification.
     *
     * @param array $post Post data.
     * @return array Errors. Empty if none.
     * @throws Gdn_UserException
     */
    protected function cleanspeakContentApproval($post) {
        $siteApprovals = [];
        foreach ($post['approvals'] as $uuid => $action) {
            $siteID = $this->getSiteIDFromUUID($uuid);
            $siteApprovals[$siteID][$uuid] = $action;
        }
        $errors = [];
        foreach ($siteApprovals as $siteID => $siteApproval) {

            $multiSiteModel = new MultisiteModel();
            $site = $multiSiteModel->getWhere(['SiteID' => $siteID])->firstRow(DATASET_TYPE_ARRAY);
            if (!$site) {
                $errors[] = 'Site not found: ' . $siteID;
                continue;
            }

            $sitePost = [];
            $sitePost['type'] = $post['type'];
            $sitePost['approvals'] = $siteApproval;
            $sitePost['moderatorId'] = $post['moderatorId'];
            $sitePost['moderatorEmail'] = $post['moderatorEmail'];
            $sitePost['moderatorExternalId'] = getValue('moderatorExternalId', $post, NULL);

            try {
                $response = $this->siteModel->nodeApi($site['Slug'], 'mod/cleanspeakpostback.json', 'POST', $sitePost);
            } catch (Gdn_UserException $e) {
                $errors[$siteID] = 'Error communicating with node.';
                Logger::log(Logger::ERROR, 'Error communicating with node.', [$e->getMessage()]);
            }
            if (getValue('Errors', $response)) {
                $errors[$siteID] = $response['Errors'];
            }
        }
        return $errors;

    }

    /**
     * Handle userAction post back notification.
     *
     * @param array $post Post data.
     * @return array Errors. Empty if none.
     */
    protected function cleanspeakUserAction($post) {

        $siteID = $this->getSiteIDFromUUID($post['userId']);
        $errors = [];

        $multiSiteModel = new MultisiteModel();
        $site = $multiSiteModel->getWhere(['SiteID' => $siteID])->firstRow(DATASET_TYPE_ARRAY);
        if (!$site) {
            Logger::event('cleanspeak_error', Logger::ERROR, "Site not found. SiteID: $siteID");
            return;
        }

        try {
            $response = $this->siteModel->nodeApi($site['Slug'], 'mod/cleanspeakpostback.json', 'POST', $post);
        } catch (Gdn_UserException $e) {
            Logger::log(Logger::ERROR, 'Error communicating with node.', [$e->getMessage()]);
        }

        if (getValue('Errors', $response)) {
            $errors[$siteID] = $response['Errors'];
        }

        return $errors;

    }

    /**
     * @param string $uuid Universal Unique Identifier.
     * @return array Containing the 4 numbers used to generate generateUUIDFromInts
     */
    public static function getIntsFromUUID($uuid) {
        $parts = str_split(str_replace('-', '', $uuid), 8);
        $parts = array_map('hexdec', $parts);
        return $parts;
    }

    /**
     * Check a permission, but don't log the access.
     *
     * @param string $permission The name of the permission to check.
     * @throws \Exception Throws an exception if the user does not have permission.
     */
    protected function permissionNoLog($permission) {
        if (Gdn::session()->checkPermission($permission)) {
            return;
        }
        throw permissionException($permission);
    }

    /**
     * Get a list of supported locales from the hub.
     *
     * This is an API-only endpoint.
     */
    public function locales() {
        $locales = MultisiteModel::instance()->getNodeLocales();
        array_walk($locales, function (&$row) {
            if (!empty($row['Url'])) {
                $row['Url'] = url($row['Url'], '//');
            }
        });

        $this->setData('Locales', $locales);
        $this->render('api');
    }
}
