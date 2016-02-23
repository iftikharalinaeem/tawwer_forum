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
        $this->Title(sprintf(T('Add %s'), T('Site')));
        $this->addedit();
    }

    protected function addedit() {
        $this->Permission('Garden.Settings.Manage');

        $localeModel = new LocaleModel();

        // Get the enabled locale packs.

        if ($this->Request->IsAuthenticatedPostBack()) {
            if ($this->site) {
                $siteID = $this->site['SubcommunityID'];

                $postData = $this->Request->Post();
                // Unchecked checkboxes are not sent in post data :P
                if (!isset($postData['IsDefault'])) {
                    $postData['IsDefault'] = false;
                }

                $this->siteModel->update($postData, ['SubcommunityID' => $siteID]);
            } else {
                $siteID = $this->siteModel->insert($this->Request->Post());
            }

            if ($siteID) {
                $site = $this->siteModel->getID($siteID);
                $this->SetData('Site', $site);
            } else {
                $this->form->SetValidationResults($this->siteModel->Validation->Results());
            }

            if ($this->form->ErrorCount() == 0) {
                if ($this->DeliveryType() === DELIVERY_TYPE_VIEW) {
                    $this->JsonTarget('', '', 'Refresh');
                } elseif ($this->DeliveryType() === DELIVERY_TYPE_ALL) {
                    Redirect('/subcommunities');
                }
            }
        } elseif ($this->site) {
            $this->form->SetData($this->site);
        }

        $locales = $localeModel->EnabledLocalePacks(true);
        $locales = array_column($locales, 'Locale', 'Locale');
        $locales = array_combine($locales, $locales);
        $locales = array_replace(['en' => 'en'], $locales);
        $this->SetData('Locales', $locales);

        $categories = CategoryModel::MakeTree(CategoryModel::Categories());
        $categories = array_column($categories, 'Name', 'CategoryID');
        $this->SetData('Categories', $categories);

        $this->View = 'addedit';
        $this->AddSideMenu();
        $this->Render();
    }

    public function edit() {
        if (!$this->site) {
            throw NotFoundException('Site');
        }

        $this->Title(sprintf(T('Edit %s'), T('Site')));
        $this->addedit();
    }

    public function delete() {
        if (!$this->site) {
            throw NotFoundException('Site');
        }

        if ($this->form->AuthenticatedPostBack()) {
            $this->siteModel->Delete(['SubcommunityID' => $this->site['SubcommunityID']]);

            if ($this->form->ErrorCount() == 0) {
                if ($this->DeliveryType() === DELIVERY_TYPE_VIEW) {
                    $this->JsonTarget('', '', 'Refresh');
                } elseif ($this->DeliveryType() === DELIVERY_TYPE_ALL) {
                    Redirect('/subcommunities');
                }
            }
        }

        $this->View = 'Delete';
        $this->Title(sprintf(T('Delete %s'), T('Site')));
        $this->Render();
    }

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
            $sites = $this->siteModel->search($search, 'Sort,Folder', 'asc', $limit + 1, $offset)->ResultArray();

            // Select 1 more than page size so we can know whether or not to display the next link.
            $this->setData('_CurrentRecords', count($sites));
            if (count($sites) > $limit) {
                $sites = array_slice($sites, 0, $pageSize);
            }

            $this->setData('Sites', $sites);
        } else {
            $where = [];
            $this->setData('Sites', $this->siteModel->GetWhere($where, 'Sort,Folder', 'asc', $limit, $offset)->ResultArray());
            $this->setData('RecordCount', $this->siteModel->GetCount($where));
        }

        $this->setData('_Limit', $pageSize);
        $this->AddJsFile('jquery.tablednd.js');
//        $this->AddJsFile('subcommunities_admin.js', 'plugins/subcommunities');

        $this->Title(T('Sites'));
        $this->AddSideMenu();
        $this->Render();
    }

    public function initialize() {
        parent::Initialize();

        $this->siteModel = SubcommunityModel::instance();
        $this->form = new Gdn_Form;

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

    protected function post() {
        if (!$this->Request->IsAuthenticatedPostBack()) {
            throw ForbiddenException('CSRF POST');
        }

        if ($this->site) {
            throw new Gdn_UserException('Site invalid when creating a site.');
        } else {
            $siteID = $this->siteModel->insert($this->Request->Post());
            if ($siteID) {
                $site = $this->siteModel->getID($siteID);
                $this->SetData('Site', $site);
            } else {
                $this->form->SetValidationResults($this->siteModel->Validation->Results());
            }
        }

        if ($this->form->ErrorCount() == 0) {
            if ($this->DeliveryType() === DELIVERY_TYPE_VIEW) {
                $this->JsonTarget('', '', 'Refresh');
            } elseif ($this->DeliveryType() === DELIVERY_TYPE_ALL) {
                Redirect('/subcommunities');
            }
        }

        $this->Render();
    }
}
