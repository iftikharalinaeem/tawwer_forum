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

        $this->Permission('Garden.Settings.Manage');
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
        $this->Permission('Garden.Settings.Manage');
        $this->Title(T('Add Site'));
        $this->Render();
    }

    /**
     * The callback for when a site has been built.
     * @throws Gdn_UserException Thrown when the site was not found.
     */
    public function buildcallback() {
        $this->Permission('Garden.Settings.Manage');

        if (!$this->site) {
            throw NotFoundException('Site');
        }
        $id = $this->site['MultisiteID'];
        $data = array_change_key_case(Gdn::Request()->Post());

        if (val('build', $data) === 'success') {
            MultisiteModel::instance()->status($id, 'active');
            MultisiteModel::instance()->Update(['SiteID' => valr('site.SiteID', $data)]);
            Trace("Status of site $id set to active.");
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

    public function nodeConfig() {
//        $this->Permission('Garden.Settings.Manage');

        // Get the base config.
        $config = C('NodeConfig', []);
        $this->SetData($config);

        // Get the roles.
        $roles = Gdn::SQL()->Select('RoleID,Name')->GetWhere('Role', ['HubSync' => ['settings', 'membership']])->ResultArray();
        $this->SetData('Roles', $roles);

        // Get the categories.


        $this->Render('api');
    }

    protected function patch() {
        if (!$this->site) {
            throw NotFoundException('Site');
        }
    }

    protected function post() {
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

    protected function delete() {
        throw ForbiddenException('DELETE');
    }

    /**
     * Synchronize a node or nodes with the hub.
     */
    public function syncNode() {
        $this->Permission('Garden.Settings.Manage');

        if ($this->site) {
            $result = MultisiteModel::instance()->syncNode($this->site);
            $this->SetData('Result', $result);
        } else {
            $result = MultisiteModel::instance()->syncNodes();
            $this->SetData('Result', $result);
        }
        $this->Render('api');
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
}