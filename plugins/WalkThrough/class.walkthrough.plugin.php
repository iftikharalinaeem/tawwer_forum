<?php

/**
 * @copyright Copyright 2010-2015 Vanilla Forums Inc.
 * @license Proprietary
 */

// Defines the plugin:
$PluginInfo['WalkThrough'] = [
    'Name' => 'Walk Through',
    'Description' => "Allow other plugins to display custom tours to users.",
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

    /**
     * The name of the loaded tour.
     *
     * @var string
     */
    private $tourName;

    /**
     * The config array of the loaded tour.
     *
     * @var array
     */
    private $tourConfig;

    /**
     * The options of the loaded tour.
     *
     * @var array
     */
    private $tourOptions;

    /**
     * Keep track of all requested tour names during a request.
     *
     * @var array
     */
    private $requestedTourNames = [];

    /**
     * Event triggered before rendering.
     *
     * Injects everything it needs to render a tour.
     * If there is no tour or we shouldn't display it at that time,
     * nothing will be injected
     *
     * @param Gdn_Controller $sender
     */
    public function base_render_before($sender) {
        // Unblocks the user stuck on a tour which is not requested anymore.
        // The next tour (if any), will be available on the next request.
        //
        // IMPORTANT: This needs to be called as late as possible in order to
        // be aware of all the tours other plugins wants to push
        $this->cleanupOldTour();

        // Do not display if the delivery method is not XHTML
        if ($sender->deliveryMethod() != DELIVERY_METHOD_XHTML) {
            return;
        }

        if (!$this->shouldWeDisplayTheTour()) {
            return;
        }

        $tourState = $this->loadTourState(Gdn::session()->UserID);
        $currentStepIndex = val('stepIndex', $tourState, 0);

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

    /**
     * This method should be used by the vfcom plugin to detect if it can push
     * a tour to the user
     *
     * @param int $userID
     * @param string $tourName
     */
    public function shouldUserSeeTour($userID, $tourName) {
        // Bail out if invalid userID
        if ($userID <= 0) {
            return false;
        }

        $this->requestedTourNames[$tourName] = true;

        $tourState = $this->loadTourState($userID);
        $runningTourName = val('name', $tourState);
        if ($runningTourName && $runningTourName != $tourName) {
            // A user must finish a tour before seeing a different one
            return false;
        }

        $isTourCompleted = $this->getUserMeta($userID, $this->getMetaKeyForCompleted($tourName), false, true);
        return !$isTourCompleted;
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

        $userID = Gdn::session()->UserID;

        $tourName = $tourConfig['name'];

        if (!$this->shouldUserSeeTour($userID, $tourName)) {
            return false;
        }

        $this->tourName = $tourName;
        $this->setTourConfig($tourConfig);

        $tourState = $this->loadTourState($userID);

        // Setup the tour state if it's the first time we load this tour.
        // This way, tours are treated first come, first serve
        if (empty($tourState)) {
            $tourState = [
                'name' => $this->tourName,
                'stepIndex' => 0
            ];

            $this->persistTourState($userID, $tourState);

        }
    }

    /**
     * Reset the tour for a specific user.
     *
     * It will reset a tour that has been completed by someone,
     * or that was already started but not finished yet.
     *
     * @param int $userID       The user id for which we want to reset the tour
     * @param string $tourName  The name of the tour to reset
     */
    public function resetTour($userID, $tourName) {
        $this->setUserMeta($userID, $this->getMetaKeyForCompleted($tourName));


        $tourState = $this->loadTourState($userID);
        if (val('name', $tourState) == $tourName) {
            $this->deleteTourState($userID);
        }
    }

    /**
     * Validate a tour.
     *
     * It makes sure the attributes `steps` and `name` are provided and valid,
     * otherwise it returns false.
     *
     * @param array $tourConfig The config array to validate
     * @return boolean
     */
    public function validateTourConfig($tourConfig) {
        if (!is_array($tourConfig)) {
            return false;
        }

        if (!isset($tourConfig['steps']) || !isset($tourConfig['name'])) {
            return false;
        }

        if (!is_array($tourConfig['steps'])) {
            return false;
        }

        foreach ($tourConfig['steps'] as $step) {
            if (!is_array($step)) {
                return false;
            }

            // Attribute `intro` is required and must be a string
            if (!isset($step['intro']) || !is_string($step['intro'])) {
                return false;
            }

            // Attribute `page` if provided, must be a string
            if (isset($step['page']) && !is_string($step['page'])) {
                return false;
            }
        }

        // Attribute `options` if provided, must be an array
        if (isset($tourConfig['options']) && !is_array($tourConfig['options'])) {
            return false;
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
        if (!Gdn::session()->isValid()) {
            return false;
        }
        $userID = (int) Gdn::session()->UserID;
        $tourState = $this->loadTourState($userID);
        $runningTourName = val('name', $tourState);
        if ($runningTourName && $runningTourName != $tourName) {
            return false;
        }

        $this->deleteTourState($userID);
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
     * @param int $currentStepIndex  The current step index
     * @return boolean
     */
    public function setCurrentStep($tourName, $currentStepIndex) {
        $userID =  (int) Gdn::session()->UserID;
        if ($userID <= 0 || !$tourName) {
            return false;
        }

        // Preventing bad input
        if (!is_numeric($currentStepIndex) || $currentStepIndex < 0) {
            return false;
        }

        $tourData = [
            'name' => $tourName,
            'stepIndex' => $currentStepIndex
        ];

        $this->persistTourState($userID, $tourData);
        return true;
    }

    /**
     * Check if we can display the loaded tour.
     *
     * @return boolean
     */
    private function shouldWeDisplayTheTour() {
        if (is_null($this->tourConfig)) {
            return false;
        }

        // Bail out if there are no steps to display!
        if (empty($this->tourConfig)) {
            return false;
        }

        if (!$this->shouldUserSeeTour(Gdn::session()->UserID, $this->tourName)) {
            return false;
        }

        return true;
    }

    /**
     * Get the meta key corresponding to a completed tour.
     *
     * @param string $tourName The tour name to generate a key for.
     * @return string The formatted meta key.
     */
    private function getMetaKeyForCompleted($tourName) {
        return 'Tours.'.md5($tourName).'.Completed';
    }

    /**
     * Set the tour configurations.
     *
     * Also transforms the page attributes to corresponding URLs.
     *
     * @param array $tourConfig
     */
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

        $tourState = $this->loadTourState($userID);
        $tourName = val('name', $tourState);
        if (!isset($this->requestedTourNames[$tourName])) {
            $this->deleteTourState($userID);
        }
    }

    /**
     * Load the tour state from user metadata.
     *
     * @param int $userID
     * @return array Returns an array describing the tour state.  Defaults to empty array if not found
     */
    private function loadTourState($userID) {
        return json_decode($this->getUserMeta($userID, 'TourState', '{}', true), true);
    }

    /**
     * Delete the tour state for a user.
     *
     * @param int $userID
     */
    private function deleteTourState($userID) {
        $this->setUserMeta($userID, 'TourState', null);
    }

    /**
     * Save the tour state.
     *
     * @param int $userID
     * @param array $state The array describing the tour state.
     */
    private function persistTourState($userID, $state) {
        $this->setUserMeta($userID, 'TourState', json_encode($state));
    }
}
