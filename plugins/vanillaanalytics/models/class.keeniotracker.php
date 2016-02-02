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
        $this->client = new KeenIOClient(
            'https://api.keen.io/{version}/',
            [
                'orgID'      => c('VanillaAnalytics.KeenIO.OrgID'),
                'orgKey'     => c('VanillaAnalytics.KeenIO.OrgKey'),
                'projectID'  => c('VanillaAnalytics.KeenIO.ProjectID'),
                'readKey'    => c('VanillaAnalytics.KeenIO.ReadKey'),
                'writeKey'   => c('VanillaAnalytics.KeenIO.WriteKey')
            ]
        );
    }

    /**
     * Add values to the gdn.meta JavaScript array on the page.
     *
     * @param Gdn_Controller Instance of the current page's controller.
     */
    public function addDefinitions(Gdn_Controller $controller) {
        $controller->addDefinition('keenio.projectID', $this->client->getProjectID());
        $controller->addDefinition('keenio.writeKey', $this->client->getWriteKey());
    }

    /**
     * Add JavaScript files to the current page.
     *
     * @param Gdn_Controller Instance of the current page's controller.
     */
    public function addJsFiles(Gdn_Controller $controller) {
        $controller->addJsFile('keenio.sdk.min.js', 'plugins/vanillaanalytics');
        $controller->addJsFile('keenio.min.js', 'plugins/vanillaanalytics');
    }

    /**
     * Overwrite and append default key/value pairs to incoming array.
     *
     * @link https://keen.io/docs/api/#data-enrichment
     * @param array $defaults List of default data pairs for all events.
     * @return array
     */
    public function addDefaults(array $defaults = array()) {
        $additionalDefaults = [
            'keen' => [
                'addons' => [
                    [
                        'name' => 'keen:ip_to_geo',
                        'input' => [
                            'ip' => 'ip'
                        ],
                        'output' => 'ipGeo'
                    ],
                    /**
                     * url_parser doesn't work without a domain name.  Since we don't currently use domain name, we're
                     * going to ditch keen's URL parser addon.
                    [
                        'name' => 'keen:url_parser',
                        'input' => [
                            'url' => 'url'
                        ],
                        'output' => 'urlParsed'
                    ]
                     */
                ]
            ]
        ];

        $defaults = array_merge($defaults, $additionalDefaults);

        if (!empty($defaults['referrer'])) {
            $defaults['keen']['addons'][] = [
                'name' => 'keen:referrer_parser',
                'input' => [
                    'referrer_url' => 'referrer',
                    'page_url' => 'url'
                ],
                'output' => 'referrerParsed'
            ];
        }

        if (!empty($defaults['userAgent'])) {
            $defaults['keen']['addons'][] = [
                'name' => 'keen:ua_parser',
                'input' => [
                    'ua_string' => 'userAgent'
                ],
                'output' => 'userAgentParsed'
            ];
        }

        return $defaults;
    }

    /**
     * Record an event using the keen.io API.
     *
     * @param string $collection Grouping for the current event.
     * @param string $type Name/type of the event being tracked.
     * @param array $details A collection of details about the event.
     * @return array Body of response from keen.io
     */
    public function event($collection, $type, array $details = []) {
        $details['type'] = $type;

        return $this->client->addEvent($collection, $details);
    }


    /**
     * Detect if tracker is configured for use.
     *
     * @param bool $write Configured to write to the tracker?
     * @param bool $read Configured to read from the tracker?
     * @return bool True on configured, false otherwise
     */
    public static function isConfigured($write = true, $read = true) {
        $configured = false;

        // Do we at least have a project ID configured?
        if (c('VanillaAnalytics.KeenIO.ProjectID')) {
            // Do we need either a read or write key and have them configured?
            if ((!$write || c('VanillaAnalytics.KeenIO.WriteKey')) &&
                (!$read || c('VanillaAnalytics.KeenIO.ReadKey'))) {
                $configured = true;
            }
        }

        return $configured;
    }

    /**
     * Setup routine, called when plug-in is enabled.
     */
    public function setup() {
        // When the plug-in is first enabled, our KeenIOClient class won't be enabled.  Grab the class.
        if (!class_exists('KeenIOClient')) {
            require_once(PATH_PLUGINS . '/vanillaanalytics/models/class.keenioclient.php');
        }

        // Attempt to grab all the necessary data for creating a project with keen.io
        $defaultProjectUser = c('VanillaAnalytics.KeenIO.DefaultProjectUser');
        $site = class_exists('Infrastructure') ? Infrastructure::site('name') : c('Garden.Domain', null);
        $orgID  = c('VanillaAnalytics.KeenIO.OrgID');
        $orgKey = c('VanillaAnalytics.KeenIO.OrgKey');

        // All of these pieces are essential for creating a project.  Fail without them.
        if (!$orgID) {
            throw new Gdn_UserException('Empty value for VanillaAnalytics.KeenIO.OrgID');
        }
        if (!$orgKey) {
            throw new Gdn_UserException('Empty value for VanillaAnalytics.KeenIO.OrgKey');
        }
        if (!$defaultProjectUser) {
            throw new Gdn_UserException('Empty value for VanillaAnalytics.KeenIO.DefaultProjectUser');
        }

        // Build the keen.io client and attempt to create a new project
        $keenIOConfig = [
            'orgID'  => $orgID,
            'orgKey' => $orgKey
        ];
        $keenIOClient = new KeenIOClient(null, $keenIOConfig);

        $project = $keenIOClient->addProject(
            $site,
            [
                [
                    'email' => $defaultProjectUser
                ]
            ]
        );

        // If we were successful, save the details.  If not, trigger an error.
        if ($project) {
            saveToConfig('VanillaAnalytics.KeenIO.ProjectID', $project->id);
            saveToConfig('VanillaAnalytics.KeenIO.ReadKey', $project->apiKeys->readKey);
            saveToConfig('VanillaAnalytics.KeenIO.WriteKey', $project->apiKeys->writeKey);
        } else {
            throw new Gdn_UserException('Unable to create project on keen.io');
        }
    }
}
