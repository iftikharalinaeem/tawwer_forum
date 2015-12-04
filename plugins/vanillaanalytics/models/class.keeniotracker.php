<?php

/**
 * Responsible for managing communication with the keen.io service.
 */
class KeenIOTracker implements TrackerInterface {

    /**
     * Instance of KeenIOClient.
     * @var KeenIOClient
     */
    protected $client;

    /**
     * Scoped API read key for the current project.
     * @var string
     */
    protected $readKey;

    /**
     * Scoped API write key for the current project.
     * @var string
     */
    protected $writeKey;

    /**
     * Unique ID for the project we're tracking events against.
     * @var string
     */
    protected $projectID;

    /**
     * Constructor.
     */
    public function __construct() {
        // Load all necessary keys from the Vanilla config.
        $this->projectID = c('VanillaAnalytics.KeenIO.ProjectID');
        $this->writeKey = c('VanillaAnalytics.KeenIO.WriteKey');
        $this->readKey = c('VanillaAnalytics.KeenIO.ReadKey');

        $this->client = new KeenIOClient(
            'https://api.keen.io/{version}/',
            [
                'projectId' => $this->projectID,
                'writeKey'   => $this->writeKey,
                'readKey'  => $this->readKey
            ]
        );
    }

    /**
     * Add values to the gdn.meta JavaScript array on the page.
     *
     * @param Gdn_Controller Instance of the current page's controller.
     */
    public function addDefinitions(Gdn_Controller $controller) {
        $controller->addDefinition('keenio.projectID', $this->projectID);
        $controller->addDefinition('keenio.writeKey', $this->writeKey);
    }

    /**
     * Add JavaScript files to the current page.
     *
     * @param Gdn_Controller Instance of the current page's controller.
     */
    public function addJsFiles(Gdn_Controller $controller) {
        $controller->addJsFile('https://d26b395fwzu5fz.cloudfront.net/3.3.0/keen.min.js');
        $controller->addJsFile('keenio.min.js', 'plugins/vanillaanalytics');
    }

    /**
     * Record an event using the keen.io API.
     *
     * @param string $collection Name of the event collection to record this data to.
     * @param array $data Details of this event.
     * @return array Body of response from keen.io
     */
    public function event($collection, $data = array()) {
        return $this->client->addEvent($collection, $data);
    }
}
