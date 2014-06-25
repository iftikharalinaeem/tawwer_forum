<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Class Zendesk.
 */
class Zendesk {

    protected $apiUrl;
    protected $apiUser;
    protected $apiToken;
    protected $logging = false;

    /**
     * Setup Properties.
     *
     * @param IZendeskHttpRequest $curlRequest Curl Request Object.
     * @param string $Url Url to API.
     * @param string $AccessToken OAuth AccessToken.
     */
    public function __construct(IZendeskHttpRequest $curlRequest, $Url, $AccessToken) {
        $this->curl = $curlRequest;
        $this->apiUrl = $Url . '/api/v2';
        $this->AccessToken = $AccessToken;
    }

    /**
     * Enable Logging.
     */
    public function enableLogging() {
        $this->logging = true;
    }

    /**
     * Disable Logging.
     */
    public function disableLogging() {
        $this->logging = true;
    }

    /**
     * Create Ticket using the Zendesk API.
     *
     * @param string $Subject Subject Line.
     * @param string $Body Message Body.
     * @param array $Requester Requester Array.
     * @param array $AdditionalTicketFields Additional fields to the json.
     *
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

    /**
     * Get Ticket.
     *
     * @param string $TicketID Ticket Identified.
     *
     * @return mixed
     */
    public function getTicket($TicketID) {
        $Response = $this->zendeskRequest('/tickets/' . $TicketID . '.json');
        return $Response['ticket'];
    }

    /**
     * Get last comment made on ticket.
     *
     * @param string $TicketID Ticket ID.
     *
     * @return array
     */
    public function getLastComment($TicketID) {
        $Response = $this->zendeskRequest('/tickets/' . $TicketID . '/comments.json');
        //remove the first comment (its the ticket)
        $Comments = $Response['comments'];
        if (count($Comments) > 1) {
            return array_pop($Comments);
        }
        return array();
    }

    /**
     * Get user details.
     *
     * @param string $UserID User ID.
     *
     * @return mixed
     * @throws Exception If errors.
     */
    public function getUser($UserID) {
        if (!is_int($UserID)) {
            throw new Exception('Invalid User ID');
        }
        $Response = $this->zendeskRequest('/users/' . $UserID . '.json');
        return $Response;
    }

    /**
     * Create Requester Array.
     *
     * @param string $name Name.
     * @param string $email Email.
     *
     * @return array
     */
    public function createRequester($name, $email) {
        return array(
            'name' => $name,
            'email' => $email
        );
    }

    /**
     * Send request to zendesk API.
     *
     * @param string $Url URL ie /tickets.json.
     * @param null|string $Json JSON encoded data to be used Required for POST and PUT actions.
     * @param string $Action POST, GET, PUT, DELETE.
     * @param bool $Logging Enable logging to error log.
     * @param bool $Cache Cache result. Only if http code 200 and method GET.
     *
     * @throws Exception If errors.
     * @return mixed json
     */
    public function zendeskRequest($Url, $Json = null, $Action = 'GET', $Logging = false, $Cache = false) {

        Trace($Action . ' ' . $this->apiUrl . $Url);

        $CacheKey = 'Zendesk.Request.' . md5($this->apiUrl . $Url);

        if ($Cache && $Action == 'GET') {
            $Output = Gdn::Cache()->Get($CacheKey, array(Gdn_Cache::FEATURE_COMPRESS => true));
            if ($Output) {
                Trace('Cached Response');
                return json_decode($Output, true);
            }
        }

        $this->curl->setOption(CURLOPT_URL, $this->apiUrl . $Url);
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
        $this->curl->setOption(CURLOPT_MAXREDIRS, 10);
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);

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
        $this->curl->setOption(
            CURLOPT_HTTPHEADER,
            array('Content-type: application/json', 'Authorization: Bearer '. $this->AccessToken)
        );
        $this->curl->setOption(CURLOPT_USERAGENT, "MozillaXYZ/1.0");
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_TIMEOUT, 10);

        $Output = $this->curl->execute();
        $HttpCode = $this->curl->getInfo(CURLINFO_HTTP_CODE);
        $Decoded = json_decode($Output, true);

        if ($Cache && $HttpCode == 200 && $Action == 'GET') {
            $CacheTTL = $this->CacheTTL + rand(0, 30);
            Gdn::Cache()->Store($CacheKey, $Output, array(
                Gdn_Cache::FEATURE_EXPIRY  => $CacheTTL,
                Gdn_Cache::FEATURE_COMPRESS => true
            ));
        }

        if ($this->logging || $Logging) {
//@todo Update to use Logger when its been added to core.
//            error_log('Curl Request: ' . $this->apiUrl . $Url);
//            error_log('Curl JSON: ' . var_export(json_decode($Json), true));
//            error_log('Output: ' . $Output);
//            error_log('Decoded Response: ' . var_export($Decoded, true));
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

    /**
     * Get user profile from zendesk.
     *
     * @param bool|int $userId Defaults to authenticated user.
     *
     * @return array Profile User Profile. Array with the following keys:
     *      [id]
     *      [fullname]
     *      [email]
     *      [photo]
     */
    public function getProfile($userId = false) {
        if (!$userId) {
            $userId = 'me';
        }
        $fullProfile = $this->zendeskRequest('/users/' . $userId . '.json');
        return array(
            'id' => $fullProfile['user']['id'],
            'email' => $fullProfile['user']['email'],
            'fullname' => $fullProfile['user']['name'],
            'photo' => $fullProfile['user']['photo'],
        );
    }
}
