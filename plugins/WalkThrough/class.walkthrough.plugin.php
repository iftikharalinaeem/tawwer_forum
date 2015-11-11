<?php if (!defined('APPLICATION')) { exit(); }

/**
 *
 * @copyright Copyright 2010-2015 Vanilla Forums Inc.
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

/**
 * WalkThrough Plugin
 *
 * Manage the display for tours provided by other plugins.
 *
 * @author Eric Vachaviolos <eric.v@vanillaforums.com>
 * @copyright 2010-2015 Vanilla Forums Inc.
 * @license Proprietary
 * @package internal
 * @subpackage WalkThrough
 * @since 2.0
 */
class WalkThroughPlugin extends Gdn_Plugin {

    private $tourName;
    private $tourConfig;

    private $options = array(
        'redirectEnabled' => true
    );

    /// Event Handlers.

    /**
     * Injects everything it needs to render a tour.
     * If there is no tour or we shouldn't display it at that time,
     * nothing will be injected
     *
     * @param Gdn_Controller $Sender
     */
    public function base_render_before($Sender) {
        // Do not display if the delivery method is not XHTML
        if ($Sender->deliveryMethod() != DELIVERY_METHOD_XHTML) {
            return;
        }

        if ($Sender->MasterView == 'admin') {
            // Do not show on the admin section
            return;
        }

        if (!$this->shouldWeDisplayTheTour()) {
            return;
        }

        $CurrentUrl = rtrim(htmlEntityDecode(url()), '&');
        $tourData = $this->loadTourMetaData();
        $currentStepIndex = val('stepIndex', $tourData, 0);
        // If possible and enabled, redirects to the page corresponding to the current step.
        if (isset($this->tourConfig[$currentStepIndex]['url'])) {
            if ($this->options['redirectEnabled'] && $this->tourConfig[$currentStepIndex]['url'] != $CurrentUrl) {
                redirectUrl($this->tourConfig[$currentStepIndex]['url']);
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

    /**
     * Saves the tour has being completed for the current user.
     *
     * @param string $tourName
     * @return boolean
     */
    public function setComplete($tourName) {

        $userID = (int) Gdn::session()->UserID;
        if ($userID <= 0 || ! $tourName) {
            return false;
        }

        $tourData = $this->loadTourMetaData();
        $runningTourName = val('name', $tourData);
        if ($runningTourName && $runningTourName != $tourName) {
            return false;
        }

        $this->setUserMeta($userID, 'TourData', null);
        $this->setUserMeta($userID, $this->getMetaKeyForCompleted($tourName), true);
        return true;
    }

    /**
     * Saves the current step index of the tour that the current user has just viewed.
     *
     * @param string $tourName
     * @param int $currentStep  The current step index
     * @return boolean
     */
    public function setCurrentStep($tourName, $currentStep) {
        $userID =  (int) Gdn::session()->UserID;
        if ($userID <= 0 || ! $tourName) {
            return false;
        }

        $tourData = array(
            'name' => $tourName,
            'stepIndex' => $currentStep
        );

        $this->setUserMeta($userID, 'TourData', json_encode($tourData));
        return true;
    }

    private function loadTourMetaData() {
        return json_decode($this->getUserMeta(Gdn::session()->UserID, 'TourData', '{}', true), true);
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
