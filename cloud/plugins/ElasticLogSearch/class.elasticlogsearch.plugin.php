<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class ElasticLogSearch extends Gdn_Plugin {

    /**
     * Development mode.
     * @var bool
     */
    public $localHostDev = false;

    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = &$sender->EventArguments['SideMenu'];
        $menu->addLink('Dashboard', t('Application Log'), '/settings/applog', 'Garden.Settings.Manage');
    }

    /**
     * @param SettingsController $sender
     * @param string $page
     */
    public function settingsController_applog_create($sender, $page = '') {
        $sender->permission('Garden.Settings.Manage');

        $sender->addJsFile('eventlog.js', 'plugins/ElasticLogSearch');
        $sender->addCssFile('eventlog.css', 'plugins/ElasticLogSearch');

        $elasticSearch = Elastic::connection('log');

        $sender->Form = new Gdn_Form();
        $pageSize = 30;
        list($offset, $limit) = offsetLimit($page, $pageSize);

        $get = array_change_key_case($sender->Request->get());
        $params = [
            'index' => 'log_vanilla*'
        ];
        //$params['body']['query']['filtered']['query'] = array('match_all' => array());

        // Look for query parameters to filter the data.
        if ($v = val('datefrom', $get)) {
            $v = strtotime($v);
            if (!$v) {
                $sender->Form->addError('Invalid Date format for From Date.');
            } else {
                $from = $v;
            }
            $sender->Form->setFormValue('datefrom', $get['datefrom']);
        }

        if ($v = val('dateto', $get)) {
            $v = strtotime($v);
            if (!$v) {
                $sender->Form->addError('Invalid Date format for To Date.');
            } else {
                $to = $v;
            }
            $sender->Form->setFormValue('dateto', $get['dateto']);
        }

        if (isset($from) && isset($to)) {
            $params['body']['query']['filtered']['filter']['bool']['must'][]['range']['message.timestamp'] = [
                'from' => $from,
                'to' => $to,
            ];
        } elseif (isset($to)) {
            $params['body']['query']['filtered']['filter']['bool']['must'][]['range']['message.timestamp'] = [
                'to' => $to,
            ];
        } elseif (isset($from)) {
            $params['body']['query']['filtered']['filter']['bool']['must'][]['range']['message.timestamp'] = [
                'from' => $from,
            ];
        }

        $sender->Form->setFormValue('priority', 'All');
        if (($v = val('priority', $get)) && $v != 'All') {

            $params['body']['query']['filtered']['filter']['bool']['must'][]['range']['message.priority'] = [
                'to' => $v,
            ];

            $sender->Form->setFormValue('priority', $v);

        }

        if ($v = val('event', $get)) {
            $params['body']['query']['filtered']['filter']['bool']['must'][]['term']['message.event'] = $v;
            $sender->Form->setFormValue('event', $v);
        }

        if ($v = val('siteid', $get)) {
            $params['body']['query']['filtered']['filter']['bool']['must'][]['term']['message.siteid'] = $v;
            $sender->Form->setFormValue('siteid ', $v);
        }

        if ($v = val('ipaddress', $get)) {
            $params['body']['query']['filtered']['filter']['bool']['must'][]['term']['message.ip'] = $v;
            $sender->Form->setFormValue('ipaddress', $v);
        }

        $sortOrder = 'desc';
        if ($v = val('sortorder', $get)) {
            if ($v == 'asc') {
                $sortOrder = 'asc';
            } else {
                $sortOrder = 'desc';
            }
        }

        $params['sort'] = ['@timestamp:' . $sortOrder];
        $sender->Form->setFormValue('sortorder', $sortOrder);

        $params['from'] = $offset;
        $params['size'] = $limit;

        trace($params);

        try {
            $results = $elasticSearch->search($params);
            $events = $this->convertHitsToRows($results['hits']['hits']);

        } catch (Exception $e) {
            // Query Error
            $searchMessage = json_decode($e->getMessage());
            trace($searchMessage, TRACE_ERROR);
            $events = [];
            $results = [];
        }

        // Application calculation.
        foreach ($events as &$event) {
            $event['Url'] = $event['Domain'] . ltrim($event['Path'], '/');
//            $event['InsertProfileUrl'] = userUrl(Gdn::userModel()->getID($event['InsertUserID']));

            unset($event['Domain'], $event['Path']);
        }

        $sender->addSideMenu();
        $priorityOptions = [
            Logger::DEBUG => LOG_DEBUG,
            Logger::INFO => LOG_INFO,
            Logger::NOTICE => LOG_NOTICE,
            Logger::WARNING => LOG_WARNING,
            Logger::ERROR => LOG_ERR,
            Logger::CRITICAL => LOG_CRIT,
            Logger::ALERT => LOG_ALERT,
            Logger::EMERGENCY => LOG_EMERG
        ];
        $priorityOptions = array_flip($priorityOptions);
        $priorityOptions['All'] = 'All';

        $filter = Gdn::request()->get();
        unset($filter['TransientKey']);
        unset($filter['hpt']);
        unset($filter['Filter']);
        $currentFilter = http_build_query($filter);

        $sender->setData(
            [
                'Events' => $events,
                'PriorityOptions' => $priorityOptions,
                'SortOrder' => $sortOrder,
                'CurrentFilter' => $currentFilter
            ]
        );

        $pager = PagerModule::current();
        $totalRecords = valr('hits.total', $results, 0);
        unset($filter['Page']);
        $currentFilter = http_build_query($filter);
        $pager->configure($offset, $limit, $totalRecords, '/settings/applog?' . $currentFilter . '&Page={Page}');


        $sender->render('eventlog', '', 'plugins/ElasticLogSearch');
    }



    public function convertHitsToRows($hits) {
        $rows = [];
        $siteIDs = [];
        foreach ($hits as $hit) {
            $siteID = valr('_source.message.siteid', $hit, 0);
            if ($siteID > 0) {
                $siteIDs[$siteID] = true;
            }
        }
        $sQL = GDN::sql();
        $sites = $sQL->select('SiteID, Name')->from('Multisite')->whereIn('SiteID', array_keys($siteIDs))->get()->resultArray();
        $sites = array_column($sites, 'Name', 'SiteID');
        $i = 0;
        foreach ($hits as $hit) {

            $message = formatString(valr('_source.message.msg', $hit), valr('_source.message', $hit));
            if ($message == '') {
                continue;
            }
            $rows[$i] = [
                'ID' => $hit['_id'],
                'Timestamp' => valr('_source.message.timestamp', $hit),
                'Event' => valr('_source.message.event', $hit),
                'Priority' => valr('_source.message.priority', $hit, 'unknown'),
                'Message' => $message,
                'Domain' => valr('_source.message.domain', $hit),
                'Path' => valr('_source.message.path', $hit),
                'UserID' => valr('_source.message.userid', $hit),
                'Username' => valr('_source.message.username', $hit),
                'IP' => valr('_source.message.ip', $hit),
                'SiteID' => valr('_source.message.siteid', $hit),
                'SiteName' => val(valr('_source.message.siteid', $hit), $sites, 'unknown'),
                'Source' => ''
            ];
            if (c('Debug')) {
                $rows[$i]['Source'] = $hit;
            }
            $i++;
        }
        return $rows;
    }

    /**
     * Takes all of the vales and sets them to that of the key.
     *
     * @param array $array Input array.
     *
     * @return array Output array.
     */
    public static function getArrayWithKeysAsValues($array) {
        $keys = array_keys($array);
        return array_combine($keys, $keys);
    }

    /**
     * Used for localhost testing.
     */
    public function vanillaController_testElastic_create() {

        if (!$this->localHostDev) {
            return;
        }

        $es = Elastic::connection('log');

        $params = [];

        $params['body']['query']['filtered']['query'] = ['match_all' => []];

        $params['body']['query']['filtered']['filter']['bool']['must'][]['term']['message.event'] = 'security_access';
        $params['body']['query']['filtered']['filter']['bool']['must'][]['term']['message.siteid'] = 6021894;

        $params['body']['query']['filtered']['filter']['bool']['must'][]['range']['message.timestamp'] = [
            'from' => 1413931530,
            'to' => 1413931550,
        ];

//        $params['body']['query']['filtered']['filter']['bool']['must'][]['range']['message.priority'] = array(
//            'to' => 7,
//        );

        //array('term' => array('message.event' => 'security_access'));
        //$params['body']['query']['filtered']['filter']['bool']['must'][] = array('term' => array('message.siteid' => 6021894));
//        $params['body']['query']['filtered']['filter'] = array(
//            'bool' => array(
//                'must' => array(
//                    array('term' => array('message.event' => 'security_access')),
//                    array('term' => array('message.siteid' => 6021894)),
//                    array('term' => array('message.userid' => 1)),
//                    array('range' =>
//                        array(
//                            'message.timestamp' => array('from' => 1413931530, 'to' => 1413931550),
////                            'message.priority' => array('from' => 0)
//                        )
//                    )
//                ),
//            ),
//        );


        $params['index'] = 'log_vanilla*';
        //$params['type']  = 'apache_access';

        // Pagination params.
        $params['from'] = 0;
        $params['size'] = 30;
        $params['sort'] = ['@timestamp:desc'];

        $results = $es->search($params);


        echo '<pre>';

        $events = $this->convertHitsToRows($results['hits']['hits']);
        echo json_encode(
            [
                'params' => $params,
                'results' => $results,
                'events' => $events
            ],
            JSON_PRETTY_PRINT
        );
        echo '</pre>';


    }

    /**
     *
     *  This is used for localhost testing.
     *
     * Provide elasticsearch configuration.
     *
     * @param Elastic $sender
     * @param array $args Sending arguments.
     */
    public function elastic_getIdentity_handler($sender, $args) {

        if (!$this->localHostDev) {
            return;
        }
        $key = $args['key'];

        // Hosts
        $hosts = [];
        switch ($key) {
            case 'log':

                $hosts[] = 'localhost:9200';

                // Only populate identity if hosts can be found
                if (!count($hosts)) {
                    return;
                }
                break;

            default:
                return;
                break;
        }

        $identity = &$args['identity'];
        $identity['params'] = [];

        // Connection management
        $identity['params']['connectionPoolClass'] = '\Elasticsearch\ConnectionPool\StaticConnectionPool';
        $identity['params']['selectorClass'] = '\Elasticsearch\ConnectionPool\Selectors\StickyRoundRobinSelector';

        $identity['params']['hosts'] = $hosts;

    }

}
