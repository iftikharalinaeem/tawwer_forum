<?php

/**
 * @copyright Copyright 2010-2015 Vanilla Forums Inc.
 * @license Proprietary
 */

// Defines the plugin:
$PluginInfo['WalkThrough'] = [
    'Name' => 'Walk Through',
    'Description' => "Walks users through the features of the forum.",
    'Version' => '0.1',
    'RequiredApplications' => ['Vanilla' => '2.1a'],
    'Author' => 'Eric Vachaviolos',
    'AuthorEmail' => 'eric.v@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/evach',
    'MobileFriendly' => false
];

/**
 * WalkThrough Plugin
 *
 * Manage the display for tours provided by other plugins.
 *
 * @author Eric Vachaviolos <eric.v@vanillaforums.com>
 * @package internal
 * @subpackage WalkThrough
 * @since 2.0
 */
class WalkThroughPlugin extends Gdn_Plugin {

    private $tourName;
    private $tourConfig;
    private $tourOptions;

    private $requestedTourNames = [];

    private $options = [
        'redirectEnabled' => true
    ];

    /// Event Handlers.

    /**
     * Injects everything it needs to render a tour.
     * If there is no tour or we shouldn't display it at that time,
     * nothing will be injected
     *
     * @param Gdn_Controller $sender
     */
    public function base_render_before($sender) {
        // Unblocks the user stuck on a tour which is not requested anymore.
        // The next tour (if any), will be available on the next request
        $this->cleanupOldTour();

        // Do not display if the delivery method is not XHTML
        if ($sender->deliveryMethod() != DELIVERY_METHOD_XHTML) {
            return;
        }

        if (!$this->shouldWeDisplayTheTour()) {
            return;
        }

        $tourData = $this->loadTourMetaData();
        $currentStepIndex = val('stepIndex', $tourData, 0);

        // If possible and enabled, redirects to the page corresponding to the current step.
        if ($this->options['redirectEnabled']) {
            $stepPath = val('page', $this->tourConfig[$currentStepIndex]);
            if ($stepPath && $stepPath != Gdn::request()->path()) {
                redirectUrl(url($stepPath));
            }
        }

        $options = array_merge($this->tourOptions, [
            'tourName' => $this->tourName,
            'steps' => $this->tourConfig,
            'currentStepIndex' => $currentStepIndex
        ]);
        $sender->addDefinition('Plugin.WalkThrough.Options', $options);
        $sender->addCssFile('introjs.min.css', 'plugins/WalkThrough');
        $sender->addJsFile('intro.min.js', 'plugins/WalkThrough');
        $sender->addJsFile('walkthrough.js', 'plugins/WalkThrough');

        // Attach a custom css file if provided
        $cssFile = val('cssFile', $this->tourOptions);
        if ($cssFile) {
            $sender->addCssFile($cssFile);
        }
    }



    /// METHODS

    /**
     * This method should be used by the vfcom plugin to detect if it can push
     * a tour to the user
     *
     * @param int $userID
     * @param string $tourName
     */
    public function shouldUserSeeTour($userID, $tourName) {
        $User = Gdn::userModel()->getID($userID);
        if (! $User) {
            return false;
        }

        $tourName = $this->sanitizeTourName($tourName);

        $this->requestedTourNames[$tourName] = true;

        $tourData = $this->loadTourMetaData();
        $runningTourName = val('name', $tourData);
        if ($runningTourName && $runningTourName != $tourName) {
            // A user must finish a tour before seeing a different one
            return false;
        }

        $isTourCompleted = $this->getUserMeta($userID, $this->getMetaKeyForCompleted($tourName), false, true);
        return ! $isTourCompleted;
    }


    /**
     * This method pushes a new tour to be displayed to the user
     *
     * @param array $tourConfig
     */
    public function loadTour($tourConfig) {
        if (!$this->validateTourConfig($tourConfig)) {
            return false;
        }

        $tourName = $this->sanitizeTourName($tourConfig['name']);

        if (! $this->shouldUserSeeTour(Gdn::session()->UserID, $tourName)) {
            return false;
        }

        $this->tourName = $tourName;
        $this->setTourConfig($tourConfig);

        $tourData = $this->loadTourMetaData();

        // Setup the tour metadata if it's the first time we load this tour.
        // This way, tours are treated first come, first serve
        if (empty($tourData)) {
            $tourData = [
                'name' => $this->tourName,
                'stepIndex' => 0
            ];

            $this->setUserMeta(Gdn::session()->UserID, 'TourData', json_encode($tourData));
        }
    }

    public function validateTourConfig($tourConfig) {
        if (!is_array($tourConfig)) {
            return false;
        }

        if (!isset($tourConfig['steps']) || !isset($tourConfig['name'])) {
            return false;
        }

        foreach ($tourConfig['steps'] as $step) {
            if (!is_array($step)) {
                return false;
            }

            if (!isset($step['intro'])) {
                return false;
            }
        }

        return true;
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
     * Saves the tour has being skipped for the current user.
     *
     * @param string $tourName
     * @return boolean
     */
    public function setSkipped($tourName) {
        // Currently, there's no distinction between complete or skipped
        return $this->setComplete($tourName);
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

        $tourData = [
            'name' => $tourName,
            'stepIndex' => $currentStep
        ];

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
        $steps = $tourConfig['steps'];

        // adds the url property if needed
        foreach ($steps as $k => $dbStep) {
            $Page = val('page', $dbStep);
            if ($Page) {
                $steps[$k]['url'] = url($Page);
            }
        }

        $this->tourOptions = val('options', $tourConfig, []);
        $this->tourConfig = $steps;
    }

    /**
     * If the current user is watching a tour that is not requested
     * anymore, we need to remove the UserMetadata so that we can push
     * a new tour to that user
     */
    private function cleanupOldTour() {
        $userID = Gdn::session()->UserID;
        if ($userID <= 0) {
            return;
        }

        $tourData = $this->loadTourMetaData();
        $tourName = val('name', $tourData);
        if (!isset($this->requestedTourNames[$tourName])) {
            $this->setUserMeta($userID, 'TourData', null);
        }
    }
}
