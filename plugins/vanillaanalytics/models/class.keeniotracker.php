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
     * Constructor.
     */
    public function __construct() {
        // Load all necessary keys from the Vanilla config.
        $this->client = new KeenIOClient(
            c('VanillaAnalytics.KeenIO.ProjectID'),
            c('VanillaAnalytics.KeenIO.WriteKey'),
            c('VanillaAnalytics.KeenIO.ReadKey')
        );
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
