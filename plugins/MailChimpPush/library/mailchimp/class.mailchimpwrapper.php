<?php

use Garden\Http;

/**
 * Class MailChimpWrapper
 *
 * Methods to facilitate commumicating with Mail Chimp.
 */

class MailChimpWrapper {
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
     * Get a json encoded list of mailing lists at Mail Chimp.
     *
     * @return Http\HttpResponse
     * @throws Exception
     */
    function lists() {
        return $this->callServer('lists');
    }

    /**
     * Send an array of emails to be subscribed to a mailing list at Mail Chimp.
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
            $mergeFields = json_encode(['EMAIL_TYPE' => $emailType]);
            $body['operations'][] = [
                'method' => 'PUT',
                'path' => "lists/{$id}/members/".md5(strtolower($email)),
                'body' => json_encode(['email_address' => $email, 'status_if_new' => 'subscribed']),
                'merge_fields' => $mergeFields
            ];
        }
        $response = $this->callServer('batches', 'POST', $body, true);
        return $response;
    }


    /**
     * Send an array of emails to be subscribed to a mailing list at Mail Chimp.
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
        $removed = $this->toArray($removedResponse);
        if (val('id', $removed) === $emailID && val('status', $removed) === 'unsubscribed') {
            $emailType = val('EMAIL_TYPE', $email, 'html');
            $mergeFields = json_encode(['EMAIL_TYPE' => $emailType]);
            $addBody = [
                'email_address' => val('NEW_EMAIL', $email),
                'status' => 'subscribed',
                'merge_fields' => $mergeFields
            ];
            $addResponse = $this->callServer("lists/{$listID}/members", 'POST', $addBody);
        }
    }

    /**
     * Get the information for one specific member of one specific mailing list at Mail Chimp.
     *
     * @param string $listID
     * @param array $emailAddress
     * @return Http\HttpResponse
     */
    function listMemberInfo($listID, $emailAddress) {
        return $this->callServer("lists/{$listID}/members/".md5(strtolower($emailAddress[0])));
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
        if ($array = json_decode($json, true)) {
            return $array;
        }
    }
}
