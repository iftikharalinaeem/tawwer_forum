<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license Proprietary
 */

class SubcommunitiesController extends DashboardController {

    /// Properties ///

    /**
     * @var Gdn_Form;
     */
    protected $form;

    /**
     * @var SubcommunityModel
     */
    protected $siteModel;

    /**
     * @var array The current site we are looking at.
     */
    protected $site;


    /// Methods ///

    public function add() {
        $this->title(sprintf(t('Add %s'), t('Site')));
        $this->addedit();
    }

    protected function addedit() {
        $this->permission('Garden.Settings.Manage');

        $localeModel = new LocaleModel();

        // Get the enabled locale packs.

        if ($this->Request->isAuthenticatedPostBack()) {
            if ($this->site) {
                $siteID = $this->site['SubcommunityID'];
                $this->siteModel->update($this->Request->post(), ['SubcommunityID' => $siteID]);
            } else {
                $siteID = $this->siteModel->insert($this->Request->post());
            }
            if ($siteID) {
                $site = $this->siteModel->getID($siteID);
                $this->setData('Site', $site);
            } else {
                $this->form->setValidationResults($this->siteModel->Validation->results());
            }

            if ($this->form->errorCount() == 0) {
                if ($this->deliveryType() === DELIVERY_TYPE_VIEW) {
                    $this->jsonTarget('', '', 'Refresh');
                } elseif ($this->deliveryType() === DELIVERY_TYPE_ALL) {
                    redirect('/subcommunities');
                }
            }
        } elseif ($this->site) {
            $this->form->setData($this->site);
        }

        $locales = $localeModel->enabledLocalePacks(true);
        $locales = array_column($locales, 'Locale', 'Locale');
        $locales = array_combine($locales, $locales);
        $locales = array_replace(['en' => 'en'], $locales);
        $this->setData('Locales', $locales);

        $categories = CategoryModel::makeTree(CategoryModel::categories());
        $categories = array_column($categories, 'Name', 'CategoryID');
        $this->setData('Categories', $categories);

        $this->View = 'addedit';
        $this->addSideMenu();
        $this->render();
    }

    public function edit() {
        if (!$this->site) {
            throw notFoundException('Site');
        }

        $this->title(sprintf(t('Edit %s'), t('Site')));
        $this->addedit();
    }

    public function delete() {
        if (!$this->site) {
            throw notFoundException('Site');
        }

        if ($this->form->authenticatedPostBack()) {
            $this->siteModel->delete(['SubcommunityID' => $this->site['SubcommunityID']]);

            if ($this->form->errorCount() == 0) {
                if ($this->deliveryType() === DELIVERY_TYPE_VIEW) {
                    $this->jsonTarget('', '', 'Refresh');
                } elseif ($this->deliveryType() === DELIVERY_TYPE_ALL) {
                    redirect('/subcommunities');
                }
            }
        }

        $this->View = 'Delete';
        $this->title(sprintf(t('Delete %s'), t('Site')));
        $this->render();
    }

    protected function getSite($siteID) {
        $this->site = $this->siteModel->getID($siteID);
    }

    public function index($page = '') {
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

        $this->permission('Garden.Settings.Manage');
        $pageSize = 20;
        list($offset, $limit) = offsetLimit($page, $pageSize);
        $this->form = new Gdn_Form();
        $this->form->Method = 'get';

        if ($search = $this->Request->get('search')) {
            $sites = $this->siteModel->search($search, 'Sort,Folder', 'asc', $limit + 1, $offset)->resultArray();

            // Select 1 more than page size so we can know whether or not to display the next link.
            $this->setData('_CurrentRecords', count($sites));
            if (count($sites) > $limit) {
                $sites = array_slice($sites, 0, $pageSize);
            }

            $this->setData('Sites', $sites);
        } else {
            $where = [];
            $this->setData('Sites', $this->siteModel->getWhere($where, 'Sort,Folder', 'asc', $limit, $offset)->resultArray());
            $this->setData('RecordCount', $this->siteModel->getCount($where));
        }

        $this->setData('_Limit', $pageSize);
        $this->addJsFile('jquery.tablednd.js');
//        $this->AddJsFile('subcommunities_admin.js', 'plugins/subcommunities');

        $this->title(t('Sites'));
        $this->addSideMenu();
        $this->render();
    }

    public function initialize() {
        parent::initialize();

        $this->siteModel = SubcommunityModel::instance();
        $this->form = new Gdn_Form;

        // Check for a site.
        $args = Gdn::dispatcher()->controllerArguments();
        if (isset($args[0]) && is_numeric($args[0])) {
            $id = array_shift($args);
            $this->getSite($id);

            // See if there is a method next.
            $method = array_shift($args);
            if ($method) {
                if (stringEndsWith($method, '.json', true)) {
                    $method = stringEndsWith($method, '.json', true, true);
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

    protected function post() {
        if (!$this->Request->isAuthenticatedPostBack()) {
            throw forbiddenException('CSRF POST');
        }

        if ($this->site) {
            throw new Gdn_UserException('Site invalid when creating a site.');
        } else {
            $siteID = $this->siteModel->insert($this->Request->post());
            if ($siteID) {
                $site = $this->siteModel->getID($siteID);
                $this->setData('Site', $site);
            } else {
                $this->form->setValidationResults($this->siteModel->Validation->results());
            }
        }

        if ($this->form->errorCount() == 0) {
            if ($this->deliveryType() === DELIVERY_TYPE_VIEW) {
                $this->jsonTarget('', '', 'Refresh');
            } elseif ($this->deliveryType() === DELIVERY_TYPE_ALL) {
                redirect('/subcommunities');
            }
        }

        $this->render();
    }
}
