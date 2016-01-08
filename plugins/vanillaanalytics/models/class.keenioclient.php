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

    const COMMAND_MASTER = 'master';

    const COMMAND_ORG = 'org';

    const COMMAND_READ = 'read';

    const COMMAND_WRITE = 'write';

    const REQUEST_DELETE = 'delete';

    const REQUEST_GET = 'get';

    const REQUEST_POST = 'post';

    /**
     * Non-scoped key for the project, capable of reading and writing.
     * @var string
     */
    protected $masterKey;

    /**
     * Unique ID of the organization our projects belong to.
     * @var string
     */
    protected $orgID;

    /**
     * Key with capabilities for managing a keen.io organization.
     * @var string
     */
    protected $orgKey;

    /**
     * The project ID we'll be recording events against.
     * @var string
     */
    protected $projectID;

    /**
     * Scoped key for reading from the configured project.
     * @var string
     */
    protected $readKey;

    /**
     * Scoped key for writing to the configured project.
     * @var string
     */
    protected $writeKey;

    /**
     * Constructor.
     *
     * @param bool $baseUrl Not used here.  Added for compatibility with official keen.io library.
     * @param array $config Configuration values for API communication.
     */
    public function __construct($baseUrl, $config) {
        $default = array(
            'baseUrl'   => 'https://api.keen.io/{version}',
            'version'   => self::API_VERSION,
            'masterKey' => null,
            'orgID'     => null,
            'orgKey'    => null,
            'writeKey'  => null,
            'readKey'   => null,
            'projectID' => null
        );

        $config = array_merge($default, $config);
        $baseUrl = str_replace(
            '{version}',
            $config['version'],
            $config['baseUrl']
        );

        // Building the basics for our API interface
        parent::__construct($baseUrl);
        $this->setDefaultHeader('Content-Type', 'application/json');
        $this->setThrowExceptions(true);

        $this->setMasterKey($config['masterKey']);
        $this->setOrgID($config['orgID']);
        $this->setOrgKey($config['orgKey']);
        $this->setProjectID($config['projectID']);
        $this->setWriteKey($config['writeKey']);
        $this->setReadKey($config['readKey']);
    }

    /**
     * Record an event against the currently configured project.
     *
     * @param $eventCollection Name of the event collection to save the current event to.
     * @param $eventData Event data.
     * @return bool|stdClass Object representing result on success, false on failure.
     */
    public function addEvent($eventCollection, $eventData) {
        return $this->command(
            "projects/{$this->projectID}/events/{$eventCollection}",
            $eventData,
            self::COMMAND_WRITE
        );
    }

    public function addEvents($data) {
        return $this->command(
            "projects/{$this->projectID}/events",
            $data,
            self::COMMAND_WRITE
        );
    }

    public function addProject($name, array $users = []) {
        $data = [
            'name'  => $name,
            'users' => $users
        ];

        return $this->command(
            "organizations/{$this->orgID}/projects",
            $data,
            self::COMMAND_ORG
        );
    }

    /**
     * Execute a command against a keen.io API endpoint.
     *
     * @param string $endpoint Target endpoint, without the host.
     * @param array $data Payload for API command.
     * @param string $authorization Value for the authorization header.
     * @return bool|stdClass Object representing result on success, false on failure.
     */
    protected function command($endpoint, $data = [], $authorization = false, $requestMethod = self::REQUEST_POST) {
        $validMethods = [
            self::REQUEST_DELETE,
            self::REQUEST_GET,
            self::REQUEST_POST
        ];

        if (!in_array($requestMethod, $validMethods)) {
            return false;
        }

        $headers = [];

        if ($authorization) {
            switch ($authorization) {
                case self::COMMAND_MASTER:
                    $headers['Authorization'] = $this->getMasterKey();
                    break;
                case self::COMMAND_ORG:
                    $headers['Authorization'] = $this->getOrgKey();
                    break;
                case self::COMMAND_READ:
                    $headers['Authorization'] = $this->getReadKey();
                    break;
                case self::COMMAND_WRITE:
                    $headers['Authorization'] = $this->getWriteKey();
                    break;
            }
        }

        try {
            $result = $this->$requestMethod(
                $endpoint,
                $data,
                $headers
            );

            return json_decode($result);
        } catch (Exception $e) {
            Logger::event('Vanilla Analytics Error', Logger::ERROR, $e->getMessage());
        }

        return false;
    }

    public function deleteEvents() {}

    public function deleteEventProperties() {}

    /**
     * Returns available schema information for this event collection, including properties and their type. It also
     * returns links to sub-resources.
     *
     * @param $eventCollection
     * @return bool|stdClass
     */
    public function getCollection($eventCollection) {
        return $this->command(
            "projects/{$this->projectID}/events/{$eventCollection}",
            [],
            self::COMMAND_MASTER
        );
    }

    /**
     * Returns schema information for all the event collections in this project, including properties and their type.
     * It also returns links to sub-resources.
     *
     * @return bool|stdClass
     */
    public function getCollections() {
        return $this->command(
            "projects/{$this->projectID}/events",
            [],
            self::COMMAND_MASTER
        );
    }

    public function getEventSchemas() {
        return $this->getCollections();
    }

    public function getOrgID() {
        return $this->orgID;
    }

    public function getOrgKey() {
        return $this->orgKey;
    }

    public function getMasterKey() {
        return $this->masterKey;
    }

    /**
     * Returns the projects accessible to the API user, as well as links to project sub-resources for discovery.
     */
    public function getProject($projectID) {
        return $this->command(
            "projects/{$projectID}",
            [],
            self::COMMAND_MASTER
        );
    }

    public function getProjectID() {
        return $this->projectID;
    }

    /**
     * Returns the projects accessible to the API user, as well as links to project sub-resources for discovery.
     */
    public function getProjects() {
        return $this->command(
            "projects",
            [],
            self::COMMAND_MASTER
        );
    }

    /**
     * Returns the property name, type, and a link to sub-resources.
     *
     * @param $eventCollection
     * @param $propertyName
     * @return bool|stdClass
     */
    public function getProperty($eventCollection, $propertyName) {
        return $this->command(
            "projects/{$this->projectID}/events/{$eventCollection}/properties/{$propertyName}",
            [],
            self::COMMAND_MASTER
        );
    }

    /**
     * Returns the available child resources. Currently, the only child resource is the Projects Resource.
     */
    public function getResources() {
        return $this->command(
            "",
            [],
            self::COMMAND_MASTER
        );
    }

    public function getReadKey() {
        return $this->readKey;
    }

    public function getWriteKey() {
        return $this->writeKey;
    }

    public function setMasterKey($masterKey) {
        return $this->masterKey = $masterKey;
    }

    public function setOrgID($orgID) {
        return $this->orgID = $orgID;
    }

    public function setOrgKey($orgKey) {
        return $this->orgKey = $orgKey;
    }

    public function setProjectID($projectID) {
        return $this->projectID = $projectID;
    }

    public function setReadKey($readKey) {
        return $this->readKey = $readKey;
    }

    public function setWriteKey($writeKey) {
        return $this->writeKey = $writeKey;
    }
}
