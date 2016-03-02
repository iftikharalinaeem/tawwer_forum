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
        $this->site = $this->siteModel->GetID($siteID);
    }

    public function index($page = '') {
        switch ($this->Request->RequestMethod()) {
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
            $this->Permission('Garden.Settings.Manage');
        }
        $pageSize = 20;
        list($offset, $limit) = OffsetLimit($page, $pageSize);
        $this->form = new Gdn_Form();
        $this->form->Method = 'get';

        if ($search = $this->Request->Get('search')) {
            $sites = $this->siteModel->search($search, 'Url', 'asc', $limit + 1, $offset)->ResultArray();

            // Select 1 more than page size so we can know whether or not to display the next link.
            $this->setData('_CurrentRecords', count($sites));
            if (count($sites) > $limit) {
                $sites = array_slice($sites, 0, $pageSize);
            }

            $this->setData('Sites', $sites);
        } else {
            $where = [];
            $this->setData('Sites', $this->siteModel->GetWhere($where, 'Url', 'asc', $limit, $offset)->ResultArray());
            $this->setData('RecordCount', $this->siteModel->GetCount($where));
        }

        $this->setData('_Limit', $pageSize);

        $this->Title('Sites');
        $this->AddSideMenu();
        $this->Render();
    }

    public function add() {
        $this->permissionNoLog('Garden.Settings.Manage');
        $this->Title(T('Add Site'));
        $this->Render();
    }

    /**
     * The callback for when a site has been built.
     * @throws Gdn_UserException Thrown when the site was not found.
     */
    public function buildcallback() {
        $this->permissionNoLog('Garden.Settings.Manage');

        if (Gdn::Request()->RequestMethod() !== 'POST') {
            throw new Gdn_UserException("This resource only accepts POST.", 405);
        }

        if (!$this->site) {
            throw NotFoundException('Site');
        }
        $id = $this->site['MultisiteID'];
        $data = array_change_key_case(Gdn::Request()->Post());

        if (val('result', $data) === 'success') {
            MultisiteModel::instance()->status($id, 'active');
            MultisiteModel::instance()->SetField($id, ['SiteID' => valr('site.SiteID', $data)]);
            Trace("Status of site $id set to active.");
            MultisiteModel::instance()->syncNode($this->site);
        } else {
            MultisiteModel::instance()->status($id, 'error', val('status', $data));
            MultisiteModel::instance()->SaveAttribute($id, 'callback', $data);
            Trace("Status of site $id set to error.");
        }

        $this->Render('API');
    }

    protected function get() {
        if (!$this->site) {
            throw NotFoundException('Site');
        }

        $this->SetData('Site', $this->site);
        $this->Render('api');
    }

    public function nodeConfig($from) {
        $this->permissionNoLog('Garden.Settings.Manage');
        if (!$from) {
            throw NotFoundException('Site');
        }
        $this->site = $this->siteModel->getWhere(['slug' => $from])->FirstRow(DATASET_TYPE_ARRAY);
        if (!$this->site) {
            throw NotFoundException('Site');
        }

        // See if we even should sync.
        $this->SetData('Sync', val('Sync', $this->site));
        if (!$this->Data('Sync')) {
            $this->Render('api');
            return;
        }
        $this->SetData('Multisite', $this->site);

        // Get the base config.
        $config = C('NodeConfig', []);

        // Parse out the node config into a version that is
        $configSettings = val('Config', $config, []);
        $realConfigSettings = [];
        foreach ($configSettings as $key => $value) {
            $realConfigSettings[str_replace('-', '.', $key)] = $value;
        }
        $config['Config'] = $realConfigSettings;

        $this->SetData($config);

        // Get the roles.
        $roles = $this->siteModel->getSyncRoles($this->site);
        $this->SetData('Roles', $roles);

        // Get the categories.
        $categories = $this->siteModel->getSyncCategories($this->site);
        $this->SetData('Categories', $categories);

        $this->SetData('OtherCategories', $this->siteModel->getDontSyncCategories($this->site));

        // Get the authentication providers.
        $providers = Gdn_AuthenticationProviderModel::GetWhereStatic(['SyncToNodes' => 1]);
        $this->SetData('Authenticators', $providers);

        SaveToConfig('Api.Clean', FALSE, FALSE);
        $this->Render('api');
    }

    public function notifySync() {
        if (!$this->site) {
            throw NotFoundException('Site');
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
            throw NotFoundException('Site');
        }

        $post = $this->Request->Post();

        $allowed = ['DateLastSync', 'Status'];
        $post = ArrayTranslate($post, $allowed);
        if (val('Status', $post) === 'active' && $this->site['Status'] !== 'active') {
            $post['DateStatus'] = Gdn_Format::ToDateTime();
        } else {
            unset($post['Status']);
        }

        if (isset($post['DateLastSync'])) {
            $this->siteModel->SetField($this->site['MultisiteID'], $post);
            $this->SetData('DateLastSync', true);
        }

        $this->Render('api');
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

        $siteID = $this->siteModel->insert($this->Request->Post());
        if ($siteID) {
            $site = $this->siteModel->getID($siteID);
            $this->SetData('Site', $site);
        } else {
            throw new Gdn_UserException($this->siteModel->Validation->ResultsText());
        }

        if ($this->DeliveryType() === DELIVERY_TYPE_VIEW) {
            $this->JsonTarget('', '', 'Refresh');
        } elseif ($this->DeliveryType() === DELIVERY_TYPE_ALL) {
            Redirect('/multisites');
        }

        $this->Render('api');
    }

    public function delete() {
        $this->Permission('Garden.Settings.Manage');

        if (!$this->site) {
            throw NotFoundException('Site');
        }

        $this->Form = new Gdn_Form();

        if ($this->Form->AuthenticatedPostBack()) {
            if (!$this->site['SiteID']) {
                // There is no site associated with this role so just delete the row.
                $this->siteModel->Delete(['MultisiteID' => $this->site['MultisiteID']]);
            } elseif (!$this->siteModel->queueDelete($this->site['MultisiteID'])) {
                $this->Form->SetValidationResults($this->siteModel->ValidationResults());
            }
        }

        $this->Title(sprintf(T('Delete %s'), T('Site')));
        $this->Render('Delete');
    }

    /**
     * The callback for when a site has been built.
     * @throws Gdn_UserException Thrown when the site was not found.
     */
    public function deletecallback() {
        $this->permissionNoLog('Garden.Settings.Manage');

        if (Gdn::Request()->RequestMethod() !== 'POST') {
            throw new Gdn_UserException("This resource only accepts POST.", 405);
        }

        if (!$this->site) {
            throw NotFoundException('Site');
        }
        $id = $this->site['MultisiteID'];
        $data = array_change_key_case(Gdn::Request()->Post());

        if (val('result', $data) === 'success') {
            MultisiteModel::instance()->delete(['MultisiteID' => $id]);
        } else {
            MultisiteModel::instance()->status($id, 'error', val('status', $data));
            MultisiteModel::instance()->SaveAttribute($id, 'callback', $data);
            Trace("Status of site $id set to error.");
        }

        $this->Render('API');
    }

    /**
     * Synchronize a node or nodes with the hub.
     */
    public function syncNode() {
        $this->permissionNoLog('Garden.Settings.Manage');

        if (Gdn::Request()->RequestMethod() !== 'POST') {
            throw new Gdn_UserException("This resource only accepts POST.", 405);
        }

        if ($this->site) {
            $result = MultisiteModel::instance()->syncNode($this->site);
            $this->SetData('Result', $result);
        } else {
            $result = MultisiteModel::instance()->syncNodes();
            $this->SetData('Result', $result);

            if ($this->DeliveryType() !== DELIVERY_TYPE_DATA) {
                $this->InformMessage(T('The sites are now synchronizing.'));
            }
        }
        $this->Render('api');
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
     * Include JS, CSS, and modules used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        parent::Initialize();

        $this->siteModel = MultisiteModel::instance();

        // Check for a site.
        $args = Gdn::Dispatcher()->ControllerArguments();
        if (isset($args[0]) && is_numeric($args[0])) {
            $id = array_shift($args);
            $this->getSite($id);

            // See if there is a method next.
            $method = array_shift($args);
            if ($method) {
                if (StringEndsWith($method, '.json', TRUE)) {
                    $method = StringEndsWith($method, '.json', TRUE, TRUE);
                    $this->DeliveryType(DELIVERY_TYPE_DATA);
                    $this->DeliveryMethod(DELIVERY_METHOD_JSON);
                }
                if (method_exists($this, $method)) {
                    Gdn::Dispatcher()->EventArguments['ControllerMethod'] = $method;
                    Gdn::Dispatcher()->ControllerMethod = $method;
                } else {
                    array_unshift($args, $method);
                }
            }

            Gdn::Dispatcher()->ControllerArguments($args);
        }
    }

    /**
     * Proxy postback notifications to nodes.
     *
     * @throws Gdn_UserException
     */
    public function cleanspeakProxy() {
        $this->permissionNoLog('Garden.Settings.Manage');

        $post = Gdn::Request()->Post();
        if (!$post) {
            return;
        }

        Logger::event('cleanspeak_proxy', Logger::INFO, 'Cleanspeak proxy postback.', array('post' => $post));

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
                $context = array('type' => $post['type']);
                Logger::event('cleanspeak_proxy', Logger::INFO, 'Cleanspeak proxy does not support type {type}.', $context);
                return;

        }
        if (is_array($errors) && !empty($errors)) {
            $this->SetData('Errors', $errors);
            $errorMessage = implode(PHP_EOL, $errors);
            $context['Errors'] = $errors;
            Logger::event('cleanspeak_error', Logger::ERROR, 'Error(s) approving content: ' . $errorMessage, $context);

        } else {
            $this->SetData('Success', true);
        }

        $this->Render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Get SiteID from a UUID.
     *
     * @param string $UUID Unique User Identification.
     * @return int mixed SiteID.
     * @throws Gdn_UserException
     */
    protected function getSiteIDFromUUID($UUID) {
        $ints = self::getIntsFromUUID($UUID);
        $siteID = $ints[0];
        if ($siteID == 0) {
            throw new Gdn_UserException('Invalid UUID: ' . $UUID);
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
        $errors = array();

        $multiSiteModel = new MultisiteModel();
        $site = $multiSiteModel->getWhere(array('SiteID' => $siteID))->FirstRow(DATASET_TYPE_ARRAY);
        if (!$site) {
            Logger::event('cleanspeak_error', Logger::ERROR, "Site not found. UUID: {$post['id']} SiteID: $siteID");
            return array();
        }

        try {
            $response = $this->siteModel->nodeApi($site['Slug'], 'mod.json/cleanspeakpostback', 'POST', $post);
        } catch (Gdn_UserException $e) {
            Logger::log(Logger::ERROR, 'Error communicating with node.', array($e->getMessage()));
        }

        if (GetValue('Errors', $response)) {
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
        $siteApprovals = array();
        foreach ($post['approvals'] as $UUID => $action) {
            $siteID = $this->getSiteIDFromUUID($UUID);
            $siteApprovals[$siteID][$UUID] = $action;
        }
        $errors = array();
        foreach ($siteApprovals as $siteID => $siteApproval) {

            $multiSiteModel = new MultisiteModel();
            $site = $multiSiteModel->getWhere(array('SiteID' => $siteID))->FirstRow(DATASET_TYPE_ARRAY);
            if (!$site) {
                $errors[] = 'Site not found: ' . $siteID;
                continue;
            }

            $sitePost = array();
            $sitePost['type'] = $post['type'];
            $sitePost['approvals'] = $siteApproval;
            $sitePost['moderatorId'] = $post['moderatorId'];
            $sitePost['moderatorEmail'] = $post['moderatorEmail'];
            $sitePost['moderatorExternalId'] = GetValue('moderatorExternalId', $post, NULL);

            try {
                $response = $this->siteModel->nodeApi($site['Slug'], 'mod.json/cleanspeakpostback', 'POST', $sitePost);
            } catch (Gdn_UserException $e) {
                $errors[$siteID] = 'Error communicating with node.';
                Logger::log(Logger::ERROR, 'Error communicating with node.', array($e->getMessage()));
            }
            if (GetValue('Errors', $response)) {
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
        $errors = array();

        $multiSiteModel = new MultisiteModel();
        $site = $multiSiteModel->getWhere(array('SiteID' => $siteID))->FirstRow(DATASET_TYPE_ARRAY);
        if (!$site) {
            Logger::event('cleanspeak_error', Logger::ERROR, "Site not found. SiteID: $siteID");
            return;
        }

        try {
            $response = $this->siteModel->nodeApi($site['Slug'], 'mod.json/cleanspeakpostback', 'POST', $post);
        } catch (Gdn_UserException $e) {
            Logger::log(Logger::ERROR, 'Error communicating with node.', array($e->getMessage()));
        }

        if (GetValue('Errors', $response)) {
            $errors[$siteID] = $response['Errors'];
        }

        return $errors;

    }

    /**
     * @param string $UUID Universal Unique Identifier.
     * @return array Containing the 4 numbers used to generate generateUUIDFromInts
     */
    public static function getIntsFromUUID($UUID) {
        $parts = str_split(str_replace('-', '', $UUID), 8);
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
        if (Gdn::Session()->CheckPermission($permission)) {
            return;
        }
        throw PermissionException($permission);
    }


}
