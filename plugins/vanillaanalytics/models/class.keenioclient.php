<?php

/**
 * keen.io client built on Garden HTTP
 * @package VanillaAnalytics
 */
class KeenIOClient extends Garden\Http\HttpClient {

    /**
     * The version of the API we're using.
     */
    const API_VERSION = '3.0';

    /**
     * The project ID we'll be recording events against.
     * @var string
     * @access protected
     */
    protected $projectID;

    /**
     * Scoped key for reading from the configured project.
     * @var string
     * @access protected
     */
    protected $readKey;

    /**
     * Scoped key for writing to the configured project.
     * @var string
     * @access protected
     */
    protected $writeKey;

    /**
     * Constructor.
     *
     * @param string $projectID ID of the project to record events against.
     * @param string $writeKey Scoped key for writing to the project.
     * @param bool|false $readKey Scoped key for reading from the project.
     */
    public function __construct($projectID, $writeKey, $readKey = false) {

        // Building the basics for our API interface
        parent::__construct('https://api.keen.io');
        $this->setDefaultHeader('Content-Type', 'application/json');
        $this->setThrowExceptions(true);

        $this->projectID = $projectID;
        $this->writeKey = $writeKey;
        $this->readKey = $readKey;
    }

    /**
     * Execute a command against a keen.io API endpoint.
     *
     * @param string $endpoint Target endpoint, without the host.
     * @param array $data Payload for API command.
     * @param string $authorization Value for the authorization header.
     * @return bool|stdClass Object representing result on success, false on failure.
     */
    protected function apiCommand($endpoint, $data, $authorization) {
        try {
            $result = $this->post(
                $endpoint,
                $data,
                [
                    'Authorization' => $authorization
                ]
            );

            return json_decode($result);
        } catch (Exception $e) {
            Logger::event('Vanilla Analytics Error', Logger::ERROR, $e->getMessage());
        }

        return false;
    }

    /**
     * Record an event against the currently configured project.
     *
     * @param $collection Name of the event collection to save the current event to.
     * @param $data Event data.
     * @return bool|stdClass Object representing result on success, false on failure.
     */
    public function event($collection, $data) {
        return $this->writeCommand(
            self::API_VERSION . "/projects/{$this->projectID}/events/{$collection}",
            $data
        );
    }

    /**
     * Perform a write command against the keen.io API.
     *
     * @param string $endpoint Target endpoint, without the host.
     * @param array $data Payload for the API command.
     * @return bool|stdClass Object representing result on success, false on failure.
     */
    protected function writeCommand($endpoint, $data) {
        return $this->apiCommand(
            $endpoint,
            $data,
            $this->writeKey
        );
    }
}
