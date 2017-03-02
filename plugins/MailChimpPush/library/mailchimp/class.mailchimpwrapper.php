<?php

use Garden\Http;

/**
 * Class MailChimpWrapper
 *
 * Methods to facilitate commumicating with MailChimp.
 */

class MailChimpWrapper {


    /**
     * @var string We to append a timestamp to emails in this list to get around MailChimp's Invalid Resource error:
     * "* has signed up to a lot of lists very recently; we're not allowing more signups for now"
     */
    public $testEmail = '';

    /**
     * @var string MailChimp's API version
     */
    public $version = '3.0';

    /**
     * @var int set time limit for the request
     */
    public $timeout = 300;

    /**
     * @var string Cache the user api_key so we only have to log in once per client instantiation
     */
    public $api_key;

    /**
     * @var string Cache the information on the API location on the server
     */
    public $apiUrl;

    public function __construct($apikey) {
        $this->apiUrl = parse_url('https://api.mailchimp.com/' . $this->version . '/');
        $this->api_key = $apikey;
        $this->testEmail = c('Plugins.MailChimp.TestEmail', '');
    }

    /**
     * Actually connect to the server and call the requested methods, parsing the result
     * You should never have to call this function manually
     *
     * @param string $endpoint
     * @param string $method
     * @param array $body
     * @return Http\HttpResponse
     * @throws Exception
     */
    public function callServer($endpoint = null, $method = 'GET', $body = array(), $returnBody = false) {
        // parse out which data center the request should be sent to from the API key
        $dataCenter = 'us1';
        if (strstr($this->api_key,'-')) {
            list($key, $dataCenter) = explode('-',$this->api_key,2);
            if (!$dataCenter) {
                $dataCenter = 'us1';
            }
        }

        if (!$endpoint) {
            throw new Exception('Missing Endpoint');
        }

        if ($endpoint === 'ping') {
            $endpoint = '';
        }

        // create the endpoint based by appending the data center as a subdomain
        $destinationURI = $this->apiUrl['scheme'].'://'.$dataCenter.'.'.$this->apiUrl['host'].'/'.$this->version.'/'.$endpoint;

        $headers['Content-type'] = 'application/json';

        $options['auth'] = ['vf-user',  $this->api_key];

        $httpRequest =  new Http\HttpRequest($method, $destinationURI, $body, $headers, $options);
        $httpRequest->setTimeout($this->timeout);

        $response = $httpRequest->send();
        if ($returnBody) {
            return $response->getBody();
        }
        return $response;
    }

    /**
     * Get a json encoded list of mailing lists at MailChimp.
     *
     * @return Http\HttpResponse
     * @throws Exception
     */
    function lists() {
        $listsResponse = $this->callServer('lists');
        $listsResponse = $this->toArray($listsResponse);
        $lists = val('lists', $listsResponse);
        $lists = Gdn_DataSet::index($lists, 'id');
        return array_column($lists, 'name', 'id');
    }

    /**
     * Get a list of categoryIDs from MailChimp, used to query the list of interests.
     *
     * @param null $list
     * @return array
     * @throws Exception
     */
    function listInterestCategories($list = null) {
        $listInterestCategories = $this->callServer('lists/'.$list.'/interest-categories');
        $listInterestCategories = $this->toArray($listInterestCategories);
        $categories = val('categories', $listInterestCategories);
        $categories = Gdn_DataSet::index($categories, 'id');
        return array_column($categories, 'id');
    }

    /**
     * Get a list of interests from MailChimp.
     *
     * @param null $list
     * @param null $category
     * @return array
     * @throws Exception
     */
    function listInterest($list = null, $category = null) {
        $listInterest = $this->callServer('lists/'.$list.'/interest-categories/'.$category.'/interests');
        $listInterest = $this->toArray($listInterest);
        $interests = val('interests', $listInterest);
        $interests = Gdn_DataSet::index($interests, 'id');
        return array_column($interests, 'name', 'id');
    }

