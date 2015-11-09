<?php if (!defined('APPLICATION')) exit();

/**
 *
 * @copyright Copyright 2010 - 2015 Vanilla Forums Inc.
 * @license Proprietary
 */
$PluginInfo['WalkThrough'] = array(
    'Name' => 'Walk Through',
    'Description' => "Walks users through the features of the forum.",
    'Version' => '0.1',
    'RequiredApplications' => array('Vanilla' => '2.1a'),
    'Author' => 'Eric Vachaviolos',
    'AuthorEmail' => 'eric.v@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/evach',
    'MobileFriendly' => false
);

class WalkThroughPlugin extends Gdn_Plugin {

    private $tourName;
    private $tourConfig;

    /// Event Handlers.


    /**
     *
     * @param WalkThroughModule $Sender
     */
    public function WalkthroughModule_Init_Handler($Sender) {
        $Sender->setTour($this->tourName, $this->tourConfig);
    }

    /**
     *
     * @param Gdn_Controller $Sender
     * @param type $args
     */
    public function base_render_before($Sender, $args) {
        if ($Sender->MasterView == 'admin') {
            // Do not show on the admin section
            return;
        }

        if (!$this->shouldWeIncludeTheModule()) {
            return;
        }

        $Sender->addCssFile('introjs.min.css', 'plugins/WalkThrough');
        $Sender->addJsFile('intro.min.js', 'plugins/WalkThrough');
        $Sender->addModule('WalkThroughModule');
    }


    /// METHODS



    /**
     * This method should be used by the vfcom plugin to detect if it can push
     * a tour to the user
     *
     * @param int $UserID
     * @param string $TourName
     */
    public function shouldUserSeeTour($UserID, $TourName) {
        $User = Gdn::userModel()->getID($UserID);
        if (! $User) {
            return false;
        }

        $isTourCompleted = $this->getUserMeta($UserID, $this->getMetaKeyForCompleted($TourName), false, true);
        return ! $isTourCompleted;
    }


    /**
     * This method pushes a new tour to be displayed to the user
     *
     * @param string $TourName
     * @param array $TourConfig
     */
    public function loadTour($TourName, $TourConfig) {
        $this->tourName = $this->sanitizeTourName($TourName);
        $this->tourConfig = $TourConfig;
    }


    private function shouldWeIncludeTheModule() {
        if (is_null($this->tourConfig)) {
            return false;
        }

        if (! $this->shouldUserSeeTour(Gdn::session()->UserID, $this->tourName)) {
            return false;
        }
        if (Gdn::session()->getCookie('-intro_completed')) {
            $this->setUserMeta(Gdn::session()->UserID, $this->getMetaKeyForCompleted($this->tourName), true);
            return false;
        }
        return true;
    }


    private function sanitizeTourName($TourName) {
        return trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $TourName), '_');
    }


    private function getMetaKeyForCompleted($TourName) {
        return 'Completed_Tour_' . $this->sanitizeTourName($TourName);
    }


}
