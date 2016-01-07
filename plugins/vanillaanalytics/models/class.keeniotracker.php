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
                'projectId' => c('VanillaAnalytics.KeenIO.ProjectID'),
                'writeKey'   => c('VanillaAnalytics.KeenIO.WriteKey'),
                'readKey'  => c('VanillaAnalytics.KeenIO.ReadKey')
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
        $controller->addJsFile('https://d26b395fwzu5fz.cloudfront.net/3.3.0/keen.min.js');
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
                    [
                        'name' => 'keen:url_parser',
                        'input' => [
                            'url' => 'url'
                        ],
                        'output' => 'urlParsed'
                    ]
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
}
