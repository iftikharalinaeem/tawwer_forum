<?php
/**
 * KeenIOClient class file.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 * @package vanillaanalytics
 */

use Garden\Http\HttpResponse;

/**
 * keen.io client built on Garden HTTP
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

    private const REQUEST_TIMEOUT = 15;

    /**
     * @link https://keen.io/docs/api/#master-key
     * @var string Non-scoped key for the project, capable of reading and writing.
     */
    protected $masterKey;

    /**
     * @var string Unique ID of the organization our projects belong to.
     */
    protected $orgID;

    /**
     * @link https://keen.io/docs/api/#organization-key
     * @var string Key with capabilities for managing a keen.io organization.
     */
    protected $orgKey;

    /**
     * @var string The project ID we'll be recording events against.
     */
    protected $projectID;

    /**
     * @link https://keen.io/docs/api/#read-key
     * @var string Scoped key for reading from the configured project.
     */
    protected $readKey;

    /**
     * @link https://keen.io/docs/api/#write-key
     * @var string Scoped key for writing to the configured project.
     */
    protected $writeKey;

    /**
     * Constructor.
     *
     * @param bool $baseUrl Not used here.  Added for compatibility with official keen.io library.
     * @param array $config Configuration values for API communication.
     */
    public function __construct($baseUrl, $config) {
        $default = [
            'baseUrl' => 'https://api.keen.io/{version}',
            'version' => self::API_VERSION,
            'masterKey' => null,
            'orgID' => null,
            'orgKey' => null,
            'writeKey' => null,
            'readKey' => null,
            'projectID' => null
        ];

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

        $this->setDefaultOption("timeout", self::REQUEST_TIMEOUT);
    }

    /**
     * Record an event against the currently configured project.
     *
     * @link https://keen.io/docs/api/#record-a-single-event
     * @param string $eventCollection Name of the event collection to save the current event to.
     * @param array $eventData Event data.
     * @return array|bool Array of result details on success, false on failure.
     */
    public function addEvent($eventCollection, $eventData) {
        try {
            $result = $this->command(
                "projects/{$this->projectID}/events/{$eventCollection}",
                $eventData,
                self::COMMAND_WRITE
            );
        } catch (Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Record multiple events against the currently configured project.
     *
     * @link https://keen.io/docs/api/#record-multiple-events
     * @param array $data Data for multiple events, grouped by collection.
     * @return array|bool Array of result details on success, false on failure.
     */
    public function addEvents(array $data) {
        try {
            $result = $this->command(
                "projects/{$this->projectID}/events",
                $data,
                self::COMMAND_WRITE
            );
        } catch (Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Create a new project in the configured organization.
     *
     * @link https://keen.io/docs/api/#create-project
     * @param string $name Specifies the name of the project to be created.
     * @param array $users Specifies users for the project.
     * @return array|bool Array of result details on success, false on failure.
     */
    public function addProject($name, array $users = []) {
        $data = [
            'name'  => $name,
            'users' => $users
        ];

        try {
            $result = $this->command(
                "organizations/{$this->orgID}/projects",
                $data,
                self::COMMAND_ORG
            );
        } catch (Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Execute a command against a keen.io API endpoint.
     *
     * @param string $endpoint Target endpoint, without the host.
     * @param array $data Payload for API command.
     * @param string|bool $authorization Value for the authorization header.
     * @param string $requestMethod Method to use for the request. Should be one of the REQUEST_* constants.
     * @return array|bool Array of result details on success, false on failure.
     * @throws Exception if an error is encountered while performing the request.
     */
    public function command($endpoint, $data = [], $authorization = false, $requestMethod = self::REQUEST_POST) {
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
        } catch (Exception $e) {
            if (debug()) {
                print_r('DEBUG:'. $e->getMessage());
            }
            Logger::event('vanilla_analytics', Logger::ERROR, $e->getMessage());
            throw $e;
        }

        if ($result instanceof HttpResponse) {
            $response = $result->getBody();
            $result = is_array($response) ? $response : false;
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * Delete an event or subset of an event collection.
     *
     * @link https://keen.io/docs/api/#delete-events
     * @todo Implement this, if we ever need it.
     */
    public function deleteEvents() {}

    /**
     * Remove a property from an event collection.
     *
     * @link https://keen.io/docs/api/#delete-a-property
     * @todo Implement this, if we ever need it.
     */
    public function deleteEventProperties() {}

    /**
     * Returns available schema information for this event collection, including properties and their type. It also
     * returns links to sub-resources.
     *
     * @param string $eventCollection
     * @return array|bool Array of result details on success, false on failure.
     */
    public function getCollection($eventCollection) {
        try {
            $result = $this->command(
                "projects/{$this->projectID}/events/{$eventCollection}",
                [],
                self::COMMAND_MASTER
            );
        } catch (Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Returns schema information for all the event collections in this project, including properties and their type.
     * It also returns links to sub-resources.
     *
     * @link https://keen.io/docs/api/#inspect-all-collections
     * @return array|bool Array of result details on success, false on failure.
     */
    public function getCollections() {
        try {
            $result = $this->command(
                "projects/{$this->projectID}/events",
                [],
                self::COMMAND_MASTER
            );
        } catch (Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Return schema information for all the event collections.
     *
     * @link https://keen.io/docs/api/#inspect-all-collections
     * @return bool|stdClass
     */
    public function getEventSchemas() {
        return $this->getCollections();
    }

    /**
     * Grab the organization ID.
     *
     * @return string The currently configured organization ID.
     */
    public function getOrgID() {
        return $this->orgID;
    }

    /**
     * Grab the organization-level API key.
     *
     * @return string The currently configured organization-level API key.
     */
    public function getOrgKey() {
        return $this->orgKey;
    }

    /**
     * Grab the project-level master API key.
     *
     * @return string The currently configured master key.
     */
    public function getMasterKey() {
        return $this->masterKey;
    }

    /**
     * Returns the projects accessible to the API user, as well as links to project sub-resources for discovery.
     *
     * @param string $projectID A keen.io project's unique ID.
     * @return array|bool Array of result details on success, false on failure.
     */
    public function getProject($projectID) {
        try {
            $result = $this->command(
                "projects/{$projectID}",
                [],
                self::COMMAND_MASTER
            );
        } catch (Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Grab the current project ID.
     *
     * @return string The currently configured project ID.
     */
    public function getProjectID() {
        return $this->projectID;
    }

    /**
     * Returns the projects accessible to the API user, as well as links to project sub-resources for discovery.
     *
     * @return array|bool Array of result details on success, false on failure.
     */
    public function getProjects() {
        try {
            $result = $this->command(
                "projects",
                [],
                self::COMMAND_MASTER
            );
        } catch (Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Returns the property name, type, and a link to sub-resources.
     *
     * @param $eventCollection
     * @param $propertyName
     * @return array|bool Array of result details on success, false on failure.
     */
    public function getProperty($eventCollection, $propertyName) {
        try {
            $result = $this->command(
                "projects/{$this->projectID}/events/{$eventCollection}/properties/{$propertyName}",
                [],
                self::COMMAND_MASTER
            );
        } catch (Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Returns the available child resources. Currently, the only child resource is the Projects Resource.
     *
     * @return array|bool Array of result details on success, false on failure.
     */
    public function getResources() {
        try {
            $result = $this->command(
                "",
                [],
                self::COMMAND_MASTER
            );
        } catch (Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Fetch the currently-configured API read key.
     *
     * @return string
     */
    public function getReadKey() {
        return $this->readKey;
    }

    /**
     * Fetch the currently-configured API write key.
     *
     * @return string
     */
    public function getWriteKey() {
        return $this->writeKey;
    }

    /**
     * Set the new master API key.
     *
     * @param $masterKey The new project-level master API key.
     * @return $this
     */
    public function setMasterKey($masterKey) {
        $this->masterKey = $masterKey;
        return $this;
    }

    /**
     * Set the new organization ID.
     *
     * @param string $orgID The new organization ID.
     * @return $this
     */
    public function setOrgID($orgID) {
        $this->orgID = $orgID;
        return $this;
    }

    /**
     * Set the new organization API key.
     *
     * @param string $orgKey The new organization-level API key.
     * @return $this
     */
    public function setOrgKey($orgKey) {
        $this->orgKey = $orgKey;
        return $this;
    }

    /**
     * Set the new project ID.
     *
     * @param string $projectID The new keen.io project unique identifier.
     * @return $this
     */
    public function setProjectID($projectID) {
        $this->projectID = $projectID;
        return $this;
    }

    /**
     * Set the new read API key.
     *
     * @param string $readKey The new project-level read API key.
     * @return $this
     */
    public function setReadKey($readKey) {
        $this->readKey = $readKey;
        return $this;
    }

    /**
     * Set the new write API key.
     *
     * @param string $writeKey The new project-level write API key.
     * @return $this
     */
    public function setWriteKey($writeKey) {
        $this->writeKey = $writeKey;
        return $this;
    }
}
