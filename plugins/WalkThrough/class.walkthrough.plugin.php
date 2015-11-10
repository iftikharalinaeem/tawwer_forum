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

    private $options = array(
        'redirectEnabled' => true
    );

    /// Event Handlers.

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

        if (!$this->shouldWeDisplayTheTour()) {
            return;
        }

        $CurrentUrl = rtrim(htmlEntityDecode(url()), '&');
        $CurrentStepNumber = Gdn::session()->getCookie('-intro_currentstep', 0);
        // If possible and enabled, redirects to the page corresponding to the current step.
        if (isset($this->tourConfig[$CurrentStepNumber]['url'])) {
            if ($this->options['redirectEnabled'] && $this->tourConfig[$CurrentStepNumber]['url'] != $CurrentUrl) {
                redirectUrl($this->tourConfig[$CurrentStepNumber]['url']);
            }
        }

        $options = array(
            'tourName' => $this->tourName,
            'steps' => $this->tourConfig
        );
        $Sender->addDefinition('Plugin.WalkThrough.Options', $options);
        $Sender->addCssFile('introjs.min.css', 'plugins/WalkThrough');
        $Sender->addJsFile('intro.min.js', 'plugins/WalkThrough');
        $Sender->addJsFile('walkthrough.js', 'plugins/WalkThrough');
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
        $this->setTourConfig($TourConfig);
    }


    private function shouldWeDisplayTheTour() {
        if (is_null($this->tourConfig)) {
            return false;
        }

        // Bail out if there are no steps to display!
        if (empty($this->tourConfig)) {
            return false;
        }

        if (! $this->shouldUserSeeTour(Gdn::session()->UserID, $this->tourName)) {
            return false;
        }

        $currentTourName = Gdn::session()->getCookie('-intro_tourname');
        if (Gdn::session()->getCookie('-intro_completed') && $currentTourName == $this->tourName) {
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

    private function setTourConfig($tourConfig) {
        $steps = $tourConfig;

        // adds the url property if needed
        foreach ($steps as $k => $dbStep) {
            $Page = val('page', $dbStep);
            if ($Page) {
                $steps[$k]['url'] = url($Page);
            }
        }

        $this->tourConfig = $steps;
    }
}
