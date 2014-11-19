<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['ElasticLogSearch'] = array(
    'Name' => 'Elastic Log Search',
    'Description' => 'Use elastic search to display log data.',
    'Version' => '1.1alpha',
    'RequiredApplications' => array('Vanilla' => '2.0.18'),
    'MobileFriendly' => FALSE,
    'Author' => 'John Ashton',
    'AuthorEmail' => 'john@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/'
);

class ElasticLogSearch extends Gdn_Plugin {

    /**
     * Development mode.
     * @var bool
     */
    public $localHostDev = false;

    public $severityOptions = array(
        'info' => true,
        'notice' => true,
        'warning' => true,
        'error' => true,
    );

    /**
     * @param SettingsController $Sender
     * @param string $Page
     */
    public function SettingsController_EventLog2_Create($Sender, $Page = '') {
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
        // Look for query parameters to filter the data.
        if ($v = val('datefrom', $get)) {
            $v = date('c', strtotime($v));
            if (!$v) {
                $Sender->Form->AddError('Invalid Date format for From Date.');
            }
            $params['body']['query']['range']['message.timestamp']['gte'] = strtotime($v);
            $Sender->Form->SetFormValue('datefrom', $get['datefrom']);
        }

        if ($v = val('dateto', $get)) {
            $v = date('c', strtotime($v));
            if (!$v) {
                $Sender->Form->AddError('Invalid Date format for To Date.');
            }
            $params['body']['query']['range']['message.timestamp']['lte'] = strtotime($v);
            $Sender->Form->SetFormValue('dateto', $get['dateto']);
        }

        $Sender->Form->SetFormValue('severity', 'all');
        if (($v = val('severity', $get)) && $v != 'all') {
            $validLevelString = implode(', ', array_keys(array_slice($this->severityOptions, 0, -1)));
            $validLevelString .= ' and ' . end(array_keys($this->severityOptions));

            if (!isset($this->severityOptions[$v])) {
                $Sender->Form->AddError('Invalid severity.  Valid options are: ' . $validLevelString);
            }
            $params['body']['query']['match']['message.priority'] = $v;
            $Sender->Form->SetFormValue('severity', $v);

        }

        if ($v = val('event', $get)) {
            $params['body']['query']['match']['message.event'] = $v;
            $Sender->Form->SetFormValue('event', $v);
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

        $Sender->SetData('_CurrentRecords', count(valr('hits.hits', $results, 0)));

        $Sender->AddSideMenu();
        $SeverityOptions = self::getArrayWithKeysAsValues($this->severityOptions);
        $SeverityOptions['all'] = 'All';

        $filter = Gdn::Request()->Get();
        unset($filter['TransientKey']);
        unset($filter['hpt']);
        unset($filter['Filter']);
        $CurrentFilter = http_build_query($filter);

        $Sender->SetData(
            array(
                'Events' => $events,
                'SeverityOptions' => $SeverityOptions,
                'SortOrder' => $sortOrder,
                'CurrentFilter' => $CurrentFilter
            )
        );

        $Sender->Render('eventlog', '', 'plugins/ElasticLogSearch');
    }



    public function convertHitsToRows($hits) {
        $rows = array();
        foreach ($hits as $hit) {

            $message = FormatString(valr('_source.message.msg', $hit), valr('_source.message', $hit));
            if ($message == '') {
                continue;
            }
            $rows[] = array(
                'ID' => $hit['_id'],
                'Timestamp' => valr('_source.message.timestamp', $hit),
                'Event' => valr('_source.message.event', $hit),
                'Level' => valr('_source.message.level', $hit, 'unknown'),
                'Message' => $message,
                'Domain' => valr('_source.message.domain', $hit),
                'Path' => valr('_source.message.path', $hit),
                'UserID' => valr('_source.message.userid', $hit),
                'Username' => valr('_source.message.username', $hit),
                'IP' => valr('_source.message.ip', $hit),
                'Source' => $hit
            );
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

        $indices = $es->indices()->getAliases();
        $params['index'] = 'log_vanilla*';
        //$params['type']  = 'apache_access';

        // Pagination params.
        $params['from'] = 0;
        $params['size'] = 30;


        // Search Query.
//        $params['body']['query']['match']['message'] = 'GET';
//        $params['body']['query']['wildcard']['message.msg'] = 'method';
//        $params['body']['query']['wildcard']['message.url'] = 'cleanspeak*';
//        $params['body']['query']['wildcard']['host'] = 'app1';
//
//        $params['body']['query']['range']['@timestamp']['gte'] = '2010-10-20T21:00:08+00:00';
//        $params['body']['query']['range']['message.timestamp']['gte'] = 1677972800;
//        $params['body']['query']['nested'] = 'msg';

        $params['sort'] = array('@timestamp:desc');
        //$params['sort'] = array('@timestamp:asc');
        $results = $es->search($params);

        $query = array();
        $query['wildcard']['message.msg'] = 'cleanspeak';

        $params['body']['query'] = $query;

        echo '<pre>';

        $events = $this->convertHitsToRows($results['hits']['hits']);
        echo json_encode(array('params' => $params, 'results' => $results), JSON_PRETTY_PRINT);
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