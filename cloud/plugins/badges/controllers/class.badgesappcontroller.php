<?php
/**
 * @copyright 2011-2015 Vanilla Forums, Inc.
 * @package Badges
 */

/**
 * Base controller for Reputation app.
 *
 * @since 1.0.0
 */
class BadgesAppController extends Gdn_Controller {

    /* @var BadgeModel */
    public $BadgeModel;

    /**
     * Models to include.
     *
     * @since 1.0.0
     * @access public
     * @var array
     */
    public $Uses = ['Database', 'Form', 'BadgeModel', 'UserBadgeModel'];

    /**
     * This is a good place to include JS, CSS, and modules used by all methods of this controller.
     * Always called by dispatcher before controller's requested method.
     *
     * @since 1.0.0
     * @access public
     */
    public function initialize() {
        $this->Application = 'badges';

        $frontendStyle = false;

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
            $frontendStyle = $frontendStyle || $this->ControllerName == 'badgescontroller';

            if ($frontendStyle) {
                $this->addCssFile('style.css');
            } else {
                $this->addCssFile('admin.css');
            }

            // Reputation goodness
            $this->addCssFile('badges.css');
            $this->addJsFile('badges.js');
        }

        // Change master template
        if (!$frontendStyle) {
            $this->MasterView = 'admin';
        }

        // Call Gdn_Controller's initialize() as well.
        parent::initialize();
    }

    /**
     * Configures navigation sidebar in Dashboard.
     *
     * @since 1.0.0
     * @access public
     *
     * @param $currentUrl Path to current location in dashboard.
     */
    public function addSideMenu($currentUrl) {
        if ($currentUrl) {
            DashboardNavModule::getDashboardNav()->setHighlightRoute($currentUrl);
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
