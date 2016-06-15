<?php
/**
 * @copyright 2011-2015 Vanilla Forums, Inc.
 * @package Badges
 */
 
/**
 * Base controller for Reputation app.
 *
 * @since 1.0.0
 * @package Reputation
 */
class BadgesAppController extends Gdn_Controller {
    /*
     * @var BadgeModel
     */
    public $BadgeModel;


    /**
     * Models to include.
     *
     * @since 1.0.0
     * @access public
     * @var array
     */
    public $Uses = array('Database', 'Form', 'BadgeModel', 'UserBadgeModel');

    /**
     * This is a good place to include JS, CSS, and modules used by all methods of this controller.
     * Always called by dispatcher before controller's requested method.
     *
     * @since 1.0.0
     * @access public
     */
    public function initialize() {
        $this->Application = 'badges';

        $FrontendStyle = false;

        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            $this->Head = new HeadModule($this);

            // Vanilla goodness
            $this->addJsFile('jquery.js');
            $this->addJsFile('jquery.form.js');
            $this->addJsFile('jquery.popup.js');
            $this->addJsFile('jquery.gardenhandleajaxform.js');
            $this->addJsFile('global.js');
            $this->addJsFile('jquery.autogrow.js');
            $this->addJsFile('jquery.autocomplete.js');

            // When we use front end style instead of admin style
            $FrontendStyle = ($this->ControllerName == 'badgecontroller' && in_array($this->RequestMethod, array('Index', 'request')));
            $FrontendStyle = $FrontendStyle || $this->ControllerName == 'badgescontroller';

            if ($FrontendStyle) {
                $this->addCssFile('style.css');
            } else {
                $this->addCssFile('admin.css');
            }

            // Reputation goodness
            $this->addCssFile('badges.css');
            $this->addJsFile('badges.js');
        }

        // Change master template
        if (!$FrontendStyle) {
            $this->MasterView = 'admin';
        }

        // Call Gdn_Controller's Initialize() as well.
        parent::initialize();
    }

    /**
     * Configures navigation sidebar in Dashboard.
     *
     * @since 1.0.0
     * @access public
     *
     * @param $CurrentUrl Path to current location in dashboard.
     */
    public function addSideMenu($CurrentUrl) {
        Gdn_Theme::section('Dashboard');
        // Only add to the assets if this is not a view-only request
        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            $sideMenu = new SideMenuModule($this);

            // Add the heading here so that they sort properly.
            $sideMenu->addItem('Dashboard', t('Dashboard'), false, ['class' => 'Dashboard']);
            $sideMenu->addItem('Appearance', t('Appearance'), false, ['class' => 'Appearance']);
            $sideMenu->addItem('Users', t('Users'), false, ['class' => 'Users']);
            $sideMenu->addItem('Moderation', t('Moderation'), false, ['class' => 'Moderation']);

            // Hook for initial setup. Do NOT use this for addons.
            $this->EventArguments['SideMenu'] = $sideMenu;
            $this->fireEvent('earlyAppSettingsMenuItems');

            // Module setup.
            $sideMenu->HtmlId = '';
            $sideMenu->highlightRoute($CurrentUrl);
            $sideMenu->Sort = c('Garden.DashboardMenu.Sort');

            // Hook for adding to menu.
            $this->fireEvent('GetAppSettingsMenuItems');

            // Add the module
            $this->addModule($sideMenu, 'Panel');
        }
    }

    /**
     *
     */
    public function bomb() {
        $this->permission('Garden.Settings.Manage');
        $this->addJsFile('bomb.js');
        $this->addSideMenu('');
        $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Set view to 404.
     *
     * @since 1.0.0
     * @access public
     */
    public function setView404() {
        // Set view to 404 since one is required.
        $this->ApplicationFolder = 'dashboard';
        $this->ControllerName = 'Home';
        $this->View = 'FileNotFound';
    }
}
