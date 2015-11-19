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

    protected $readKey;

    protected $writeKey;

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
            $this->projectID,
            $this->writeKey,
            $this->readKey
        );
    }

    /**
     *
     */
    public function addDefinitions(Gdn_Controller $controller) {
        $controller->addDefinition('keenio.projectID', $this->projectID);
        $controller->addDefinition('keenio.writeKey', $this->writeKey);
    }

    /**
     *
     */
    public function addJsFiles(Gdn_Controller $controller) {
        $controller->addJsFile('https://d26b395fwzu5fz.cloudfront.net/3.3.0/keen.min.js');
    }

    /**
     * Record an event using the keen.io API.
     *
     * @param string $collection Name of the event collection to record this data to.
     * @param array $data Details of this event.
     */
    public function event($collection, $data = array()) {
        echo $this->client->event($collection, $data);
    }
}
