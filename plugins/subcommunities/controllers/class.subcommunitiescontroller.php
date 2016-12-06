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

    /**
     * @param array $form
     */
    private function saveForm($form) {
        $result = [];
        foreach ($form as $field => $row) {
            if (strcasecmp(val('Control', $row), 'imageupload') === 0) {
                $this->form->saveImage($field, ['Prefix' => 'subcommunities/']);
            }
            $value = $this->form->getFormValue($field);

            if (strpos($field, 'Config.') === 0) {
                setvalr($field, $result, $value);
            } else {
                $result[$field] = $value;
            }
        }

        return $result;
    }

    protected function addedit() {
        $this->permission('Garden.Settings.Manage');

        $localeModel = new LocaleModel();
        $locales = $localeModel->enabledLocalePacks(true);
        $locales = array_column($locales, 'Locale', 'Locale');
        $locales = array_combine($locales, $locales);
        $locales = array_replace(['en' => 'en'], $locales);
        $this->setData('Locales', $locales);

        $categories = Gdn::sql()->getWhere('Category', ['Depth' => 1], 'Name')->resultArray();
        $categories = array_column($categories, 'Name', 'CategoryID');
        $this->setData('Categories', $categories);

        // Set the form elements on the add/edit form.
        $form = [
            'Name' => ['Description' => 'Enter a friendly name for the site.'],
            'Folder' => ['Description' => 'Enter a url-friendly folder name for the site.'],
            'CategoryID' => ['LabelCode' => 'Category', 'Control' => 'DropDown', 'Items' => $this->data('Categories'), 'Options' => ['IncludeNull' => true]],
            'Locale' => ['Control' => 'DropDown', 'Items' => $this->data('Locales'), 'Options' => ['IncludeNull' => true]],
        ];

        $this->EventArguments['Form'] =& $form;
        $this->fireEvent('addedit');

        $form['IsDefault'] =['Control' => 'Checkbox', 'LabelCode' => 'Default'];
        $this->setData('_Form', $form);

        if ($this->Request->isAuthenticatedPostBack()) {
            $postData = $this->saveForm($form, $this->Request->post());

            // Unchecked checkboxes are not sent in post data :P
            if (empty($postData['IsDefault'])) {
                $postData['IsDefault'] = null;
            }

            if ($this->site) {
                $siteID = $this->site['SubcommunityID'];

                $this->siteModel->update($postData, ['SubcommunityID' => $siteID]);
            } else {
                $siteID = $this->siteModel->insert($postData);
            }

            $this->form->setValidationResults($this->siteModel->Validation->results());

            if ($siteID) {
                $site = $this->siteModel->getID($siteID);
                $this->setData('Site', $site);
            }

            if ($this->form->errorCount() == 0) {
                if ($this->deliveryType() === DELIVERY_TYPE_VIEW) {
                    $this->jsonTarget('', '', 'Refresh');
                } elseif ($this->deliveryType() === DELIVERY_TYPE_ALL) {
                    redirect('/subcommunities');
                }
            }
        } elseif ($this->site) {
            $site = $this->site;
            if (array_key_exists('Config', $site) && is_array($site['Config'])) {
                $site = array_replace($site, flattenArray('.', ['Config' => $site['Config']]));
            }
            $this->form->setData($site);
        }

        foreach ($form as $row) {
            if (strcasecmp(val('Control', $row), 'imageupload')) {
                $this->setData('_HasFile', true);
            }
        }

        $this->View = 'addedit';
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
                $this->informMessage(t('Site deleted.'));
                if ($this->deliveryType() === DELIVERY_TYPE_VIEW) {
                    $this->jsonTarget('', '', 'Refresh');
                } elseif ($this->deliveryType() === DELIVERY_TYPE_ALL) {
                    redirect('/subcommunities');
                }
            }
        }

        $this->render('blank', 'utility', 'dashboard');
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
        $this->form = new Gdn_Form('', 'bootstrap');
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
        $this->render();
    }

    public function initialize() {
        parent::initialize();

        $this->siteModel = SubcommunityModel::instance();
        $this->form = new Gdn_Form('', 'bootstrap');

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
