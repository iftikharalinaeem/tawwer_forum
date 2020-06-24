<?php if (!defined('APPLICATION')) exit;

/**
 * Intercomio Plugin
 *
 * @author    Patrick Kelly <patrick.k@vanillaforums.com>
 * @copyright 2015 (c) Patrick Kelly
 * @license   GPLv3
 * @since     1.0.0
 */
class vfIntercomioPlugin extends Gdn_Plugin {
    /**
     * This will run when you "Enable" the plugin
     *
     * @since  1.0.0
     * @access public
     * @return bool
     */
    public function setup() {
        return true;
    }

    public function addJsEvent($eventName, $metadata = null) {
        if ($metadata) {
            $this->events[] = [$eventName, $metadata];
        } else {
            $this->events[] = [$eventName];
        }
    }

    public function trackEvent($eventName, $metadata = null) {
        $this->api()->track($eventName, $metadata);
    }


    public function createEvent($eventName, $metadata = null) {
        $this->api()->track($eventName, $metadata);
    }


    public function gdn_dispatcher_beforeControllerMethod_handler($sender, $args) {
        // Don't track page view event.
        if (!gdn::session()->checkPermission('Garden.Settings.Manage')) {
            return;
        }
        trace(array_keys($args), "Arguments available");
        trace($args['ControllerMethod'], "Controller Method");

        $controller = get_class($args['Controller']);
        $controller = strtolower(stringEndsWith($controller, 'Controller', false, true));
        $eventName = "{$controller}_{$args['ControllerMethod']}";

        $this->addJsEvent($controller);
        $this->addJsEvent($eventName);

        trace($eventName ,'Event Name');
        trace($controller, 'Controller');
    }

    /**
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_render_before($sender, $args) {
        $intercomio = [
            'siteID' => Infrastructure::siteID(),
            'events' => $this->events,
            'starttime' => strtotime(gdn::session()->User->DateInserted),
            'name' => gdn::session()->User->Name,
            'email' => gdn::session()->User->Email,
            'userID' => gdn::session()->User->UserID,
            'trackingPages' => 'Banner,Themes,Users,Roles & Permissions,Social,Categories,Plugins',
            'app_id' => c('Plugins.Intercomio.App_id', 0)
        ];

        $sender->addJsFile('intercomio.js', 'plugins/vfintercomio/js');
        $sender->addDefinition('intercomIO', $intercomio);
    }

    public function settingsController_vfintercomio_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');

        $cf = new ConfigurationModule($sender);

        $cf->initialize(
            [
                'Plugins.Intercomio.App_id' => []
            ]
        );

        $sender->addSideMenu();
        $sender->setData('Title', t('Intercom.io Settings'));
        $cf->renderAll();
    }
}
