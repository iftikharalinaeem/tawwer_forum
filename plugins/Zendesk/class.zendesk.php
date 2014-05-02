<?php
/**
 * @copyright 2014 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Class Zendesk
 */
class Zendesk
{

    protected $apiUrl;
    protected $apiUser;
    protected $apiToken;
    protected $logging = false;

    public function __construct(IZendeskHttpRequest $curlRequest, $apiUrl, $apiUser, $apiToken) {
        $this->curl = $curlRequest;
        $this->apiUrl = $apiUrl;
        $this->apiUser = $apiUser;
        $this->apiToken = $apiToken;
    }

    public function enableLogging() {
        $this->logging = true;
    }

    public function disableLogging() {
        $this->logging = true;
    }

    /**
     * Create Ticket using the Zendesk API
     *
     * @param string $Subject
     * @param string $Body
     * @param array $Requester
     * @param array $AdditionalTicketFields ; Will be added to the json
     * @return int
     */
    public function createTicket($Subject, $Body, $Requester, $AdditionalTicketFields = array()) {
        $TicketFields = array(
            'requester' => $Requester,
            'subject' => $Subject,
            'comment' => array('body' => $Body)
        );
        $Ticket = array_merge($TicketFields, $AdditionalTicketFields);
        $Response = $this->zendeskRequest(
            "/tickets.json",
            json_encode(array('ticket' => $Ticket)),
            "POST"
        );
        return $Response['ticket']['id'];
    }

    public function getTicket($TicketID) {
        $Response = $this->zendeskRequest('/tickets/' . $TicketID . '.json');
        return $Response['ticket'];
    }

    public function getLastComment($TicketID) {
        $Response = $this->zendeskRequest('/tickets/' . $TicketID . '/comments.json');
        //remove the first comment (its the ticket)
        $Comments = $Response['comments'];
        if (count($Comments) > 1) {
            return array_pop($Comments);
        }
        return array();
    }

    public function getUser($UserID) {
        if (!is_int($UserID)) {
            throw new Exception('Invalid User ID');
        }
        $Response = $this->zendeskRequest('/users/' . $UserID . '.json');
        return $Response;
    }

    public function createRequester($name, $email) {
        return array(
            'name' => $name,
            'email' => $email
        );
    }

    /**
     * @param string $Url ie /tickets.json
     * @param null|string $Json JSON encoded data to be used Required for POST and PUT actions
     * @param string $Action POST, GET, PUT, DELETE
     * @param bool $Logging - enable logging to error log
     * @throws Exception
     * @return mixed json
     */
    public function zendeskRequest($Url, $Json = null, $Action = 'GET', $Logging = false) {

        $this->curl->setOption(CURLOPT_URL, $this->apiUrl . $Url);
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
        $this->curl->setOption(CURLOPT_MAXREDIRS, 10);
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
        $this->curl->setOption(CURLOPT_USERPWD, $this->apiUser . "/token:" . $this->apiToken);
        switch ($Action) {
            case "POST":
                $this->curl->setOption(CURLOPT_CUSTOMREQUEST, "POST");
                $this->curl->setOption(CURLOPT_POSTFIELDS, $Json);
                break;
            case "GET":
                $this->curl->setOption(CURLOPT_CUSTOMREQUEST, "GET");
                break;
            case "PUT":
                $this->curl->setOption(CURLOPT_CUSTOMREQUEST, "PUT");
                $this->curl->setOption(CURLOPT_POSTFIELDS, $Json);
                break;
            case "DELETE":
                $this->curl->setOption(CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            default:
                break;
        }
        $this->curl->setOption(CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        $this->curl->setOption(CURLOPT_USERAGENT, "MozillaXYZ/1.0");
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_TIMEOUT, 10);

        Trace($Action . ' ' . $this->apiUrl . $Url);
        $Output = $this->curl->execute();

        $HttpCode = $this->curl->getInfo(CURLINFO_HTTP_CODE);
        $Decoded = json_decode($Output, true);

        if ($this->logging || $Logging) {
            error_log('Curl Request: ' . $this->apiUrl . $Url);
            error_log('Curl JSON: ' . var_export(json_decode($Json), true));
            error_log('Output: ' . $Output);
            error_log('Decoded Response: ' . var_export($Decoded, true));
        }
        if ($HttpCode == 404) {
            //throw not found
            throw new Gdn_UserException('Invalid URL: ' . $this->apiUrl . $Url . "\n", 404);
        }
        if ($HttpCode == 401 || $HttpCode == 429) {
            //429 - auth error; repeated...
            throw new Gdn_UserException('Authentication Error. Check your settings');
        }
        if ($HttpCode != 200 && $HttpCode != 201) {
            throw new Gdn_UserException('Error making request. Try again later ->' . $HttpCode);
        }

        if (!is_array($Decoded)) {
            throw new Gdn_UserException('Errors in Request:' . $Output);
        }
        if (!empty($Decoded['errors'])) {
            throw new Gdn_UserException('Errors in Request:' . $Output);
        }

        return $Decoded;

    }
}
