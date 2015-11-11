<?php if (!defined('APPLICATION')) { exit(); }

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

    public function __construct() {
        parent::__construct();
        $this->plugin = Gdn::pluginManager()->getPluginInstance('WalkThroughPlugin', Gdn_PluginManager::ACCESS_CLASSNAME);
    }

    /**
     * Notifies that the tour has been completed
     */
    public function complete() {
        $tourName = Gdn::request()->post('TourName');

        // Delegate to the plugin
        $result = $this->plugin->setComplete($tourName);

        $this->renderData(array('Result' => $result));
    }

    /**
     * Notifies that the tour has been skipped
     */
    public function skip() {
        $tourName = Gdn::request()->post('TourName');

        // Delegate to the plugin
        $result = $this->plugin->setSkipped($tourName);

        $this->renderData(array('Result' => $result));
    }

    /**
     * Notifies which step is being viewed
     */
    public function currentStep() {
        $tourName = Gdn::request()->post('TourName');
        $currentStep = Gdn::request()->post('CurrentStep');

        // Delegate to the plugin
        $result = $this->plugin->setCurrentStep($tourName, $currentStep);

        $this->renderData(array('Result' => $result));
    }


}
