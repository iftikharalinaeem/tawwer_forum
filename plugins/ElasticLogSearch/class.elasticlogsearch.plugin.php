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

    public function Base_GetAppSettingsMenuItems_Handler($Sender) {
        $Menu = &$Sender->EventArguments['SideMenu'];
        $Menu->AddLink('Dashboard', T('Application Log'), '/settings/applog', 'Garden.Settings.Manage');
    }

    /**
     * @param SettingsController $Sender
     * @param string $Page
     */
    public function SettingsController_Applog_Create($Sender, $Page = '') {
        $Sender->Permission('Garden.Settings.Manage');

        $Sender->AddJsFile('eventlog.js', 'plugins/ElasticLogSearch');
        $Sender->AddCssFile('eventlog.css', 'plugins/ElasticLogSearch');

        $elasticSearch = Elastic::connection('log');

        $Sender->Form = new Gdn_Form();
        $pageSize = 30;
        list($offset, $limit) = OffsetLimit($Page, $pageSize);

        $get = array_change_key_case($Sender->Request->Get());
        $params = array(
            'index' => 'log_vanilla*'
        );
        //$params['body']['query']['filtered']['query'] = array('match_all' => array());

        // Look for query parameters to filter the data.
        if ($v = val('datefrom', $get)) {
            $v = strtotime($v);
            if (!$v) {
                $Sender->Form->AddError('Invalid Date format for From Date.');
            } else {
                $from = $v;
            }
            $Sender->Form->SetFormValue('datefrom', $get['datefrom']);
        }

        if ($v = val('dateto', $get)) {
            $v = strtotime($v);
            if (!$v) {
                $Sender->Form->AddError('Invalid Date format for To Date.');
            } else {
                $to = $v;
            }
            $Sender->Form->SetFormValue('dateto', $get['dateto']);
        }

        if (isset($from) && isset($to)) {
            $params['body']['query']['filtered']['filter']['bool']['must'][]['range']['message.timestamp'] = array(
                'from' => $from,
                'to' => $to,
            );
        } elseif (isset($to)) {
            $params['body']['query']['filtered']['filter']['bool']['must'][]['range']['message.timestamp'] = array(
                'to' => $to,
            );
        } elseif (isset($from)) {
            $params['body']['query']['filtered']['filter']['bool']['must'][]['range']['message.timestamp'] = array(
                'from' => $from,
            );
        }

        $Sender->Form->SetFormValue('priority', 'All');
        if (($v = val('priority', $get)) && $v != 'All') {

            $params['body']['query']['filtered']['filter']['bool']['must'][]['range']['message.priority'] = array(
                'to' => $v,
            );

            $Sender->Form->SetFormValue('priority', $v);

        }

        if ($v = val('event', $get)) {
            $params['body']['query']['filtered']['filter']['bool']['must'][]['term']['message.event'] = $v;
            $Sender->Form->SetFormValue('event', $v);
        }

        if ($v = val('siteid', $get)) {
            $params['body']['query']['filtered']['filter']['bool']['must'][]['term']['message.siteid'] = $v;
            $Sender->Form->SetFormValue('siteid ', $v);
        }

        if ($v = val('ipaddress', $get)) {
            $params['body']['query']['filtered']['filter']['bool']['must'][]['term']['message.ip'] = $v;
            $Sender->Form->SetFormValue('ipaddress', $v);
        }

        $sortOrder = 'desc';
        if ($v = val('sortorder', $get)) {
            if ($v == 'asc') {
                $sortOrder = 'asc';
            } else {
                $sortOrder = 'desc';
            }
        }

        $params['sort'] = array('@timestamp:' . $sortOrder);
        $Sender->Form->SetFormValue('sortorder', $sortOrder);

        $params['from'] = $offset;
        $params['size'] = $limit;

        Trace($params);

        try {
            $results = $elasticSearch->search($params);
            $events = $this->convertHitsToRows($results['hits']['hits']);

        } catch (Exception $e) {
            // Query Error
            $searchMessage = json_decode($e->getMessage());
            Trace($searchMessage, TRACE_ERROR);
            $events = array();
            $results = array();
        }

        // Application calculation.
        foreach ($events as &$event) {
            $event['Url'] = $event['Domain'] . ltrim($event['Path'], '/');
//            $event['InsertProfileUrl'] = UserUrl(Gdn::UserModel()->GetID($event['InsertUserID']));

            unset($event['Domain'], $event['Path']);
        }

        $Sender->AddSideMenu();
        $PriorityOptions = array(
            Logger::DEBUG => LOG_DEBUG,
            Logger::INFO => LOG_INFO,
            Logger::NOTICE => LOG_NOTICE,
            Logger::WARNING => LOG_WARNING,
            Logger::ERROR => LOG_ERR,
            Logger::CRITICAL => LOG_CRIT,
            Logger::ALERT => LOG_ALERT,
            Logger::EMERGENCY => LOG_EMERG
        );
        $PriorityOptions = array_flip($PriorityOptions);
        $PriorityOptions['All'] = 'All';

        $filter = Gdn::Request()->Get();
        unset($filter['TransientKey']);
        unset($filter['hpt']);
        unset($filter['Filter']);
        $CurrentFilter = http_build_query($filter);

        $Sender->SetData(
            array(
                'Events' => $events,
                'PriorityOptions' => $PriorityOptions,
                'SortOrder' => $sortOrder,
                'CurrentFilter' => $CurrentFilter
            )
        );

        $Pager = PagerModule::Current();
        $totalRecords = valr('hits.total', $results, 0);
        unset($filter['Page']);
        $CurrentFilter = http_build_query($filter);
        $Pager->Configure($offset, $limit, $totalRecords, '/settings/applog?' . $CurrentFilter . '&Page={Page}');


        $Sender->Render('eventlog', '', 'plugins/ElasticLogSearch');
    }



    public function convertHitsToRows($hits) {
        $rows = array();
        $siteIDs = array();
        foreach ($hits as $hit) {
            $siteID = valr('_source.message.siteid', $hit, 0);
            if ($siteID > 0) {
                $siteIDs[$siteID] = true;
            }
        }
        $SQL = GDN::SQL();
        $Sites = $SQL->Select('SiteID, Name')->From('Multisite')->WhereIn('SiteID', array_keys($siteIDs))->Get()->ResultArray();
        $Sites = array_column($Sites, 'Name', 'SiteID');
        $i = 0;
        foreach ($hits as $hit) {

            $message = FormatString(valr('_source.message.msg', $hit), valr('_source.message', $hit));
            if ($message == '') {
                continue;
            }
            $rows[$i] = array(
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
                'SiteName' => val(valr('_source.message.siteid', $hit), $Sites, 'unknown'),
                'Source' => ''
            );
            if (C('Debug')) {
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
    public function VanillaController_TestElastic_Create() {

        if (!$this->localHostDev) {
            return;
        }

        $es = Elastic::connection('log');

        $params = array();

        $params['body']['query']['filtered']['query'] = array('match_all' => array());

        $params['body']['query']['filtered']['filter']['bool']['must'][]['term']['message.event'] = 'security_access';
        $params['body']['query']['filtered']['filter']['bool']['must'][]['term']['message.siteid'] = 6021894;

        $params['body']['query']['filtered']['filter']['bool']['must'][]['range']['message.timestamp'] = array(
            'from' => 1413931530,
            'to' => 1413931550,
        );

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
        $params['sort'] = array('@timestamp:desc');

        $results = $es->search($params);


        echo '<pre>';

        $events = $this->convertHitsToRows($results['hits']['hits']);
        echo json_encode(
            array(
                'params' => $params,
                'results' => $results,
                'events' => $events
            ),
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
    public function Elastic_GetIdentity_Handler($sender, $args) {

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
