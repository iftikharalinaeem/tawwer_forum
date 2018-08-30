<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Class Zendesk.
 */
class Zendesk {

    CONST REST_API_URL = 'https://developer.zendesk.com/rest_api/docs';

    protected $apiUrl;
    protected $apiUser;
    protected $apiToken;

    /**
     * Setup Properties.
     *
     * @param IZendeskHttpRequest $curlRequest Curl Request Object.
     * @param string $url Url to API.
     * @param string $accessToken OAuth AccessToken.
     */
    public function __construct(IZendeskHttpRequest $curlRequest, $url, $accessToken) {
        $this->curl = $curlRequest;
        $this->apiUrl = trim($url, '/').'/api/v2';
        $this->AccessToken = $accessToken;
    }


    /**
     * Create Ticket using the Zendesk API.
     *
     * @param string $subject Subject Line.
     * @param string $body Message Body.
     * @param array $requester Requester Array.
     * @param array $additionalTicketFields Additional fields to the json.
     *
     * @return int
     */
    public function createTicket($subject, $body, $requester, $additionalTicketFields = []) {
        //$body = Gdn_Format::htmlFilter($body);
        $ticketFields = [
            'requester' => $requester,
            'subject' => $subject,
            'comment' => ['html_body' => $body]
        ];
        $ticket = array_merge($ticketFields, $additionalTicketFields);
        $response = $this->zendeskRequest(
            "/tickets.json",
            json_encode(['ticket' => $ticket]),
            "POST"
        );
        return $response['ticket']['id'];
    }

    /**
     * Get Ticket.
     *
     * @param string $ticketID Ticket Identified.
     *
     * @return mixed
     */
    public function getTicket($ticketID) {
        $response = $this->zendeskRequest('/tickets/'.$ticketID.'.json');
        return $response['ticket'];
    }

    /**
     * Get last comment made on ticket.
     *
     * @param string $ticketID Ticket ID.
     *
     * @return array
     */
    public function getLastComment($ticketID) {
        $response = $this->zendeskRequest('/tickets/'.$ticketID.'/comments.json');
        //remove the first comment (its the ticket)
        $comments = $response['comments'];
        if (count($comments) > 1) {
            return array_pop($comments);
        }
        return [];
    }

    /**
     * Get user details.
     *
     * @param string $userID User ID.
     *
     * @return mixed
     * @throws Exception If errors.
     */
    public function getUser($userID) {
        if (!is_int($userID)) {
            throw new Exception('Invalid User ID');
        }
        $response = $this->zendeskRequest('/users/'.$userID.'.json');
        return $response;
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
        return [
            'name' => $name,
            'email' => $email
        ];
    }

    /**
     * Send request to zendesk API.
     *
     * @param string $url URL ie /tickets.json.
     * @param null|string $json JSON encoded data to be used Required for POST and PUT actions.
     * @param string $action POST, GET, PUT, DELETE.
     * @param bool $cache Cache result. Only if http code 200 and method GET.
     *
     * @throws Exception If errors.
     * @return mixed json
     */
    public function zendeskRequest($url, $json = null, $action = 'GET', $cache = false) {

        $apiURL = $this->apiUrl.$url;

        trace($action.' '.$apiURL);

        $cacheKey = 'Zendesk.Request.'.md5($apiURL);

        if ($cache && $action == 'GET') {
            $responseBody = Gdn::cache()->get($cacheKey, [Gdn_Cache::FEATURE_COMPRESS => true]);
            if ($responseBody) {
                trace('Cached Response Body');
                return json_decode($responseBody, true);
            }
        }

        $this->curl->setOption(CURLOPT_URL, $apiURL);
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
        $this->curl->setOption(CURLOPT_MAXREDIRS, 10);
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);

        switch ($action) {
            case "POST":
                $this->curl->setOption(CURLOPT_CUSTOMREQUEST, "POST");
                $this->curl->setOption(CURLOPT_POSTFIELDS, $json);
                break;
            case "GET":
                $this->curl->setOption(CURLOPT_CUSTOMREQUEST, "GET");
                break;
            case "PUT":
                $this->curl->setOption(CURLOPT_CUSTOMREQUEST, "PUT");
                $this->curl->setOption(CURLOPT_POSTFIELDS, $json);
                break;
            case "DELETE":
                $this->curl->setOption(CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            default:
                break;
        }
        $this->curl->setOption(
            CURLOPT_HTTPHEADER,
            ['Content-type: application/json', 'Authorization: Bearer '.$this->AccessToken]
        );
        $userAgent = Gdn::request()->getValueFrom(INPUT_SERVER, 'HTTP_USER_AGENT', 'MozillaXYZ/1.0');
        $this->curl->setOption(CURLOPT_USERAGENT, $userAgent);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, 1);
        $this->curl->setOption(CURLOPT_HEADER, 1);
        $this->curl->setOption(CURLOPT_TIMEOUT, 10);

        $response = $this->curl->execute();
        $headerSize = $this->curl->getInfo(CURLINFO_HEADER_SIZE);
        $responseHeader = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);

        $httpCode = $this->curl->getInfo(CURLINFO_HTTP_CODE);
        $decoded = json_decode($responseBody, true);

        if ($cache && $httpCode == 200 && $action == 'GET') {
            $cacheTTL = $this->CacheTTL + rand(0, 30);
            Gdn::cache()->store($cacheKey, $responseBody, [
                Gdn_Cache::FEATURE_EXPIRY => $cacheTTL,
                Gdn_Cache::FEATURE_COMPRESS => true
            ]);
        }

        $errorMessage = false;

        $requestID = 'N/A';
        if (preg_match('/X-Request-Id: (.+)?\r\n/', $responseHeader, $requestIDMatch)) {
            $requestID = $requestIDMatch[1];
        }

        if ($httpCode == 404) {
            $errorMessage = 'URL not found: '.$this->apiUrl.$url;
        }

        if ($httpCode == 409) {
            $errorMessage = 'Concurrency issue. Try again. See '.self::REST_API_URL.'/core/introduction#409';
        }

        if ($httpCode == 422) {
            $errorMessage = 'Unprocessable Entity. See '.self::REST_API_URL.'/core/introduction#422-unprocessable-entity';
        }

        if ($httpCode == 429) {
            $errorMessage = 'Rate Limits exceeded. See '.self::REST_API_URL.'/core/introduction#429';
        }

        if ($httpCode >= 500) {
            $retryAfter = null;
            if (preg_match('/Retry-After: (.+)?\r\n/', $responseHeader, $retryAfterMatches)) {
                $retryAfter = $retryAfterMatches[1];
            }
            $errorMessage = 'Unknown error. Try again '.($retryAfter ? "in $retryAfter seconds." : 'later.');
            $errorMessage .= "\nIf the error persist contact Zendesk with the provided Request ID";
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $errorMessage = 'Unknown error.';
        }

        if ($errorMessage) {
            $errorMessage .= "\nZendesk Request ID: $requestID\nAPI call: $apiURL\nResponse: $responseBody\n";

            Logger::log(Logger::DEBUG, 'zendesk_request_error', [
                'error' => $errorMessage,
                'access_token' => $this->AccessToken,
                'request' => $action.' '.$apiURL,
                'response' => $response,
            ]);

            throw new Gdn_UserException($errorMessage, $httpCode);
        }

        return $decoded;

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
        $fullProfile = $this->zendeskRequest("/users/$userId.json");

        return [
            'id' => $fullProfile['user']['id'],
            'email' => $fullProfile['user']['email'],
            'fullname' => $fullProfile['user']['name'],
            'photo' => valr('user.photo.content_url', $fullProfile)
        ];

    }
}
