<?php

/**
 * Handles the status of a tour.
 *
 * @author Eric Vachaviolos <eric.v@vanillaforums.com>
 * @copyright 2010-2015 Vanilla Forums Inc.
 * @license Proprietary
 * @package internal
 * @subpackage WalkThrough
 * @since 2.0
 */
class WalkthroughController extends PluginController {

    /**
     *
     * @var WalkThroughPlugin
     */
    private $plugin;

    /**
     * Instantiate objects
     */
    public function __construct() {
        parent::__construct();
        $this->plugin = WalkThroughPlugin::instance();
    }

    /**
     * Notifies that the tour has been completed
     */
    public function complete() {
        $tourName = Gdn::request()->post('TourName');

        // Delegate to the plugin
        $result = $this->plugin->setComplete($tourName);

        $this->EventArguments['TourName'] = $tourName;
        $this->fireEvent('completed');

        $this->renderData(['Result' => $result]);
    }

    /**
     * Notifies that the tour has been skipped
     */
    public function skip() {
        $tourName = Gdn::request()->post('TourName');

        $userID = Gdn::session()->UserID;
        $tourState = WalkThroughPlugin::instance()->getTourState($userID);

        // Delegate to the plugin
        $result = $this->plugin->setSkipped($tourName);

        $this->EventArguments['TourName'] = $tourName;
        $this->EventArguments['TourState'] = $tourState;
        $this->fireEvent('skipped');

        $this->renderData(['Result' => $result]);
    }

    /**
     * Notifies which step is being viewed
     */
    public function currentStep() {
        $tourName = Gdn::request()->post('TourName');
        $currentStep = Gdn::request()->post('CurrentStep');

        // Delegate to the plugin
        $result = $this->plugin->setCurrentStep($tourName, $currentStep);

        $this->renderData(['Result' => $result]);
    }


}
