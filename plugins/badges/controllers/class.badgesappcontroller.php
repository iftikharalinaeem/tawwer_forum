<?php
/**
 * Reputation Controller.
 *
 * @package Reputation
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
    public function Initialize() {
        $FrontendStyle = false;

        if ($this->DeliveryType() == DELIVERY_TYPE_ALL) {
            $this->Head = new HeadModule($this);

            // Vanilla goodness
            $this->AddJsFile('jquery.js');
            $this->AddJsFile('jquery.livequery.js');
            $this->AddJsFile('jquery.form.js');
            $this->AddJsFile('jquery.popup.js');
            $this->AddJsFile('jquery.gardenhandleajaxform.js');
            $this->AddJsFile('global.js');
            $this->AddJsFile('jquery.autogrow.js');
            $this->AddJsFile('jquery.autocomplete.js');

            // When we use front end style instead of admin style
            $FrontendStyle = ($this->ControllerName == 'badgecontroller' && in_array($this->RequestMethod, array('Index', 'request')));
            $FrontendStyle = $FrontendStyle || $this->ControllerName == 'badgescontroller';

            if ($FrontendStyle) {
                $this->AddCssFile('style.css');
            } else {
                $this->AddCssFile('admin.css');
            }

            // Reputation goodness
            $this->AddCssFile('badges.css');
            $this->AddJsFile('badges.js');
        }

        // Change master template
        if (!$FrontendStyle) {
            $this->MasterView = 'admin';
        }

        // Call Gdn_Controller's Initialize() as well.
        parent::Initialize();
    }

    /**
     * Configures navigation sidebar in Dashboard.
     *
     * @since 1.0.0
     * @access public
     *
     * @param $CurrentUrl Path to current location in dashboard.
     */
    public function AddSideMenu($CurrentUrl) {
        Gdn_Theme::Section('Dashboard');
        // Only add to the assets if this is not a view-only request
        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            $SideMenu = new SideMenuModule($this);
            $SideMenu->HtmlId = '';
            $SideMenu->HighlightRoute($CurrentUrl);
            $SideMenu->Sort = C('Garden.DashboardMenu.Sort');
            $this->EventArguments['SideMenu'] = &$SideMenu;
            $this->FireEvent('GetAppSettingsMenuItems');
            $this->AddModule($SideMenu, 'Panel');
        }
    }

    public function Bomb() {
        $this->Permission('Garden.Settings.Manage');

        $this->AddJsFile('bomb.js');

        $this->AddSideMenu('');
        $this->Render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Set view to 404.
     *
     * @since 1.0.0
     * @access public
     */
    public function SetView404() {
        // Set view to 404 since one is required.
        $this->ApplicationFolder = 'dashboard';
        $this->ControllerName = 'Home';
        $this->View = 'FileNotFound';
    }
}
