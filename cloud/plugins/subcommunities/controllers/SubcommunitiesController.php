<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\FeatureFlagHelper;
use Vanilla\Subcommunities\Models\ProductModel;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Contracts\ConfigurationInterface;
use Gdn_Router as Router;

/**
 * Controller for routing the /subcommunities
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

    /** @var ProductModel */
    private $productModel;

    /** @var SiteSectionModel $siteSectionModel */
    private $siteSectionModel;

    /** @var ConfigurationInterface $config */
    private $config;

    /** @var Router $router */
    private $router;

    /// Methods ///

    /**
     * DI.
     *
     * @param ProductModel $productModel
     * @param SiteSectionModel $siteSectionModel
     * @param ConfigurationInterface $config
     * @param Gdn_Router $router
     */
    public function __construct(
        ProductModel $productModel,
        SiteSectionModel $siteSectionModel,
        ConfigurationInterface $config,
        Router $router
    ) {
        parent::__construct();
        $this->productModel = $productModel;
        $this->siteSectionModel = $siteSectionModel;
        $this->config = $config;
        $this->router = $router;
    }

    public function add() {
        $this->title(sprintf(t('Add %s'), t('Site')));
        $this->addedit();
    }

    /**
     * Get the current Gdn_Form instance.
     *
     * @return Gdn_Form|null
     */
    public function getForm() {
        return $this->form;
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
            $value = $value === "" ? null : $value; // Normalize empty strings

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

        $layoutOptions = $this->siteSectionModel->getLayoutOptions();
        foreach ($layoutOptions as $key => &$value) {
            $value = t($value);
        }
        $configDefaultController = $this->config->get('Routes.DefaultController');

        $defaultHomepage = $this->router->parseRoute($configDefaultController)['Destination'];
        $default= sprintf(t('Default (%s)'), $layoutOptions[$defaultHomepage]);

        $layoutOptions = ['null' => $default] + $layoutOptions;
        $defaultController = ['LabelCode' => 'Homepage', 'Control' => 'DropDown', 'Items' => $layoutOptions];

        // Set the form elements on the add/edit form.
        $form = [
            'Name' => ['Description' => 'Enter a friendly name for the site.'],
            'Folder' => ['Description' => 'Enter a url-friendly folder name for the site.'],
            'ProductID' => ['Control' => 'react', 'Component' => 'product-selector-form-group'],
            'themeID' => ['Control' => 'react', 'Component' => 'subcommunity-theme-form-group'],
            'CategoryID' => ['LabelCode' => 'Category', 'Control' => 'DropDown', 'Items' => $this->data('Categories'), 'Options' => ['IncludeNull' => true]],
            'Locale' => ['Control' => 'DropDown', 'Items' => $this->data('Locales'), 'Options' => ['IncludeNull' => true]],
        ];

        $form['defaultController'] = $defaultController;

        $apps = $this->siteSectionModel->applications();
        if (count($apps)>1) {
            foreach ($apps as $app => $settings) {
                $form[$app] =[
                    'Control' => 'Toggle',
                    'LabelCode' => 'Disable '.$settings['name'] ?? $app,
                    'Description' => 'Disable application menu items and pages.'
                ];
            }
        }

        if (!FeatureFlagHelper::featureEnabled(ProductModel::FEATURE_FLAG)) {
            unset($form['ProductID']);
        }

        $this->EventArguments['Form'] =& $form;
        $this->EventArguments['Site'] =& $this->site;
        $this->fireEvent('addedit');

        $form['IsDefault'] =['Control' => 'Checkbox', 'LabelCode' => 'Default'];
        $this->setData('_Form', $form);

        if ($this->Request->isAuthenticatedPostBack()) {
            $postData = $this->saveForm($form, $this->Request->post());

            // Unchecked checkboxes are not sent in post data :P
            if (empty($postData['IsDefault'])) {
                $postData['IsDefault'] = null;
            }

            if ($postData['defaultController'] === 'null') {
                $postData['defaultController'] = null;
            }

            if (FeatureFlagHelper::featureEnabled(ProductModel::FEATURE_FLAG)) {
                $this->siteModel->validateProduct($postData);
            }

            $this->siteModel->validateApps($postData, $defaultHomepage);

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
                    redirectTo('/subcommunities');
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
                    redirectTo('/subcommunities');
                }
            }
        }

        $this->render('blank', 'utility', 'dashboard');
    }

    protected function getSite($siteID) {
        $this->site = $this->siteModel->getID($siteID);
    }

    /**
     * Serve the /subcommunities page.
     *
     * @param string $page The page number.
     */
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
        $pageSize = 100;
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
        } else {
            $where = [];
            $sites = $this->siteModel->getWhere($where, 'Sort,Folder', 'asc', $limit, $offset)->resultArray();
            $this->setData('RecordCount', $this->siteModel->getCount($where));
        }

        // Check if the product integration is enabled.
        if (FeatureFlagHelper::featureEnabled(ProductModel::FEATURE_FLAG)) {
            $this->productModel->expandProduct($sites);
            $this->setData('useProducts', true);
        } else {
            $this->setData('useProducts', false);
        }

        // Generate add & edit URLs
        $request = Gdn::request();
        foreach ($sites as &$site) {
            $id = $site['SubcommunityID'];
            $site['EditUrl'] = $request->url("/subcommunities/$id/edit");
            $site['DeleteUrl'] = $request->url("/subcommunities/$id/delete");
        }

        $this->setData('Sites', $sites);

        // Temporary until the dashboardSymbol from https://github.com/vanilla/vanilla/pull/9282 is merged.
        $this->setData('functionContainer', new FunctionContainer());

        $this->setData('_Limit', $pageSize);
        $this->addJsFile('jquery.tablednd.js');

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
                redirectTo('/subcommunities');
            }
        }

        $this->render();
    }
}

class FunctionContainer {
    public function dashboardSymbol(...$args) {
        $result = dashboardSymbol(...$args);
        return new \Twig\Markup($result, 'utf-8');
    }
}