    /**
     * Send an array of emails to be subscribed to a mailing list at MailChimp.
     *
     * @param string $id
     * @param array $batch
     * @return Http\HttpResponse
     * @throws Exception
     */
    public function listBatchSubscribe($id, $batch) {
        $body = [];
        foreach ($batch as $userInfo) {
            $emailType = val('EMAIL_TYPE', $userInfo, 'html');
            $email = val('EMAIL', $userInfo);

            if (!empty($this->testEmail)) {
                // if there's a '+', put the timestamp before the '+', otherwise before the '@'
                $pos = strpos($email, '@');

                if (strpos($email, '+') !== false) {
                    $pos = strpos($email, '+');
                }
                $emailWithoutPlus = substr($email, 0, $pos) . substr($email, strpos($email, '@'));

                if (strtolower($emailWithoutPlus) === strtolower($this->testEmail)) {
                    $domain = substr($email, $pos);
                    $email = substr($email, 0, $pos);
                    $email .= time();
                    $email .= $domain;
                }
            }

            $mergeFields = json_encode(['EMAIL_TYPE' => $emailType]);
            $interestFields = val('InterestID', $userInfo);
            $interestValues = [];
            if ($interestFields) {
                $interestValues = ['interests' => [val('InterestID', $userInfo) => true]];
            }
            $bodyValues = ['email_address' => $email, 'status' => ($userInfo['DoubleOptIn']) ? 'pending' : 'subscribed'] + $interestValues;
            $body['operations'][] = [
                'method' => 'PUT',
                'path' => "lists/{$id}/members/".md5(strtolower($email)),
                'body' => json_encode($bodyValues),
                'merge_fields' => $mergeFields
            ];
        }

        $response = $this->callServer('batches', 'POST', $body, true);

        // Log what is sent, only show the first 10 items in the array, these arrays can be huge
        Logger::event('mailchimp_api', Logger::INFO, 'Batch Subscribe', array_slice($body['operations'], 0, 10));

        // Log the response.
        Logger::event('mailchimp_api', Logger::INFO, 'Batch Subscribe Response', $this->toArray($response));

        return $response;
    }


    /**
     * Send an array of emails to be subscribed to a mailing list at MailChimp.
     *
     * @param string $listID
     * @param array $email
     * @return Http\HttpResponse
     * @throws Exception
     */
    public function listUpdateAddress($listID, $email = array()) {
        $emailID = md5(strtolower(val('EMAIL', $email)));
        $removeBody = ['status' => 'unsubscribed'];

        if (!$listID) {
            throw new Exception('Missing ListID');
        }

        $removedResponse = $this->callServer("lists/{$listID}/members/{$emailID}", 'PATCH', $removeBody);
        Logger::event('mailchimp_api', Logger::INFO, 'Remove Address', $this->toArray($removedResponse));

        $removed = $this->toArray($removedResponse);
        if (val('id', $removed) === $emailID && val('status', $removed) === 'unsubscribed') {
            $emailType = val('EMAIL_TYPE', $email, 'html');
            $mergeFields = ['EMAIL_TYPE' => $emailType];
            $addBody = [
                'email_address' => val('NEW_EMAIL', $email),
                'status' => 'subscribed',
                'merge_fields' => $mergeFields,
                'interests' => val('interests', $removed)
            ];
            $addResponse = $this->callServer("lists/{$listID}/members", 'POST', $addBody);
            Logger::event('mailchimp_api', Logger::INFO, 'Update Address', $this->toArray($addResponse));
        }
    }

    /**
     * Get the information for one specific member of one specific mailing list at MailChimp.
     *
     * @param string $listID
     * @param array $emailAddress
     * @return Http\HttpResponse
     */
    function listMemberInfo($listID, $emailAddress) {
        return $this->callServer("lists/{$listID}/members/".md5(strtolower($emailAddress[0])), 'GET', [], true);
    }

    /**
     * Get the status of a batch that is being processed at MailChimp
     *
     * @param string $batchID
     * @return Http\HttpResponse
     */
    function getBatchStatus($batchID = null) {
        if ($batchID) {
            $batchID = '/'.$batchID;
        }
        return $this->callServer('batches'.$batchID);
    }

    /**
     * Check to see if the account is valid.
     *
     * @return bool
     */
    function ping() {
        $response = $this->callServer('ping');
        return (val('account_id', json_decode($response))) ? true : false;
    }

    /**
     * JSON decode helper function
     *
     * @param string|null $json
     * @return mixed
     */
    function toArray($json = null) {
        if (is_array($json)) {
            return $json;
        }
        if ($array = json_decode($json, true)) {
            return $array;
        }
        return [];
    }
}
